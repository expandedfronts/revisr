/**
 * revisr-dashboard.js
 *
 * Processes AJAX functionality on the Revisr dashboard.
 *
 * @package 	Revisr
 * @license 	GPLv3
 * @link 		https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

/**
 * Discards uncommitted changes.
 */
function revisr_discard() {
	tb_remove();
	processing();

	jQuery.post(ajaxurl, {action: 'process_discard', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
		process_complete();
	});
}

/**
 * Pushes changes to a remote server.
 */
function revisr_push() {
	tb_remove();
	processing();

	jQuery.post(ajaxurl, {action: 'process_push', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
		process_complete();
	});
}

/**
 * Pulls changes from a remote server.
 */
function revisr_pull() {
	tb_remove();
	processing();

	jQuery.post(ajaxurl, {action: 'process_pull', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
		process_complete();
	});
}

/**
 * Refresh the Revisr status bar after any transients have expired.
 */
function delayed_refresh() {
	setTimeout( function() {
		update_alert();
		revisr_list.update();
	}, 4000);
}

/**
 * Display the latest logged alert.
 */
 function update_alert() {
		jQuery.ajax({
		type : 'POST',
		url : ajaxurl,
		data : {action: 'render_alert'},
		success : function(response){
			if ( 0 == response ) {
				document.getElementById('revisr-processing-request').innerHTML = '<p>' + revisr_dashboard_vars.login_prompt + '</p>';
			} else {
				jQuery('#revisr-loading-alert').hide();
				jQuery('#revisr-processing-request').hide();
				document.getElementById('revisr-alert-container').innerHTML = response;
			}
		}
	});
 }

/**
 * Update the AJAX button counts.
 */
function update_counts() {
	jQuery.post(ajaxurl, {action: 'ajax_button_count', data: 'unpulled'}, function(response) {
		if ( 0 == response ) {
			response = '';
		}
		document.getElementById('revisr-unpulled').innerHTML = response;
	});
	jQuery.post(ajaxurl, {action: 'ajax_button_count',data: 'unpushed'}, function(response) {
		if ( 0 == response ) {
			response = '';
		}
		document.getElementById('revisr-unpushed').innerHTML = response;
	});
}

/**
 * Display the loading message.
 */
function processing() {
	jQuery('.revisr-alert').hide();
	jQuery('#revisr-loader').show();
	jQuery('#revisr-processing-request').show();
}

/**
 * Run when an AJAX request from the dashboard has been completed.
 */
function process_complete() {
	jQuery('#revisr-loader').hide();
	update_alert();
	revisr_list.update();
	update_counts();
	delayed_refresh();
}

/**
 * Display the branches list.
 */
jQuery('#branches-link').click(function() {
	jQuery('#tags-tab').attr('class', 'hide-if-no-js');
	jQuery('#branches-tab').attr('class', 'tabs');
	jQuery('#tags').hide();
	jQuery('#branches').show();
});

/**
 * Display the tags list.
 */
jQuery('#tags-link').click(function() {
	jQuery('#branches-tab').attr('class', 'hide-if-no-js');
	jQuery('#tags-tab').attr('class', 'tabs');
	jQuery('#branches').hide();
	jQuery('#tags').show();
});

/**
 * Redirects to the new commit screen.
 */
jQuery('#revisr-commit-btn').click(function() {
	window.location = 'admin.php?page=revisr_new_commit';
});

/**
 * Runs when the 'Backup Database' button is clicked.
 */
jQuery('#revisr-backup-btn').click(function() {
	processing();
	jQuery.post(ajaxurl, {action: 'backup_db', source: 'ajax_button', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
		process_complete();
	});
});


