import { useState } from 'react';
import { __, assoc, compose } from 'lodash/fp';
import { useNavigate } from 'react-router-dom';
import { __ as t } from '@wordpress/i18n';

import { MailPoet } from 'mailpoet';
import { Grid } from 'common/grid';
import { Button } from 'common/button/button';
import { APIErrorsNotice } from 'notices/api-errors-notice';

import { Scheduling } from './scheduling';
import { ListingHeadingStepsRoute } from '../../listings/heading-steps-route';

export function NewsletterTypeReEngagement(): JSX.Element {
  let defaultAfterTime = '11';
  if (MailPoet.deactivateSubscriberAfterInactiveDays) {
    defaultAfterTime = (
      Math.floor(Number(MailPoet.deactivateSubscriberAfterInactiveDays) / 30) -
      1
    ).toString();
  }

  const [options, setOptions] = useState({
    afterTimeNumber: defaultAfterTime,
    afterTimeType: 'months',
  });
  const [errors, setErrors] = useState([]);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  function showTemplateSelection(newsletterId: string) {
    navigate(`/template/${newsletterId}`);
  }

  function handleNext() {
    setErrors([]);
    setLoading(true);
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 're_engagement',
        subject: t('Subject', 'mailpoet'),
        options,
      },
    })
      .done((response) => {
        showTemplateSelection(response.data.id as string);
      })
      .fail((response) => {
        setLoading(false);
        if (response.errors) {
          setErrors(response.errors as { message: string }[]);
        }
      });
  }

  return (
    <div>
      {errors && <APIErrorsNotice errors={errors} />}

      <ListingHeadingStepsRoute
        emailType="re_engagement"
        automationId="re_engagement_heading_creation_heading"
      />

      <Grid.Column align="center" className="mailpoet-schedule-email">
        <Scheduling
          afterTimeNumber={options.afterTimeNumber}
          afterTimeType={options.afterTimeType}
          inactiveSubscribersPeriod={Number(
            MailPoet.deactivateSubscriberAfterInactiveDays,
          )}
          updateAfterTimeNumber={compose([
            setOptions,
            assoc('afterTimeNumber', __, options),
          ])}
          updateAfterTimeType={compose([
            setOptions,
            assoc('afterTimeType', __, options),
          ])}
        />

        <Button
          isFullWidth
          onClick={() => handleNext()}
          type="button"
          isDisabled={!options.afterTimeNumber || loading}
          withSpinner={loading}
        >
          {t('Next', 'mailpoet')}
        </Button>
      </Grid.Column>
    </div>
  );
}

NewsletterTypeReEngagement.displayName = 'NewsletterTypeReEngagement';
