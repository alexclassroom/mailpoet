import { useNavigate, useLocation, useParams } from 'react-router-dom';
import moment from 'moment';
import ReactStringReplace from 'react-string-replace';
import { __ } from '@wordpress/i18n';
import { Form } from 'form/form.jsx';
import { MailPoet } from 'mailpoet';
import { SubscribersLimitNotice } from 'notices/subscribers-limit-notice';
import { TopBarWithBoundary } from '../common/top-bar/top-bar';
import { BackButton, PageHeader } from '../common/page-header';

interface CustomField {
  id: number;
  name: string;
  type: string;
  params?: {
    values?: Record<string, string>;
  };
}

interface CustomFieldFormField {
  name: string;
  label: string;
  type: string;
  params?: Record<string, unknown>;
  values?: Record<string, string>;
  year_placeholder?: string;
  month_placeholder?: string;
  day_placeholder?: string;
  placeholder?: string;
}

interface Subscriber {
  wp_user_id: number;
  is_woocommerce_user: number;
  subscriptions?: Array<{
    segment_id: number;
    status: string;
    updated_at: string;
  }>;
}

interface Unsubscribe {
  createdAt: {
    date: string;
  };
  source: 'admin' | 'manage' | 'newsletter' | 'mp_api' | string;
  meta?: string;
  newsletterId?: string;
  newsletterSubject?: string;
}

interface FormValues {
  unsubscribes?: Unsubscribe[];
}

declare global {
  interface Window {
    mailpoet_custom_fields: CustomField[];
    mailpoet_api_version: string;
  }
}

interface BaseField {
  name: string;
  label: string;
  type: string;
}

interface TextField extends BaseField {
  type: 'text';
  disabled?: (subscriber: Subscriber) => boolean;
}

interface SelectField extends BaseField {
  type: 'select';
  automationId?: string;
  values: Record<string, string>;
}

interface SelectionField extends BaseField {
  type: 'selection';
  placeholder: string;
  tip: string;
  api_version: string;
  endpoint: string;
  multiple: boolean;
  selected: (subscriber: Subscriber) => number[] | null;
  filter: (segment: unknown) => boolean;
  getLabel: (segment: unknown) => string;
  getCount: (segment: unknown) => number;
  getSearchLabel: (segment: unknown, subscriber: Subscriber) => string;
}

interface TokenField extends BaseField {
  type: 'tokenField';
  placeholder: string;
  suggestedValues: unknown[];
  endpoint: string;
  getName: (tag: unknown) => string;
}

type FormField =
  | TextField
  | SelectField
  | SelectionField
  | TokenField
  | CustomFieldFormField;

const fields: FormField[] = [
  {
    name: 'email',
    label: MailPoet.I18n.t('email'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
      );
    },
  },
  {
    name: 'first_name',
    label: MailPoet.I18n.t('firstname'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
      );
    },
  },
  {
    name: 'last_name',
    label: MailPoet.I18n.t('lastname'),
    type: 'text',
    disabled: function disabled(subscriber: Subscriber) {
      return Boolean(
        Number(subscriber.wp_user_id > 0) ||
          Number(subscriber.is_woocommerce_user) === 1,
      );
    },
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    type: 'select',
    automationId: 'subscriber-status',
    values: {
      subscribed: MailPoet.I18n.t('subscribed'),
      unconfirmed: MailPoet.I18n.t('unconfirmed'),
      unsubscribed: MailPoet.I18n.t('unsubscribed'),
      inactive: MailPoet.I18n.t('inactive'),
      bounced: MailPoet.I18n.t('bounced'),
    },
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectList'),
    tip: MailPoet.I18n.t('welcomeEmailTip'),
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    selected: function selected(subscriber: Subscriber) {
      if (Array.isArray(subscriber.subscriptions) === false) {
        return null;
      }

      return subscriber.subscriptions
        .filter((subscription) => subscription.status === 'subscribed')
        .map((subscription) => subscription.segment_id);
    },
    filter: function filter(segment: unknown) {
      return (
        !(segment as { deleted_at?: string })?.deleted_at &&
        (segment as { type?: string })?.type === 'default'
      );
    },
    getLabel: function getLabel(segment: unknown) {
      return (segment as { name?: string })?.name || '';
    },
    getCount: function getCount(segment: unknown) {
      return (segment as { subscribers?: number })?.subscribers || 0;
    },
    getSearchLabel: function getSearchLabel(
      segment: unknown,
      subscriber: Subscriber,
    ) {
      let label = '';

      if (subscriber.subscriptions !== undefined) {
        subscriber.subscriptions.forEach((subscription) => {
          if ((segment as { id?: number })?.id === subscription.segment_id) {
            label = (segment as { name?: string })?.name || '';

            if (subscription.status === 'unsubscribed') {
              const unsubscribedAt = MailPoet.Date.format(
                subscription.updated_at,
              );
              label += ' (%1$s)'.replace(
                '%1$s',
                MailPoet.I18n.t('unsubscribedOn').replace(
                  '%1$s',
                  unsubscribedAt,
                ),
              );
            }
          }
        });
      }
      return label;
    },
  },
  {
    name: 'tags',
    label: MailPoet.I18n.t('tags'),
    type: 'tokenField',
    placeholder: MailPoet.I18n.t('addNewTag'),
    suggestedValues: [],
    endpoint: 'tags',
    getName: function getName(tag: unknown) {
      return Object.prototype.hasOwnProperty.call(tag, 'name')
        ? (tag as { name: string }).name
        : String(tag);
    },
  },
];

