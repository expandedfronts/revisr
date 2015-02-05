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

 (function( $ ) {
	'use strict';

	/**
	 * Refresh the Revisr status bar after any transients have expired.
	 */
	function delayed_refresh() {
		setTimeout( function() {
			update_alert();
		}, 4000);
	}

	/**
	 * Display the latest logged alert.
	 */
	 function update_alert() {
 		$.ajax({
			type : 'POST',
			url : ajaxurl,
			data : {action: 'render_alert'},
			success : function(response){ 
				$('#revisr-loading-alert').hide();
				$('#revisr-processing-request').hide();
				document.getElementById('revisr-alert-container').innerHTML = response; 
			}
		});
	 }

	/**
	 * Update the Recent Activity.
	 */
	function update_recent_activity() {
  		$.ajax({
			type : 'POST',
			url : ajaxurl,
			data : {action: 'recent_activity'},
			success : function(response){
				document.getElementById('revisr_activity').innerHTML = response;
			}
		});
	}

	/**
	 * Update the AJAX button counts.
	 */
	function update_counts() {
		$.post(ajaxurl, {action: 'ajax_button_count', data: 'unpulled'}, function(response) {
			document.getElementById('#unpulled').innerHTML = response;
		});
		$.post(ajaxurl, {action: 'ajax_button_count',data: 'unpushed'}, function(response) {
			document.getElementById('#unpushed').innerHTML = response;
		});
	}

	/**
	 * Display the loading message.
	 */
	function processing() {
		$('.revisr-alert').hide();
		$('#loader').show();
		$('#revisr-processing-request').show();
	}

	/**
	 * Run when an AJAX request from the dashboard has been completed.
	 */
	function process_complete() {
		$('#loader').hide();
		update_alert();
		update_recent_activity();
		update_counts();
		delayed_refresh();
	}

	/**
	 * Display the branches list.
	 */
	$('#branches-link').click(function() {
		$('#tags-tab').attr('class', 'hide-if-no-js');
		$('#branches-tab').attr('class', 'tabs');
		$('#tags').hide();
		$('#branches').show();
	});

	/**
	 * Display the tags list.
	 */
	$('#tags-link').click(function() {
		$('#branches-tab').attr('class', 'hide-if-no-js');
		$('#tags-tab').attr('class', 'tabs');
		$('#branches').hide();
		$('#tags').show();
	});

	/**
	 * Redirects to the new commit screen.
	 */
	$('#commit-btn').click(function() { 
		window.location = 'post-new.php?post_type=revisr_commits';
	});

	/**
	 * Runs when the 'Discard Changes' button is clicked.
	 */
	$('#discard-btn').click(function() { 
	    if ( confirm( revisr_dashboard_vars.discard_msg ) == true ) {
	    	processing();
			$.post(ajaxurl, {action: 'discard', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
				process_complete();
			});
	    } 
	});
	
	/**
	 * Runs when the 'Backup Database' button is clicked.
	 */
	$('#backup-btn').click(function() {
		processing();
		$.post(ajaxurl, {action: 'backup_db', source: 'ajax_button', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
			process_complete();
		});
	});

	/**
	 * Runs when the 'Push Changes' button is clicked.
	 */
	$('#push-btn').click(function() { 
	    if ( confirm( revisr_dashboard_vars.push_msg ) == true ) {
	   		processing();
			$.post(ajaxurl, {action: 'process_push', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
				process_complete();
			});
	    } 
	});

	/**
	 * Runs when the 'Pull Changes' button is clicked.
	 */
	$('#pull-btn').click(function() { 
	    if ( confirm( revisr_dashboard_vars.pull_msg ) == true ) {
	    	processing();
			$.post(ajaxurl, {action: 'process_pull', from_dash: 'true', revisr_dashboard_nonce: revisr_dashboard_vars.ajax_nonce}, function(response) {
				process_complete();
			});
	    } 
	});

	/**
	 * Initialize the dashboard.
	 */
	update_alert();
	update_recent_activity();
	update_counts();
	$('#loader').hide();

})( jQuery );