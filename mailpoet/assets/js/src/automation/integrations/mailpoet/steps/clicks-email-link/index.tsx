import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';
import { PremiumModalForStepEdit } from '../../../../components/premium-modal-steps-edit';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Subscriber clicks a link in email" trigger
  __('email', 'mailpoet'),
  // translators: noun, used as a search keyword for "Subscriber clicks a link in email" trigger
  __('link', 'mailpoet'),
  // translators: noun, used as a search keyword for "Subscriber clicks a link in email" trigger
  __('clicks', 'mailpoet'),
];

export const step: StepType = {
  key: 'mailpoet:clicks-email-link',
  group: 'triggers',
  title: () => __('Subscriber clicks a link in email', 'mailpoet'),
  description: () =>
    __(
      'Triggers an automation when a subscriber clicks a link in an email.',
      'mailpoet',
    ),
  subtitle: () => <LockedBadge text={__('Premium', 'mailpoet')} />,
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => (
    <PremiumModalForStepEdit
      tracking={{
        utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_clicks_email_link',
      }}
    >
      {__(
        'Triggering an automation by following an email link is a premium feature.',
        'mailpoet',
      )}
    </PremiumModalForStepEdit>
  ),
} as const;
