import { __ } from '@wordpress/i18n';
import { Button, Flex, FlexItem, Modal } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { storeName } from '../../store';
import { store as editorStore } from '@wordpress/editor';

export function EditTemplateModal( { close } ) {
	const { onNavigateToEntityRecord, template } = useSelect( ( sel ) => {
		const { getEditorSettings } = sel( editorStore );
		const editorSettings = getEditorSettings();
		return {
			onNavigateToEntityRecord:
				// @ts-expect-error onNavigateToEntityRecord type is not defined
				editorSettings.onNavigateToEntityRecord,
			template: sel( storeName ).getCurrentTemplate(),
		};
	}, [] );

	return (
		<Modal size="medium" onRequestClose={ close } __experimentalHideHeader>
			<p>
				{ __(
					'Note that the same template can be used by multiple emails, so any changes made here may affect other emails on the site. To switch back to editing the page content click the ‘Back’ button in the toolbar.',
					'mailpoet'
				) }
			</p>
			<Flex justify={ 'end' }>
				<FlexItem>
					<Button
						variant="tertiary"
						onClick={ () => {
							close();
						} }
					>
						{ __( 'Cancel', 'mailpoet' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="primary"
						onClick={ () => {
							onNavigateToEntityRecord( {
								postId: template.id,
								postType: 'wp_template',
							} );
						} }
						disabled={ ! template.id }
					>
						{ __( 'Continue', 'mailpoet' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
