import { useEffect, useRef } from 'react';
import { MailPoet } from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { debounce } from 'lodash';
import { isFormValid } from './validator';
import { loadCount } from './subscribers-calculator';

import { Segment, SubscriberCount } from './types';
import { storeName } from './store';

function SubscribersCounter(): JSX.Element {
  const segment: Segment = useSelect(
    (select) => select(storeName).getSegment(),
    [],
  );

  const subscribersCount: SubscriberCount = useSelect(
    (select) => select(storeName).getSubscriberCount(),
    [],
  );

  const { updateSubscriberCount } = useDispatch(storeName);

  const serializedSegment = JSON.stringify(segment);
  const latestRequestIdRef = useRef(1);
  const deferredRequestRef = useRef<Segment>(null);
  const isRequestInFlight = useRef(false);

  function load(loadItem: Segment): void {
    // Don't allow multiple in-flight requests to avoid hammering the database
    // when we'll only ever use the results of the last request
    if (isRequestInFlight.current) {
      deferredRequestRef.current = loadItem;
      return;
    }

    deferredRequestRef.current = null;
    latestRequestIdRef.current += 1;
    const requestId = latestRequestIdRef.current;
    isRequestInFlight.current = true;

    loadCount(loadItem).then(
      (response) => {
        isRequestInFlight.current = false;
        if (deferredRequestRef.current) {
          load(deferredRequestRef.current);
          return;
        }
        if (requestId !== latestRequestIdRef.current) {
          // Don't do anything with the response because a newer request has been initiated
          return;
        }
        const finished = {} as SubscriberCount;
        finished.loading = false;
        if (response) {
          finished.count = response.count;
          finished.errors = response.errors;
        }
        void updateSubscriberCount(finished);
      },
      (errorResponse) => {
        isRequestInFlight.current = false;
        const finished = {} as SubscriberCount;
        const errors = errorResponse.errors.map((error) => error.message);
        finished.loading = false;
        finished.count = undefined;
        finished.errors = errors;
        void updateSubscriberCount(finished);
      },
    );
  }

  const debouncedLoadRef = useRef(debounce(load, 2000, { trailing: true }));

  useEffect(() => {
    if (isFormValid(segment.filters)) {
      void updateSubscriberCount({
        loading: true,
        count: undefined,
        errors: undefined,
      });
      const debouncedLoad = debouncedLoadRef.current;
      debouncedLoad(segment);
    } else {
      void updateSubscriberCount({
        count: undefined,
        loading: false,
      });
    }
  }, [segment, serializedSegment, updateSubscriberCount]);

  if (subscribersCount.errors) {
    return (
      <div className="mailpoet-form-field">
        <span className="mailpoet-form-error-message">
          {MailPoet.I18n.t('dynamicSegmentSizeCalculatingTimeout')}{' '}
          <a
            href="https://kb.mailpoet.com/article/237-guide-to-subscriber-segmentation"
            target="_blank"
            className="mailpoet-form-error-message"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('learnMore')}
          </a>
        </span>
      </div>
    );
  }

  if (!subscribersCount.loading && subscribersCount.count === undefined) {
    return <span />;
  }

  if (subscribersCount.loading) {
    return (
      <div className="mailpoet-form-field">
        <span className="mailpoet-form-notice-message">
          {MailPoet.I18n.t('dynamicSegmentSizeIsCalculated')}
        </span>
      </div>
    );
  }

  return (
    <div className="mailpoet-form-field">
      <span className="mailpoet-form-notice-message">
        {MailPoet.I18n.t('dynamicSegmentSize').replace(
          '%1$d',
          subscribersCount.count.toLocaleString(),
        )}
      </span>
    </div>
  );
}

export { SubscribersCounter };
