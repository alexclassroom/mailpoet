import { select } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { blockMeta } from '@wordpress/icons';
import { Edit } from './edit';
import { storeName, StepType } from '../../../../editor/store';
import { BranchBadge } from './branch-badge';
import { Footer } from './footer';

const keywords = [
  // translators: noun, used as a search keyword for "If/Else" automation action
  __('condition', 'mailpoet'),
  // translators: adjective, used as a search keyword for "If/Else" automation action
  __('conditional', 'mailpoet'),
  // translators: noun, used as a search keyword for "If/Else" automation action
  __('if/else', 'mailpoet'),
];

export const step: StepType = {
  key: 'core:if-else',
  group: 'actions',
  title: () => _x('If/Else', 'mailpoet'),
  description: () =>
    __(
      'The automation follows a different path based on specified conditions.',
      'mailpoet',
    ),
  subtitle: (data) => {
    const fieldKeys = [
      ...new Set(
        data.filters?.groups.flatMap((group) =>
          group.filters.map((filter) => filter.field_key),
        ),
      ),
    ];

    if (fieldKeys.length === 0) {
      return __('Not set up yet', 'mailpoet');
    }

    const subjects = Object.values(select(storeName).getRegistry().subjects);
    const subjectNames = subjects
      .filter((subject) =>
        subject.field_keys.find((key) => fieldKeys.includes(key)),
      )
      .map(({ name }) => name)
      .sort();

    // translators: %s is a list of subjects
    return sprintf(__('Based on %s', 'mailpoet'), subjectNames.join(', '));
  },
  keywords,
  foreground: '#1D2327',
  background: '#F0F0F1',
  icon: () => (
    <div
      style={{
        transform: 'rotate(90deg)',
      }}
    >
      {blockMeta}
    </div>
  ),
  edit: Edit,
  footer: Footer,
  branchBadge: BranchBadge,
  createStep: (stepData) => {
    const nextSteps = stepData.next_steps;
    return {
      ...stepData,
      next_steps: [
        ...nextSteps,
        ...Array.from({ length: 2 - nextSteps.length }, () => ({ id: null })),
      ],
    };
  },
} as const;
