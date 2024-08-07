import { useCallback, useMemo } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import classNames from 'classnames';
import { TabPanel } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';
import { Icon, lockSmall } from '@wordpress/icons';
import { AutomationFlow } from './automation-flow';
import { Emails } from './emails';
import { Orders } from './orders';
import { Subscribers } from './subscribers';
import { storeName as editorStoreName } from '../../../../../editor/store/constants';
import { MailPoet } from '../../../../../../mailpoet';

export function Tabs(): JSX.Element {
  const navigate = useNavigate();
  const location = useLocation();
  const { hasEmails } = useSelect((s) => ({
    hasEmails: s(editorStoreName).automationHasStep('mailpoet:send-email'),
  }));
  const pageParams = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );
  const currentTab = pageParams.get('tab') ?? 'automation-flow';
  const tabs = [
    {
      name: 'automation-flow',
      className: 'mailpoet-analytics-tab-flow',
      title: __('Automation flow', 'mailpoet'),
    },
    {
      name: 'automation-subscribers',
      className: classNames(
        'mailpoet-analytics-tab-subscribers',
        !MailPoet.capabilities.detailedAnalytics.isRestricted
          ? 'is-unlocked'
          : '',
      ),
      title: (
        <>
          {__('Subscribers', 'mailpoet')} <Icon icon={lockSmall} />
        </>
      ) as unknown as string,
    },
  ];
  if (hasEmails) {
    tabs.push({
      name: 'automation-emails',
      className: 'mailpoet-analytics-tab-emails',
      title: __('Emails', 'mailpoet'),
    });
    if (MailPoet.isWoocommerceActive) {
      tabs.push({
        name: 'automation-orders',
        className: classNames(
          'mailpoet-analytics-tab-orders',
          !MailPoet.capabilities.detailedAnalytics.isRestricted
            ? 'is-unlocked'
            : '',
        ),
        // title is defined as string but allows for JSX.Element
        title: (
          <>
            {_x('Orders', 'WooCommerce orders', 'mailpoet')}{' '}
            <Icon icon={lockSmall} />
          </>
        ) as unknown as string,
      });
    }
  }

  const updateUrlSearchString = useCallback(
    (tab: string) => {
      const newSearch = new URLSearchParams({
        ...Object.fromEntries(pageParams.entries()),
        ...{
          tab,
        },
      });
      navigate({ search: newSearch.toString() });
    },
    [pageParams, navigate],
  );

  return (
    <div className="mailpoet-analytics-tabs">
      <TabPanel
        onSelect={updateUrlSearchString}
        initialTabName={currentTab}
        tabs={tabs}
      >
        {(tab) => {
          switch (tab.name) {
            case 'automation-flow':
              return <AutomationFlow />;
            case 'automation-emails':
              return <Emails />;
            case 'automation-orders':
              return <Orders />;
            case 'automation-subscribers':
              return <Subscribers />;
            default:
              return null;
          }
        }}
      </TabPanel>
    </div>
  );
}
