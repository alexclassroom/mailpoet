/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { check, Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	useEffect,
	useRef,
	createInterpolateElement,
	memo,
} from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import { isEmail } from '@wordpress/url';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import {
	MailPoetEmailData,
	SendingPreviewStatus,
	storeName,
	editorCurrentPostType,
} from '../../store';
import { recordEvent, recordEventOnce } from '../../events';

function RawSendPreviewEmail() {
	const sendToRef = useRef( null );

	const {
		requestSendingNewsletterPreview,
		togglePreviewModal,
		updateSendPreviewEmail,
	} = useDispatch( storeName );

	const {
		toEmail: previewToEmail,
		isSendingPreviewEmail,
		sendingPreviewStatus,
		isModalOpened,
	} = useSelect( ( select ) => select( storeName ).getPreviewState(), [] );

	const [ mailpoetEmailData ] = useEntityProp(
		'postType',
		editorCurrentPostType,
		'mailpoet_data'
	) as [ MailPoetEmailData, unknown, unknown ];

	const handleSendPreviewEmail = () => {
		void requestSendingNewsletterPreview(
			mailpoetEmailData.id,
			previewToEmail
		);
	};

	const closeCallback = () => {
		recordEvent( 'send_preview_email_modal_closed' );
		void togglePreviewModal( false );
	};

	// We use this effect to focus on the input field when the modal is opened
	useEffect( () => {
		if ( isModalOpened ) {
			sendToRef.current?.focus();
			recordEvent( 'send_preview_email_modal_opened' );
		}
	}, [ isModalOpened ] );

	if ( ! isModalOpened ) {
		return null;
	}

	return (
		<Modal
			className="mailpoet-send-preview-email"
			title={ __( 'Send a test email', 'mailpoet' ) }
			onRequestClose={ closeCallback }
			focusOnMount={ false }
		>
			{ sendingPreviewStatus === SendingPreviewStatus.ERROR ? (
				<div className="mailpoet-send-preview-modal-notice-error">
					{ __(
						'Sorry, we were unable to send this email.',
						'mailpoet'
					) }
					<ul>
						<li>
							{ createInterpolateElement(
								__(
									'Please check your <link>sending method configuration</link> with your hosting provider.',
									'mailpoet'
								),
								{
									link: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
										<a
											href="admin.php?page=mailpoet-settings#mta"
											target="_blank"
											rel="noopener noreferrer"
											onClick={ () =>
												recordEvent(
													'send_preview_email_modal_check_sending_method_configuration_link_clicked'
												)
											}
										/>
									),
								}
							) }
						</li>
						<li>
							{ createInterpolateElement(
								__(
									'Or, sign up for MailPoet Sending Service to easily send emails. <link>Sign up for free</link>',
									'mailpoet'
								),
								{
									link: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
										<a
											href={ new URL(
												'free-plan',
												'https://www.mailpoet.com/'
											).toString() }
											key="sign-up-for-free"
											target="_blank"
											rel="noopener noreferrer"
											onClick={ () =>
												recordEvent(
													'send_preview_email_modal_sign_up_for_mailpoet_sending_service_link_clicked'
												)
											}
										/>
									),
								}
							) }
						</li>
					</ul>
				</div>
			) : null }
			<p>
				{ createInterpolateElement(
					__(
						'Send yourself a test email to test how your email would look like in different email apps. You can also test your spam score by sending a test email to <link1>{$serviceName}</link1>. <link2>Learn more</link2>.',
						'mailpoet'
					).replace( '{$serviceName}', 'Mail Tester' ),
					{
						link1: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
							<a
								href="https://www.mail-tester.com/"
								target="_blank"
								rel="noopener noreferrer"
								onClick={ () =>
									recordEvent(
										'send_preview_email_modal_send_test_email_to_mail_tester_link_clicked'
									)
								}
							/>
						),
						link2: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
							<a
								href="https://kb.mailpoet.com/article/147-test-your-spam-score-with-mail-tester"
								target="_blank"
								rel="noopener noreferrer"
								onClick={ () =>
									recordEvent(
										'send_preview_email_modal_learn_more_about_mail_tester_link_clicked'
									)
								}
							/>
						),
					}
				) }
			</p>
			<TextControl
				label={ __( 'Send to', 'mailpoet' ) }
				onChange={ ( email ) => {
					void updateSendPreviewEmail( email );
					recordEventOnce(
						'send_preview_email_modal_send_to_field_updated'
					);
				} }
				onKeyDown={ ( event ) => {
					const { keyCode } = event;
					if ( keyCode === ENTER ) {
						event.preventDefault();
						handleSendPreviewEmail();
						recordEvent(
							'send_preview_email_modal_send_to_field_key_code_enter'
						);
					}
				} }
				value={ previewToEmail }
				type="email"
				ref={ sendToRef }
				required
			/>
			{ sendingPreviewStatus === SendingPreviewStatus.SUCCESS ? (
				<p className="mailpoet-send-preview-modal-notice-success">
					<Icon icon={ check } style={ { fill: '#4AB866' } } />
					{ __( 'Test email sent successfully!', 'mailpoet' ) }
				</p>
			) : null }
			<div className="mailpoet-send-preview-modal-footer">
				<Button
					variant="tertiary"
					onClick={ () => {
						recordEvent(
							'send_preview_email_modal_close_button_clicked'
						);
						closeCallback();
					} }
				>
					{ __( 'Close', 'mailpoet' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ () => {
						handleSendPreviewEmail();
						recordEvent(
							'send_preview_email_modal_send_test_email_button_clicked'
						);
					} }
					disabled={
						isSendingPreviewEmail || ! isEmail( previewToEmail )
					}
				>
					{ isSendingPreviewEmail
						? __( 'Sending…', 'mailpoet' )
						: __( 'Send test email', 'mailpoet' ) }
				</Button>
			</div>
		</Modal>
	);
}

export const SendPreviewEmail = memo( RawSendPreviewEmail );