var revisr_list = {
	/**
	 * Register our triggers
	 *
	 * We want to capture clicks on specific links, but also value change in
	 * the pagination input field. The links contain all the information we
	 * need concerning the wanted page number or ordering, so we'll just
	 * parse the URL to extract these variables.
	 *
	 * The page number input is trickier: it has no URL so we have to find a
	 * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
	 * to recover the ordering variables, and the default paged input added
	 * automatically by WordPress.
	 */
	init: function() {
		// This will have its utility when dealing with the page number input
		var timer;
		var delay = 500;
		// Pagination links, sortable link
		jQuery('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
			// We don't want to actually follow these links
			e.preventDefault();
			// Simple way: use the URL to extract our needed variables
			var query = this.search.substring( 1 );

			var data = {
				paged: revisr_list.__query( query, 'paged' ) || '1',
				order: revisr_list.__query( query, 'order' ) || 'desc',
				orderby: revisr_list.__query( query, 'orderby' ) || 'time',
				revisr_event: revisr_list.__query( query, 'revisr_event' ) || 'all',
				revisr_user: revisr_list.__query( query, 'revisr_user' ) || 'all',
				revisr_time: revisr_list.__query( query, 'revisr_time' ) || 'all'
			};
			revisr_list.update( data );
		});
		// Page number input
		jQuery('input[name=paged]').on('keyup', function(e) {
			// If user hit enter, we don't want to submit the form
			// We don't preventDefault() for all keys because it would
			// also prevent to get the page number!
			if ( 13 == e.which )
				e.preventDefault();
			// This time we fetch the variables in inputs
			var data = {
				paged: parseInt( jQuery('input[name=paged]').val() ) || '1',
				order: jQuery('input[name=order]').val() || 'asc',
				orderby: jQuery('input[name=orderby]').val() || 'title',
				revisr_event : jQuery('input[name=revisr_event]').val() || 'all',
				revisr_user : jQuery('input[name=revisr_user]').val() || 'all',
				revisr_time : jQuery('input[name=revisr_time]').val() || 'all'
			};
			// Now the timer comes to use: we wait half a second after
			// the user stopped typing to actually send the call. If
			// we don't, the keyup event will trigger instantly and
			// thus may cause duplicate calls before sending the intended
			// value
			window.clearTimeout( timer );
			timer = window.setTimeout(function() {
				revisr_list.update( data );
			}, delay);
		});
	},
	/**
	 * AJAX call
	 *
	 * Send the call and replace table parts with updated version!
	 *
	 * @param	object	data The data to pass through AJAX
	 */
	update: function( data ) {
		jQuery.ajax({
			// /wp-admin/admin-ajax.php
			url: ajaxurl,
			// Add action and nonce to our collected data
			data: jQuery.extend(
				{
					revisr_list_nonce: jQuery('#revisr_list_nonce').val(),
					action: 'revisr_get_custom_list',
				},
				data
			),
			// Handle the successful result
			success: function( response ) {
				// WP_List_Table::ajax_response() returns json
				var response = jQuery.parseJSON( response );
				// Add the requested rows
				if ( response.rows.length )
					jQuery('#the-list').html( response.rows );
				// Update column headers for sorting
				if ( response.column_headers.length )
					jQuery('thead tr, tfoot tr').html( response.column_headers );
				// Update pagination for navigation
				if ( response.pagination.bottom.length )
					jQuery('.tablenav.top .tablenav-pages').html( jQuery(response.pagination.top).html() );
				if ( response.pagination.top.length )
					jQuery('.tablenav.bottom .tablenav-pages').html( jQuery(response.pagination.bottom).html() );
				// Init back our event handlers
				revisr_list.init();
			}
		});
	},
	/**
	 * Filter the URL Query to extract variables
	 *
	 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
	 *
	 * @param    string    query The URL query part containing the variables
	 * @param    string    variable Name of the variable we want to get
	 *
	 * @return   string|boolean The variable value if available, false else.
	 */
	__query: function( query, variable ) {
		var vars = query.split("&");
		for ( var i = 0; i <vars.length; i++ ) {
			var pair = vars[ i ].split("=");
			if ( pair[0] == variable )
				return pair[1];
		}
		return false;
	},
}

/**
 * Initialize the dashboard.
 */
revisr_list.init();
update_alert();
update_counts();
jQuery('#revisr-loader').hide();

/**
 * Initializes select2 selects.
 */
 jQuery('#revisr-events-select').select2({
 	theme: "classic"
 });
 jQuery('#revisr-author-select').select2({
 	theme: "classic"
 });
 jQuery('#revisr-time-select').select2({
 	theme: "classic"
 });

