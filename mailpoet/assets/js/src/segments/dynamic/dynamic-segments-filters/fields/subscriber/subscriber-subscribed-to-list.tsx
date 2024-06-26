import { useEffect } from 'react';
import { map, filter, parseInt } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { Select } from 'common/form/select/select';
import { ReactSelect } from 'common/form/react-select/react-select';

import {
  AnyValueTypes,
  FilterProps,
  SelectOption,
  StaticSegment,
  WordpressRoleFormItem,
} from '../../../types';
import { storeName } from '../../../store';

export function validateSubscribedToList(
  formItems: WordpressRoleFormItem,
): boolean {
  return (
    (formItems.operator === AnyValueTypes.ANY ||
      formItems.operator === AnyValueTypes.ALL ||
      formItems.operator === AnyValueTypes.NONE) &&
    Array.isArray(formItems.segments) &&
    formItems.segments.length > 0
  );
}

export function SubscribedToList({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );
  const staticSegmentsList: StaticSegment[] = useSelect(
    (select) => select(storeName).getStaticSegmentsList(),
    [],
  );

  const { updateSegmentFilter, updateSegmentFilterFromEvent } =
    useDispatch(storeName);

  useEffect(() => {
    if (
      segment.operator !== AnyValueTypes.ANY &&
      segment.operator !== AnyValueTypes.ALL &&
      segment.operator !== AnyValueTypes.NONE
    ) {
      void updateSegmentFilter({ operator: AnyValueTypes.ANY }, filterIndex);
    }
  }, [updateSegmentFilter, segment, filterIndex]);
  const options = staticSegmentsList.map((currentValue) => ({
    value: currentValue.id,
    label: currentValue.name,
  }));

  return (
    <>
      <Select
        key="select"
        isMinWidth
        value={segment.operator}
        onChange={(e) => {
          void updateSegmentFilterFromEvent('operator', filterIndex, e);
        }}
      >
        <option value={AnyValueTypes.ANY}>{MailPoet.I18n.t('anyOf')}</option>
        <option value={AnyValueTypes.ALL}>{MailPoet.I18n.t('allOf')}</option>
        <option value={AnyValueTypes.NONE}>{MailPoet.I18n.t('noneOf')}</option>
      </Select>
      <ReactSelect
        dimension="small"
        isMulti
        placeholder={MailPoet.I18n.t('searchLists')}
        options={options}
        value={filter((option) => {
          if (!segment.segments) return undefined;
          const segmentId = option.value;
          return segment.segments.indexOf(segmentId) !== -1;
        }, options)}
        onChange={(selectOptions: SelectOption[]): void => {
          void updateSegmentFilter(
            { segments: map(parseInt(10), map('value', selectOptions)) },
            filterIndex,
          );
        }}
      />
    </>
  );
}
