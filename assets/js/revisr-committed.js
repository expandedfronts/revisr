jQuery(document).on("dblclick", ".committed", function (event) {
    var pending = event.target.value;
    var commit 	= document.getElementById('commit_hash').value;
    var status 	= pending.substr(0, 2);
    var file 	= pending.substr(2);
    if (status.indexOf('M') !== -1) {
        var file = ajaxurl + "?action=view_diff&file=" + file.trim() + "&commit=" + commit;
        tb_show("View Diff", file);
    }
});