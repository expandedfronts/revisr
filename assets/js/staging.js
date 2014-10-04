function stage_file()
{
    jQuery("#unstaged > option:selected").each(function () {
        jQuery(this).remove().appendTo("#staged");
    });
    var num_staged = jQuery("#staged option").length;
    jQuery('#count_staged').innerHTML = num_staged;
}

function unstage_file()
{
    jQuery("#staged > option:selected").each(function () {
        jQuery(this).remove().appendTo("#unstaged");
    });
    var num_staged = jQuery("#staged option").length;
    jQuery('#count_staged').innerHTML = num_staged;
}

function stage_all()
{
    jQuery("#unstaged > option").each(function () {
        jQuery(this).remove().appendTo("#staged");
    });
    var num_staged = jQuery("#staged option").length;
    jQuery('#count_staged').innerHTML = num_staged;
}

function unstage_all()
{
    jQuery("#staged > option").each(function () {
        jQuery(this).remove().appendTo("#unstaged");
    });
    var num_staged = jQuery("#staged option").length;
    jQuery('#count_staged').innerHTML = num_staged;
}

jQuery(document).ready(function($) {

    $("#publish").val("Commit Files");

	var data = {
		action: 'pending_files',
        security: pending_vars.ajax_nonce
	};

	$.post(ajaxurl, data, function(response) {
		document.getElementById('pending_files_result').innerHTML = response;
	});

	var url = document.URL;
	var empty_title = url.indexOf("message=42");
    var empty_commit = url.indexOf("message=43");

	if (empty_title != "-1") {
		document.getElementById('message').innerHTML = "<div class='error'><p>Please enter a message for your commit.</p></div>";
	}
    if (empty_commit != "-1") {
        document.getElementById('message').innerHTML = "<div class='error'><p>Nothing was added to the commit. Please use the section below to add files to be included in the commit.</p></div>";
    }

    $("#publish").click(function() {  
      $("#staged option").each(function() {
        $(this).attr("selected", "selected");
      });
    });

});

jQuery(document).on("dblclick", ".pending", function () {
    var pending = event.target.value;
    var status = pending.substr(0, 3);
    if (status === " M ") {
        var file = ajaxurl + "?action=view_diff&file=" + pending.substr(3);
        tb_show("View Diff", file);
    }
});
