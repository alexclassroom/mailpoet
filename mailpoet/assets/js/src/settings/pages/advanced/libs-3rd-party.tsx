import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import ReactStringReplace from 'react-string-replace';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function Libs3rdParty() {
  const [enabled, setEnabled] = useSetting('3rd_party_libs', 'enabled');

  return (
    <>
      <Label
        title={t('libs3rdPartyTitle')}
        description={
          <>
            {ReactStringReplace(
              t('libs3rdPartyDescription'),
              /\[link\](.*?)\[\/link\]/,
              (match) => (
                <a
                  key={match}
                  href="https://www.mailpoet.com/support/"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {match}
                </a>
              ),
            )}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/338-enabledisable-3rd-party-libraries"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
          </>
        }
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="libs-3rd-party-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
        />
        <label htmlFor="libs-3rd-party-enabled">{t('yes')}</label>
        <span className="mailpoet-gap" />
        <Radio
          id="libs-3rd-party-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
        />
        <label htmlFor="libs-3rd-party-disabled">{t('no')}</label>
      </Inputs>
    </>
  );
}