const customFields = window.mailpoet_custom_fields || [];
customFields.forEach((customField) => {
  const field: CustomFieldFormField = {
    name: `cf_${customField.id}`,
    label: customField.name,
    type: customField.type,
  };

  if (customField.params) {
    field.params = customField.params;
    if (customField.params.values) {
      field.values = customField.params.values;
    }
  }

  // add placeholders for selects (date, select)
  switch (customField.type) {
    case 'date':
      field.year_placeholder = MailPoet.I18n.t('year');
      field.month_placeholder = MailPoet.I18n.t('month');
      field.day_placeholder = MailPoet.I18n.t('day');
      break;

    case 'select':
      field.placeholder = '-';
      break;

    default:
      field.placeholder = '';
      break;
  }

  fields.push(field);
});

const messages = {
  onUpdate: function onUpdate() {
    MailPoet.Notice.success(MailPoet.I18n.t('subscriberUpdated'));
  },
  onCreate: function onCreate() {
    MailPoet.Notice.success(MailPoet.I18n.t('subscriberAdded'));
    MailPoet.trackEvent('Subscribers > Add new');
  },
};

function beforeFormContent(subscriber: Subscriber) {
  if (Number(subscriber.wp_user_id) > 0) {
    return (
      <p className="description">
        {ReactStringReplace(
          MailPoet.I18n.t('WPUserEditNotice'),
          /\[link\](.*?)\[\/link\]/g,
          (match, i) => (
            <a key={i} href={`user-edit.php?user_id=${subscriber.wp_user_id}`}>
              {match}
            </a>
          ),
        )}
      </p>
    );
  }
  return undefined;
}

function afterFormContent(values: FormValues) {
  return (
    <>
      {values?.unsubscribes?.map((unsubscribe) => {
        const date = moment(unsubscribe.createdAt.date).format(
          'dddd MMMM Do YYYY [at] h:mm:ss a',
        );
        let message;
        if (unsubscribe.source === 'admin') {
          message = MailPoet.I18n.t('unsubscribedAdmin')
            .replace('%1$d', date)
            .replace('%2$d', unsubscribe.meta || '');
        } else if (unsubscribe.source === 'manage') {
          message = MailPoet.I18n.t('unsubscribedManage').replace('%1$d', date);
        } else if (unsubscribe.source === 'newsletter') {
          message = ReactStringReplace(
            MailPoet.I18n.t('unsubscribedNewsletter').replace('%1$d', date),
            /\[link\]/g,
            (_match, i) => (
              <a
                key={i}
                href={`admin.php?page=mailpoet-newsletter-editor&id=${
                  unsubscribe.newsletterId || ''
                }`}
              >
                {unsubscribe.newsletterSubject || ''}
              </a>
            ),
          );
        } else if (unsubscribe.source === 'mp_api') {
          message = MailPoet.I18n.t('unsubscribedMpApi').replace('%1$d', date);
        } else {
          message = MailPoet.I18n.t('unsubscribedUnknown').replace(
            '%1$d',
            date,
          );
        }
        return (
          <p className="description" key={message}>
            {message}
          </p>
        );
      })}
      <p className="description">
        <strong>{MailPoet.I18n.t('tip')}</strong>{' '}
        {MailPoet.I18n.t('customFieldsTip')}
      </p>
    </>
  );
}

function SubscriberForm() {
  const location = useLocation();
  const params = useParams();
  const navigate = useNavigate();
  const backUrl = (location.state?.backUrl as string) || '/';
  return (
    <div className="mailpoet-main-container">
      <TopBarWithBoundary hideScreenOptions />

      <PageHeader
        heading={
          params.id
            ? __('Edit subscriber', 'mailpoet')
            : __('Add new subscriber', 'mailpoet')
        }
        headingPrefix={
          <BackButton
            onClick={() => navigate(backUrl)}
            label={MailPoet.I18n.t('backToList')}
          />
        }
      />

      <SubscribersLimitNotice />

      <Form
        automationId="subscriber_edit_form"
        endpoint="subscribers"
        fields={fields}
        params={params}
        messages={messages}
        beforeFormContent={beforeFormContent}
        afterFormContent={afterFormContent}
        onSuccess={() => navigate(backUrl)}
      />
    </div>
  );
}

SubscriberForm.displayName = 'SubscriberForm';

export { SubscriberForm };
