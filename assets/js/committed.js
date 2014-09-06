jQuery(document).ready(function($) {

	var data = {
		action: 'committed_files',
		id: committed_vars.post_id,
		security: committed_vars.ajax_nonce
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		document.getElementById('committed_files_result').innerHTML = response;
	});
});

jQuery(document).on("dblclick", ".committed", function () {
    var pending = event.target.value;
    var commit = document.getElementById('commit_hash').value;
    var status = pending.substr(0, 2);
    var file = pending.substr(2);
    if (status.indexOf('M') !== -1) {
        var file = ajaxurl + "?action=view_diff&file=" + file.trim() + "&commit=" + commit;
        tb_show("View Diff", file);
    }
});