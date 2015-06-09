/**
 * revisr-setup.js
 *
 * Processes JS on the Revisr Setup page.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

/**
 * Initializes select2 selects.
 */
jQuery('#revisr-tracking-options').select2({
	theme: 'classic',
	width: '100%'
});
jQuery('#revisr-plugin-or-theme').select2({
	theme: 'classic',
	placeholder: revisr_setup_vars.plugin_or_theme_placeholder,
	width: '100%'
});

jQuery('#revisr-tracking-options').change(function(){
	if (this.value == 'single') {
		jQuery('#revisr-plugin-or-theme-wrap').fadeIn('fast');

	} else {
		jQuery('#revisr-plugin-or-theme-wrap').fadeOut('fast');
	}
});