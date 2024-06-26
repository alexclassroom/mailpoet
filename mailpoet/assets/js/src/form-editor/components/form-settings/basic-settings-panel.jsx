import {
  BaseControl,
  Panel,
  PanelBody,
  RadioControl,
  SelectControl,
  TextareaControl,
  ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { isEqual } from 'lodash';
import { Selection } from './selection.jsx';
import { FormTitle } from '../form-title';
import { storeName } from '../../store';

function BasicSettingsPanel({ onToggle, isOpened }) {
  const { settings, segments, pages, missingListError, isFormEnabled } =
    useSelect(
      (select) => ({
        settings: select(storeName).getFormSettings(),
        segments: select(storeName).getAllAvailableSegments(),
        pages: select(storeName).getAllWPPages(),
        missingListError: select(storeName).getNotice('missing-lists'),
        isFormEnabled: select(storeName).isFormEnabled(),
      }),
      [],
    );

  const { changeFormSettings, toggleForm } = useDispatch(storeName);

  const onSegmentsChange = (e) => {
    // We don't want to update state when is same
    // It's a workaround because selection.jsx call handleChange,
    // when segments are restored from history
    if (isEqual(settings.segments, e.target.value)) {
      return;
    }
    changeFormSettings({
      ...settings,
      segments: e.target.value,
    });
  };

  const onSuccessTypeChange = (onSuccess) => {
    changeFormSettings({
      ...settings,
      on_success: onSuccess,
    });
  };

  const onSuccessMessageChange = (message) => {
    changeFormSettings({
      ...settings,
      success_message: message,
    });
  };

  const onSuccessPageChange = (message) => {
    changeFormSettings({
      ...settings,
      success_page: message,
    });
  };

  const selectedSegments = settings.segments
    ? segments.filter((seg) => settings.segments.includes(seg.id.toString()))
    : [];
  const shouldDisplayMissingListError =
    missingListError && !selectedSegments.length;
  return (
    <Panel>
      <PanelBody
        title={MailPoet.I18n.t('formSettings')}
        opened={isOpened}
        onToggle={onToggle}
      >
        <FormTitle />
        <ToggleControl
          label={MailPoet.I18n.t('displayForm')}
          checked={isFormEnabled}
          onChange={toggleForm}
        />
        <BaseControl
          label={MailPoet.I18n.t('settingsListLabel')}
          className={classnames({
            'mailpoet-form-missing-lists': shouldDisplayMissingListError,
          })}
        >
          {shouldDisplayMissingListError ? (
            <span className="mailpoet-form-lists-error">
              {MailPoet.I18n.t('settingsPleaseSelectList')}
            </span>
          ) : null}
          <Selection
            item={{
              segments: selectedSegments,
            }}
            onValueChange={onSegmentsChange}
            field={{
              id: 'segments',
              name: 'segments',
              values: segments,
              multiple: true,
              placeholder: MailPoet.I18n.t('settingsPleaseSelectList'),
              getLabel: (seg) =>
                `${seg.name} (${parseInt(
                  seg.subscribers,
                  10,
                ).toLocaleString()})`,
              filter: (seg) => !!(!seg.deleted_at && seg.type === 'default'),
            }}
          />
        </BaseControl>
        <RadioControl
          className="mailpoet-form-inline-radios__control"
          onChange={onSuccessTypeChange}
          selected={settings.on_success || 'message'}
          label={MailPoet.I18n.t('settingsAfterSubmit')}
          options={[
            {
              label: MailPoet.I18n.t('settingsShowMessage'),
              value: 'message',
            },
            {
              label: MailPoet.I18n.t('settingsGoToPage'),
              value: 'page',
            },
          ]}
        />
        {settings.on_success === 'page' ? (
          <SelectControl
            value={settings.success_page}
            options={pages.map((page) => ({
              value: page.id.toString(),
              label: page.name,
            }))}
            onChange={onSuccessPageChange}
          />
        ) : (
          <TextareaControl
            value={settings.success_message}
            onChange={onSuccessMessageChange}
            rows={3}
          />
        )}
      </PanelBody>
    </Panel>
  );
}

BasicSettingsPanel.propTypes = {
  onToggle: PropTypes.func.isRequired,
  isOpened: PropTypes.bool.isRequired,
};
BasicSettingsPanel.displayName = 'FormEditorBasicSettingsPanel';
export { BasicSettingsPanel };
