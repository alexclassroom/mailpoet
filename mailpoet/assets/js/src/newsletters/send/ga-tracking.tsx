import _ from 'underscore';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import ReactStringReplace from 'react-string-replace';
import { Field } from '../../form/types';

// Track once per page load
const trackCampaignNameTyped = _.once(() =>
  MailPoet.trackEvent('User has typed a GA campaign name'),
);
const tipLink =
  'https://kb.mailpoet.com/article/187-track-newsletters-subscribers-in-google-analytics';
const tip = ReactStringReplace(
  __('For example, “Spring email”. [link]Read the guide.[/link]', 'mailpoet'),
  /\[link\](.*?)\[\/link\]/g,
  (match, i) => (
    <span key={i}>
      <br />
      <a
        href={tipLink}
        target="_blank"
        rel="noopener noreferrer"
        className="mailpoet-link"
      >
        {match}
      </a>
    </span>
  ),
);

export const GATrackingField: Field = {
  name: 'ga_campaign',
  label: __('Google Analytics Campaign', 'mailpoet'),
  tip,
  type: 'text',
  onBeforeChange: trackCampaignNameTyped,
};
