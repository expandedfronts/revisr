var page = 1;

function next()
{
	var next_page = ++page;

	var data = {
		action: 'pending_files',
		pagenum: next_page
	};
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('pending_files_result').innerHTML = response;
	});		
}

function prev()
{
	var prev_page = --page;
	var data = {
		action: 'pending_files',
		pagenum: prev_page
	};
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('pending_files_result').innerHTML = response;
	});				
}

jQuery(document).ready(function($) {

	var data = {
		action: 'pending_files',
		pagenum: page
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		document.getElementById('pending_files_result').innerHTML = response;
	});

	var url = document.URL;
	var index = url.indexOf("message=42");

	if (index != "-1") {
		document.getElementById('message').innerHTML = "<div class='error'><p>Please enter a message for your commit.</p></div>";
	}

});