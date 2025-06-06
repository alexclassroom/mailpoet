import React from 'react';
import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';

type Props = React.ComponentProps<typeof Button>;

export function BackButton(props: Props): JSX.Element {
  return (
    <div className="mailpoet-back-button">
      <Button size="small" icon={chevronLeft} {...props} />
    </div>
  );
}
