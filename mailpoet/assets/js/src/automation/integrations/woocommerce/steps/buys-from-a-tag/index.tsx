import { __ } from '@wordpress/i18n';
import { StepType } from '../../../../editor/store';
import { Edit } from './edit';
import { Icon } from './icon';

const keywords = [
  // translators: noun, used as a search keyword for "Customer buys from a tag" trigger
  __('tag', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer buys from a tag" trigger
  __('buy', 'mailpoet'),
  // translators: verb, used as a search keyword for "Customer buys from a tag" trigger
  __('purchase', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a tag" trigger
  __('ecommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a tag" trigger
  __('woocommerce', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a tag" trigger
  __('product', 'mailpoet'),
  // translators: noun, used as a search keyword for "Customer buys from a tag" trigger
  __('order', 'mailpoet'),
];
export const step: StepType = {
  key: 'woocommerce:buys-from-a-tag',
  group: 'triggers',
  title: () => __('Customer buys from a tag', 'mailpoet'),
  description: () =>
    __(
      'Start the automation when a customer buys a product from a tag.',
      'mailpoet',
    ),

  subtitle: () => __('Trigger', 'mailpoet'),
  keywords,
  foreground: '#2271b1',
  background: '#f0f6fc',
  icon: () => <Icon />,
  edit: () => <Edit />,
} as const;
