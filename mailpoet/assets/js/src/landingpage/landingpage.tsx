import { createRoot } from 'react-dom/client';
import { GlobalContext, useGlobalContextValue } from 'context';
import { ErrorBoundary, registerTranslations } from 'common';
import { Background } from 'common/background/background';
import { TopBarWithBoundary } from 'common/top-bar/top-bar';
import { Header } from './header';
import { Footer } from './footer';
import { Faq } from './faq';
import { Content } from './content';

function Landingpage() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <main>
        <TopBarWithBoundary hideScreenOptions />

        <Background color="#fff" />

        <Header />

        <div className="mailpoet-gap" />

        <Content />

        <div className="mailpoet-gap" />

        <Faq />

        <Footer />
      </main>
    </GlobalContext.Provider>
  );
}

Landingpage.displayName = 'Landingpage';

const container = document.getElementById('mailpoet_landingpage_container');

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(
    <ErrorBoundary>
      <Landingpage />
    </ErrorBoundary>,
  );
}
