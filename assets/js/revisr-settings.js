/**
 * revisr-settings.js
 *
 * JS used on the Revisr Settings pages.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

remote_field = jQuery('#remote_url');
result_span = jQuery('#verify-remote');
db_driver_select = jQuery('#db-driver-select');
db_tracking_select = jQuery('#db-tracking-select');
advanced_db_tracking = jQuery('#advanced-db-tracking');
post_hook = jQuery('#post-hook');
auto_pull = jQuery('#auto_pull');

remote_field.blur(function() {
	data = {
		action: 'verify_remote',
		remote: remote_field.val()
	}

	jQuery.post(ajaxurl, data, function(response) {
		if (response.indexOf('Success') !== -1) {
			result_span.removeClass();
			result_span.addClass('verify-remote-success');
		}
		else {
			result_span.removeClass();
			result_span.addClass('verify-remote-error');
		}
		result_span.html( response );
	});

});

if ( db_driver_select.val() == 'mysql' ) {
	db_driver_select.closest('tr').next('tr').show();
} else {
	db_driver_select.closest('tr').next('tr').hide();
}

if ( db_tracking_select.val() == 'custom' ) {
	advanced_db_tracking.show();
} else {
	advanced_db_tracking.hide();
}

post_hook.hide();

if ( auto_pull.prop('checked') === true ) {
	post_hook.show();
}

auto_pull.change( function() {
	if ( this.checked ) {
		post_hook.fadeIn('fast');
	} else {
		post_hook.fadeOut('fast');
	}
});

db_tracking_select.change( function() {
	if ( this.value == 'custom' ) {
		advanced_db_tracking.fadeIn('fast');
	} else {
		advanced_db_tracking.fadeOut('fast');
	}
});

db_driver_select.change( function() {
	if ( this.value == 'mysql' ) {
		jQuery(this).closest('tr').next('tr').fadeIn('fast');
	} else {
		jQuery(this).closest('tr').next('tr').fadeOut('fast');
	}
});
