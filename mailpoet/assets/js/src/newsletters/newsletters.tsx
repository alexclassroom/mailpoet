import { createRoot } from 'react-dom/client';
import {
  HashRouter,
  Navigate,
  Route,
  Routes,
  useParams,
} from 'react-router-dom';
import { __ } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import { NewsletterTemplates } from 'newsletters/templates.jsx';
import { NewsletterSend } from 'newsletters/send';
import { Congratulate } from 'newsletters/send/congratulate/congratulate.jsx';
import { NewsletterTypeStandard } from 'newsletters/types/standard.jsx';
import { NewsletterNotification } from 'newsletters/types/notification/notification';
import { NewsletterTypeReEngagement } from 'newsletters/types/re-engagement/re-engagement';
import { NewsletterListStandard } from 'newsletters/listings/standard.jsx';
import { NewsletterListNotification } from 'newsletters/listings/notification.jsx';
import { NewsletterListReEngagement } from 'newsletters/listings/re-engagement.jsx';
import { NewsletterListNotificationHistory } from 'newsletters/listings/notification-history.jsx';
import { SendingStatus } from 'newsletters/sending-status.jsx';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { Notices } from 'notices/notices.jsx';
import { RoutedTabs } from 'common/tabs/routed-tabs';
import { ErrorBoundary, registerTranslations, Tab, withBoundary } from 'common';
import { withNpsPoll } from 'nps-poll.jsx';
import { ListingHeading } from 'newsletters/listings/heading';
import { ListingHeadingDisplay } from 'newsletters/listings/heading-display.jsx';
import { TransactionalEmailsProposeOptInNotice } from 'notices/transactional-emails-propose-opt-in-notice';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { CampaignStatsPage } from './campaign-stats/page';
import { CorruptEmailNotice } from '../notices/corrupt-email-notice';
import { LegacyAutomaticEmailsNotice } from '../notices/legacy-automatic-emails-notice';
import { TopBarWithBoundary } from '../common/top-bar/top-bar';
import { BackButton, PageHeader } from '../common/page-header';

interface RouteConfig {
  path: string;
  name?: string;
  children: React.ComponentType;
}

const trackTabSwitch = (tabKey: string): void => {
  MailPoet.trackEvent(`Tab Emails > ${tabKey} clicked`);
};

const Tabs = withNpsPoll(() => {
  const { parentId } = useParams<{ parentId?: string }>();
  return (
    <>
      <ListingHeadingDisplay>
        <ListingHeading />
      </ListingHeadingDisplay>
      {window.mailpoet_legacy_automatic_emails_count > 0 &&
        !window.mailpoet_legacy_automatic_emails_notice_dismissed && (
          <LegacyAutomaticEmailsNotice />
        )}
      {MailPoet.corrupt_newsletters.length > 0 && (
        <CorruptEmailNotice newsletters={MailPoet.corrupt_newsletters} />
      )}
      <RoutedTabs
        activeKey="standard"
        routerType="switch-only"
        onSwitch={(tabKey: string) => trackTabSwitch(tabKey)}
        automationId="newsletters_listing_tabs"
      >
        <Tab
          key="standard"
          route="standard/*"
          title={__('Newsletters', 'mailpoet')}
          automationId={`tab-${__('Newsletters', 'mailpoet')}`}
        >
          <NewsletterListStandard />
        </Tab>
        <Tab
          key="notification"
          route="notification/*"
          title={__('Post Notifications', 'mailpoet')}
          automationId={`tab-${__('Post Notifications', 'mailpoet')}`}
        >
          {parentId ? (
            <NewsletterListNotificationHistory parentId={parentId} />
          ) : (
            <NewsletterListNotification />
          )}
        </Tab>
        <Tab
          key="re_engagement"
          route="re_engagement/*"
          title={__('Re-engagement Emails', 'mailpoet')}
          automationId={`tab-${__('Re-engagement Emails', 'mailpoet')}`}
        >
          <NewsletterListReEngagement />
        </Tab>
      </RoutedTabs>
    </>
  );
});

function NewNewsletter() {
  return (
    <ErrorBoundary>
      <TopBarWithBoundary />
      <div className="mailpoet-main-container">
        <PageHeader
          heading={__('What would you like to create?', 'mailpoet')}
          headingPrefix={
            <BackButton
              href="#/"
              label={__('Listing', 'mailpoet')}
              aria-label={__('Go back to email listing page', 'mailpoet')}
            />
          }
        />
        <NewsletterTypes />
      </div>
    </ErrorBoundary>
  );
}

const routes: RouteConfig[] = [
  /* Listings */
  {
    path: '/notification/history/:parentId/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/standard/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/notification/*',
    children: withBoundary(Tabs),
  },
  {
    path: '/re_engagement/*',
    children: withBoundary(Tabs),
  },
  /* New newsletter: types */
  {
    path: '/new/standard',
    children: withBoundary(NewsletterTypeStandard),
  },
  {
    path: '/new/notification',
    children: withBoundary(NewsletterNotification),
  },
  {
    path: '/new/re-engagement',
    children: withBoundary(NewsletterTypeReEngagement),
  },
  /* Newsletter: type selection */
  {
    path: '/new',
    children: withBoundary(NewNewsletter),
  },
  /* Template selection */
  {
    name: 'template',
    path: '/template/:id',
    children: withBoundary(NewsletterTemplates),
  },
  /* congratulate */
  {
    path: '/send/congratulate/:id',
    children: withBoundary(Congratulate),
  },
  /* Sending options */
  {
    path: '/send/:id',
    children: withBoundary(NewsletterSend),
  },
  {
    path: '/sending-status/:id/*',
    children: withBoundary(SendingStatus),
  },
  {
    path: '/stats/:id/*',
    children: withBoundary(CampaignStatsPage),
  },
];

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <HashRouter>
        <GlobalNotices />
        <Notices />
        <ErrorBoundary>
          <TransactionalEmailsProposeOptInNotice
            mailpoetInstalledDaysAgo={MailPoet.installedDaysAgo}
            sendTransactionalEmails={MailPoet.transactionalEmailsEnabled}
            mtaMethod={MailPoet.mtaMethod}
            apiVersion={MailPoet.apiVersion}
            noticeDismissed={MailPoet.transactionalEmailsOptInNoticeDismissed}
          />
        </ErrorBoundary>
        <ErrorBoundary>
          <MssAccessNotices />
        </ErrorBoundary>
        <Routes>
          <Route
            path="/"
            element={
              <Navigate
                to={
                  window.mailpoet_newsletters_count === 0 ? '/new' : '/standard'
                }
              />
            }
          />
          {routes.map((route) => (
            <Route
              key={route.path}
              path={route.path}
              element={<route.children />}
            />
          ))}
        </Routes>
      </HashRouter>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('newsletters_container');
if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}
