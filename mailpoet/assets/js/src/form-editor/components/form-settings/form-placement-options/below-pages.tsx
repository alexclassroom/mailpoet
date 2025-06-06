import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { BelowPageIcon } from './icons/below-pages-icon';
import { FormPlacementOption } from './form-placement-option';
import { storeName } from '../../../store';

export function BelowPages(): JSX.Element {
  const formSettings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );

  const { showPlacementSettings } = useDispatch(storeName);

  return (
    <FormPlacementOption
      active={formSettings.formPlacement.belowPosts.enabled}
      label={__('Below pages', 'mailpoet')}
      icon={BelowPageIcon}
      onClick={(): void => {
        void showPlacementSettings('below_posts');
      }}
      canBeActive
    />
  );
}
