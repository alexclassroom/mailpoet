/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * WordPress private dependencies
 */
import { StylesColorPanel } from '../../../private-apis';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';
import { useEmailStyles } from '../../../hooks';
import { storeName } from '../../../store';
import { recordEvent, recordEventOnce } from '../../../events';

export function ScreenColors(): JSX.Element {
	recordEventOnce( 'styles_sidebar_screen_colors_opened' );
	const { userStyles, styles, updateStyles } = useEmailStyles();
	const theme = useSelect( ( select ) => select( storeName ).getTheme(), [] );

	const handleOnChange = ( newStyles ) => {
		updateStyles( newStyles );
		recordEvent( 'styles_sidebar_screen_colors_styles_updated' );
	};

	return (
		<>
			<ScreenHeader
				title={ __( 'Colors', 'mailpoet' ) }
				description={ __(
					'Manage palettes and the default color of different global elements.',
					'mailpoet'
				) }
			/>
			<StylesColorPanel
				value={ userStyles }
				inheritedValue={ styles }
				onChange={ handleOnChange }
				settings={ theme?.settings }
				panelId="colors"
			/>
		</>
	);
}
