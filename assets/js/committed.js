var page = 1;

function next()
{
	var next_page = ++page;

	var data = {
		action: 'committed_files',
		pagenum: next_page,
		id: committed_vars.post_id,
		security: committed_vars.ajax_nonce
	};	
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('committed_files_result').innerHTML = response;
	});		
}

function prev()
{
	var prev_page = --page;
	var data = {
		action: 'committed_files',
		pagenum: prev_page,
		id: committed_vars.post_id,
		security: committed_vars.ajax_nonce
	};
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('committed_files_result').innerHTML = response;
	});				
}

jQuery(document).ready(function($) {

	var data = {
		action: 'committed_files',
		page: 1,
		id: committed_vars.post_id,
		security: committed_vars.ajax_nonce
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		document.getElementById('committed_files_result').innerHTML = response;
	});
});