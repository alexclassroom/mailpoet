import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { dispatch, select, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TopBarWithBoundary } from '../../../../common/top-bar/top-bar';
import { Notices } from '../../../listing/components/notices';
import { Header } from './components/header';
import { Overview } from './components/overview';
import { Tabs } from './components/tabs';
import { createStore, Section, storeName } from './store';
import {
  createStore as editorStoreCreate,
  storeName as editorStoreName,
} from '../../../editor/store';
import { registerApiErrorHandler } from '../../../listing/api-error-handler';
import { initializeApi } from './api';
import { PremiumModal } from '../../../../common/premium-modal';
import { MailPoet } from '../../../../mailpoet';
import { initializeIntegrations } from '../../../editor/integrations';
import { AutomationStatus } from '../../../components/status';

function Analytics(): JSX.Element {
  const premiumModal = useSelect((s) => s(storeName).getPremiumModal());
  const { closePremiumModal } = dispatch(storeName);

  return (
    <div className="mailpoet-automation-analytics">
      <Header />
      <Overview />
      <Tabs />
      {premiumModal && (
        <PremiumModal
          onRequestClose={closePremiumModal}
          tracking={{
            utm_campaign: premiumModal.utmCampaign ?? 'automation_analytics',
          }}
          data={premiumModal.data}
        >
          {premiumModal.content}
        </PremiumModal>
      )}
    </div>
  );
}

function TopBarWithBreadcrumb(): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));

  return (
    <TopBarWithBoundary>
      <p className="mailpoet-automation-analytics-title">
        <a href={MailPoet.urls.automationListing}>
          {__('Automations', 'mailpoet')}
        </a>{' '}
        › <strong>{automation.name}</strong>
        <AutomationStatus status={automation.status} />
      </p>
    </TopBarWithBoundary>
  );
}

function App(): JSX.Element {
  return (
    <BrowserRouter>
      <TopBarWithBreadcrumb />
      <Notices />
      <Analytics />
    </BrowserRouter>
  );
}

function boot() {
  initializeApi();
  select(storeName)
    .getSections()
    .forEach((section: Section) => {
      void dispatch(storeName).updateSection(section);
    });
}

window.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('mailpoet_automation_analytics');
  if (!container) {
    return;
  }
  createStore();
  editorStoreCreate();
  initializeIntegrations();
  registerApiErrorHandler();
  boot();
  const root = createRoot(container);
  root.render(<App />);
});
