import { useCallback, useEffect, useRef, useState } from 'react';
import { MailPoet } from 'mailpoet';
import { SelectControl, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Modal } from 'common/modal/modal';
import { Preview } from 'common/preview/preview.jsx';
import { SettingsPanel } from 'form-editor/components/form-settings/form-placement-options/settings-panel';
import { ErrorBoundary } from 'common';
import { storeName } from '../../store';

function FormPreview(): JSX.Element {
  const iframeElement = useRef(null);
  const [iframeLoaded, setIframeLoaded] = useState(false);
  const { hidePreview, changePreviewSettings } = useDispatch(storeName);
  const isPreview = useSelect(
    (select) => select(storeName).getIsPreviewShown(),
    [],
  );
  const isPreviewReady = useSelect(
    (select) => select(storeName).getIsPreviewReady(),
    [],
  );

  const previewSettings = useSelect(
    (select) => select(storeName).getPreviewSettings(),
    [],
  );

  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );

  const formId = useSelect((select) => select(storeName).getFormData().id, []);

  const editorUrl = useSelect((select) => select(storeName).getEditorUrl(), []);

  const previewPageUrl = useSelect(
    (select) => select(storeName).getPreviewPageUrl(),
    [],
  );

  useEffect(() => {
    setIframeLoaded(false);
    const beacon = document.getElementById('beacon-container');
    if (isPreview && beacon) {
      // hide beacon in the preview modal
      beacon.style.display = 'none';
    }
  }, [isPreview]);

  useEffect(() => {
    if (!iframeElement.current || !iframeLoaded) {
      return;
    }
    const data = { formType: previewSettings.formType, formSettings };
    iframeElement.current.contentWindow.postMessage(data, previewPageUrl);
  }, [
    formSettings,
    iframeElement,
    previewSettings,
    iframeLoaded,
    previewPageUrl,
  ]);

  const closePreview = useCallback((): void => {
    const beacon = document.getElementById('beacon-container');
    if (beacon) {
      beacon.style.display = 'block';
    }
    void hidePreview();
  }, [hidePreview]);

  const setFormType = useCallback(
    (type): void => {
      setIframeLoaded(false);
      void changePreviewSettings({ ...previewSettings, formType: type });
    },
    [changePreviewSettings, previewSettings],
  );

  const onPreviewTypeChange = useCallback(
    (type): void => {
      void changePreviewSettings({ ...previewSettings, displayType: type });
    },
    [changePreviewSettings, previewSettings],
  );

  if (!isPreview) return null;

  const urlData = {
    id: formId,
    form_type: previewSettings.formType,
    editor_url: editorUrl,
  };
  let iframeSrc = `${previewPageUrl}&data=${btoa(JSON.stringify(urlData))}`;
  // Add anchor to scroll to certain types of form
  if (['below_posts'].includes(previewSettings.formType)) {
    iframeSrc += `#mailpoet_form_preview_${formId}`;
  }
  return (
    <Modal
      onRequestClose={closePreview}
      fullScreen
      contentClassName="mailpoet_form_preview_modal"
    >
      {!isPreviewReady && (
        <div className="mailpoet_spinner_wrapper">
          <Spinner />
        </div>
      )}
      {isPreviewReady && (
        <div className="mailpoet_preview_content_wrapper">
          <div className="mailpoet_preview_sidebar">
            <SelectControl
              label={MailPoet.I18n.t('formPlacementLabel')}
              value={previewSettings.formType}
              onChange={setFormType}
              className="mailpoet_preview_form_type_selection"
              data-automation-id="form_type_selection"
              options={[
                { value: 'others', label: __('Others (widget)', 'mailpoet') },
                {
                  value: 'below_posts',
                  label: __('Below pages', 'mailpoet'),
                },
                {
                  value: 'fixed_bar',
                  label: __('Fixed bar', 'mailpoet'),
                },
                {
                  value: 'popup',
                  label: __('Pop-up', 'mailpoet'),
                },
                {
                  value: 'slide_in',
                  label: __('Slide–in', 'mailpoet'),
                },
              ]}
            />
            <SettingsPanel activePanel={previewSettings.formType} />
          </div>
          <ErrorBoundary>
            <Preview
              onDisplayTypeChange={onPreviewTypeChange}
              selectedDisplayType={previewSettings.displayType}
            >
              {!iframeLoaded && (
                <div className="mailpoet_spinner_wrapper">
                  <Spinner />
                </div>
              )}
              <iframe
                ref={iframeElement}
                className="mailpoet_form_preview_iframe"
                src={iframeSrc}
                title={MailPoet.I18n.t('formPreview')}
                onLoad={(): void => setIframeLoaded(true)}
                data-automation-id="form_preview_iframe"
                scrolling={previewSettings.formType === 'others' ? 'no' : 'yes'}
              />
              {previewSettings.formType === 'others' &&
                previewSettings.displayType === 'desktop' && (
                  <div className="mailpoet_form_preview_disclaimer">
                    {MailPoet.I18n.t('formPreviewOthersDisclaimer')}
                  </div>
                )}
            </Preview>
          </ErrorBoundary>
        </div>
      )}
    </Modal>
  );
}

FormPreview.displayName = 'FormPreviewWrapper';
export { FormPreview };
