import { find } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react-select/react-select';
import { Grid } from 'common/grid';

import { Select } from 'common/form/select/select';
import {
  WordpressRoleFormItem,
  SelectOption,
  WindowCustomFields,
  FilterProps,
  isBlankOption,
  BlankOptions,
} from '../../../../types';
import { storeName } from '../../../../store';

interface ParamsType {
  values?: {
    value: string;
  }[];
}

export function validateRadioSelect(item: WordpressRoleFormItem): boolean {
  if (isBlankOption(item.operator)) {
    return true;
  }
  return typeof item.value === 'string' && item.value.length > 0;
}

export function RadioSelect({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  const customFieldsList: WindowCustomFields = useSelect(
    (select) => select(storeName).getCustomFieldsList(),
    [],
  );
  const customField = find(
    { id: Number(segment.custom_field_id) },
    customFieldsList,
  );
  if (!customField) return null;
  const params = customField.params as ParamsType;
  if (!params || !Array.isArray(params.values)) return null;

  const options = params.values.map((currentValue) => ({
    value: currentValue.value,
    label: currentValue.value,
  }));

  const matchingLabel = options.find(
    (option) => option.value === segment.value,
  )?.label;

  return (
    <Grid.CenteredRow>
      <Select
        key="select"
        automationId="text-custom-field-operator"
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value="equals">{MailPoet.I18n.t('is')}</option>
        <option value={BlankOptions.BLANK}>{MailPoet.I18n.t('isBlank')}</option>
        <option value={BlankOptions.NOT_BLANK}>
          {MailPoet.I18n.t('isNotBlank')}
        </option>
      </Select>
      {!isBlankOption(segment.operator) && (
        <ReactSelect
          dimension="small"
          placeholder={MailPoet.I18n.t('selectValue')}
          options={options}
          value={
            segment.value && matchingLabel
              ? { value: segment.value, label: matchingLabel }
              : null
          }
          onChange={(option: SelectOption): void => {
            void updateSegmentFilter({ value: option.value }, filterIndex);
          }}
          automationId="segment-wordpress-role"
        />
      )}
    </Grid.CenteredRow>
  );
}
