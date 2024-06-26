import { MailPoet } from 'mailpoet';

export function* openEmailCustomizer(newsletterId?: string) {
  let id = newsletterId;
  if (!id) {
    const { res, success, error } = yield {
      type: 'CALL_API',
      endpoint: 'settings',
      action: 'set',
      data: { 'signup_confirmation.use_mailpoet_editor': 1 },
    };
    if (!success) {
      return { type: 'SAVE_FAILED', error };
    }
    id = res.data.signup_confirmation.transactional_email_id;
    MailPoet.trackEvent('Editor > Confirmation email customizer enabled');
  }
  MailPoet.trackEvent('User Open confirmation email customizer');
  window.location.href = `?page=mailpoet-newsletter-editor&id=${id}`;
  return null;
}
