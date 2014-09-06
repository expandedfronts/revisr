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
    var status = pending.substr(0, 3);
    if (status === " M ") {
        var file = ajaxurl + "?action=view_diff&file=" + pending.substr(3) + "&commit=" + commit;
        tb_show("View Diff", file);
    }
});