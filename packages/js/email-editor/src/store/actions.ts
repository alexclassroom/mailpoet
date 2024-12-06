import { dispatch, select } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';
import {
	store as coreStore,
	store as coreDataStore,
} from '@wordpress/core-data';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as noticesStore } from '@wordpress/notices';
import { store as editorStore } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { apiFetch } from '@wordpress/data-controls';
import wpApiFetch from '@wordpress/api-fetch';
import { storeName, mainSidebarDocumentTab } from './constants';
import { SendingPreviewStatus, State, Feature, EmailTheme } from './types';
import { addQueryArgs } from '@wordpress/url';
import {
	// @ts-expect-error No types for __unstableSerializeAndClean
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__unstableSerializeAndClean,
	parse,
} from '@wordpress/blocks';
import { decodeEntities } from '@wordpress/html-entities';

export const toggleFeature =
	( feature: Feature ) =>
	( { registry } ): unknown =>
		registry.dispatch( preferencesStore ).toggle( storeName, feature );

export const changePreviewDeviceType =
	( deviceType: string ) =>
	( { registry } ) =>
		void registry.dispatch( editorStore ).setDeviceType( deviceType );

export function togglePreviewModal( isOpen: boolean ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { isModalOpened: isOpen } as Partial< State[ 'preview' ] >,
	} as const;
}

export function updateSendPreviewEmail( toEmail: string ) {
	return {
		type: 'CHANGE_PREVIEW_STATE',
		state: { toEmail } as Partial< State[ 'preview' ] >,
	} as const;
}

export const openSidebar =
	( key = mainSidebarDocumentTab ) =>
	( { registry } ): unknown =>
		registry
			.dispatch( interfaceStore )
			.enableComplementaryArea( storeName, key );

export const closeSidebar =
	() =>
	( { registry } ): unknown =>
		registry
			.dispatch( interfaceStore )
			.disableComplementaryArea( storeName );

export function toggleSettingsSidebarActiveTab( activeTab: string ) {
	return {
		type: 'TOGGLE_SETTINGS_SIDEBAR_ACTIVE_TAB',
		state: { activeTab } as Partial< State[ 'settingsSidebar' ] >,
	} as const;
}

export function* saveEditedEmail() {
	const postId = select( storeName ).getEmailPostId();
	// This returns a promise

	const result = yield dispatch( coreDataStore ).saveEditedEntityRecord(
		'postType',
		'mailpoet_email',
		postId,
		{ throwOnError: true }
	);

	result.then( () => {
		void dispatch( noticesStore ).createErrorNotice(
			__( 'Email saved!', 'mailpoet' ),
			{
				type: 'snackbar',
				isDismissible: true,
				context: 'email-editor',
			}
		);
	} );

	result.catch( () => {
		void dispatch( noticesStore ).createErrorNotice(
			__(
				'The email could not be saved. Please, clear browser cache and reload the page. If the problem persists, duplicate the email and try again.',
				'mailpoet'
			),
			{
				type: 'default',
				isDismissible: true,
				context: 'email-editor',
			}
		);
	} );
}

export function* updateEmailMailPoetProperty( name: string, value: string ) {
	const postId = select( storeName ).getEmailPostId();
	// There can be a better way how to get the edited post data
	const editedPost = select( coreDataStore ).getEditedEntityRecord(
		'postType',
		'mailpoet_email',
		postId
	);
	// @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
	const mailpoetData = editedPost?.mailpoet_data || {};
	yield dispatch( coreDataStore ).editEntityRecord(
		'postType',
		'mailpoet_email',
		postId,
		{
			mailpoet_data: {
				...mailpoetData,
				[ name ]: value,
			},
		}
	);
}

export const setTemplateToPost =
	( templateSlug, emailTheme: EmailTheme ) =>
	async ( { registry } ) => {
		const postId = registry.select( storeName ).getEmailPostId();
		registry
			.dispatch( coreDataStore )
			.editEntityRecord( 'postType', 'mailpoet_email', postId, {
				template: templateSlug,
				meta: {
					mailpoet_email_theme: emailTheme,
				},
			} );
	};

export function* requestSendingNewsletterPreview(
	newsletterId: number,
	email: string
) {
	// If preview is already sending do nothing
	const previewState = select( storeName ).getPreviewState();
	if ( previewState.isSendingPreviewEmail ) {
		return;
	}
	// Initiate sending
	yield {
		type: 'CHANGE_PREVIEW_STATE',
		state: {
			sendingPreviewStatus: null,
			isSendingPreviewEmail: true,
		} as Partial< State[ 'preview' ] >,
	} as const;
	try {
		const postId = select( storeName ).getEmailPostId();

		yield apiFetch( {
			path: '/mailpoet-email-editor/v1/send_preview_email',
			method: 'POST',
			data: {
				newsletterId,
				email,
				postId,
			},
		} );

		yield {
			type: 'CHANGE_PREVIEW_STATE',
			state: {
				sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
				isSendingPreviewEmail: false,
			},
		};
	} catch ( errorResponse ) {
		yield {
			type: 'CHANGE_PREVIEW_STATE',
			state: {
				sendingPreviewStatus: SendingPreviewStatus.ERROR,
				isSendingPreviewEmail: false,
			},
		};
	}
}

/**
 * Revert template modifications to defaults
 * Created based on https://github.com/WordPress/gutenberg/blob/4d225cc2ba6f09822227e7a820b8a555be7c4d48/packages/editor/src/store/private-actions.js#L241
 * @param template - Template post object
 */
export function revertAndSaveTemplate( template ) {
	return async ( { registry } ) => {
		try {
			const templateEntityConfig = registry
				.select( coreStore )
				.getEntityConfig( 'postType', template.type as string );

			const fileTemplatePath = addQueryArgs(
				`${ templateEntityConfig.baseURL as string }/${
					template.id as string
				}`,
				{ context: 'edit', source: 'theme' }
			);

			const fileTemplate = await wpApiFetch( { path: fileTemplatePath } );

			const serializeBlocks = ( {
				blocks: blocksForSerialization = [],
			} ) => __unstableSerializeAndClean( blocksForSerialization );

			// @ts-expect-error template type is not defined
			const blocks = parse( fileTemplate?.content?.raw as string );

			await registry.dispatch( coreStore ).editEntityRecord(
				'postType',
				template.type as string,
				// @ts-expect-error template type is not defined
				fileTemplate.id as string,
				{
					content: serializeBlocks,
					blocks,
					source: 'theme',
				}
			);
			await registry
				.dispatch( coreStore )
				.saveEditedEntityRecord(
					'postType',
					template.type,
					template.id,
					{}
				);
			void registry.dispatch( noticesStore ).createSuccessNotice(
				sprintf(
					/* translators: The template/part's name. */
					__( '"%s" reset.', 'mailpoet' ),
					decodeEntities( template.title )
				),
				{
					type: 'snackbar',
					id: 'edit-site-template-reverted',
				}
			);
		} catch ( error ) {
			void registry
				.dispatch( noticesStore )
				.createErrorNotice(
					__(
						'An error occurred while reverting the template.',
						'mailpoet'
					),
					{
						type: 'snackbar',
					}
				);
		}
	};
}
