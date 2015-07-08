function stage_file() {
    jQuery('option:selected', ('#unstaged')).each(function () {
        jQuery(this).remove().appendTo('#staged');
    });
    jQuery('#count_staged').innerHTML = jQuery('option', ('#staged')).length;
}

function unstage_file() {
    jQuery('option:selected', ('#staged')).each(function () {
        jQuery(this).remove().appendTo('#unstaged');
    });
    jQuery('#count_staged').innerHTML = jQuery('option', ('#staged')).length;
}

function stage_all() {
    jQuery('option', ('#unstaged')).each(function () {
        jQuery(this).remove().appendTo('#staged');
    });
    jQuery('#count_staged').innerHTML = jQuery('option', ('#staged')).length;
}

function unstage_all() {
    jQuery('option', ('#staged')).each(function () {
        jQuery(this).remove().appendTo('#unstaged');
    });
    jQuery('#count_staged').innerHTML = jQuery('option', ('#staged')).length;
}

function commit_files() {
    jQuery('option', ('#staged')).each(function () {
        jQuery(this).attr('selected', 'selected');
    });
    jQuery('#commit').val('Committing...');
    jQuery('.spinner').show();
}

jQuery(document).ready(function($) {

    var data = {
        action: 'pending_files',
        security: pending_vars.ajax_nonce
        },
        url = document.URL;

    $.post(ajaxurl, data, function(response) {
        document.getElementById('pending_files_result').innerHTML = response;
    });

    // empty_title
    if (url.indexOf('message=42') != '-1') {
        document.getElementById('message').innerHTML = '<div class='error'><p>' + pending_vars.empty_title_msg + '</p></div>';
    }
    // empty_commit
    if (url.indexOf('message=43') != '-1') {
        document.getElementById('message').innerHTML = '<div class='error'><p>' + pending_vars.empty_commit_msg + '</p></div>';
    }
    // error_commit
    if (url.indexOf('message=44') != '-1') {
        document.getElementById('message').innerHTML = '<div class='error'><p>' + pending_vars.error_commit_msg + '</p></div>';
    }

});

jQuery(document).on('dblclick', '.pending', function (event) {
    var pending = event.target.value,
        status  = pending.substr(0, 3);
    if ( status === ' M ' ) {
        var file = ajaxurl + '?action=view_diff&file=' + pending.substr(3);
        tb_show(pending_vars.view_diff, file);
    }
});

jQuery(document).on('dblclick', '.committed', function (event) {
    var pending = event.target.value,
        commit  = document.getElementById('commit_hash').value,
        status  = pending.substr(0, 2),
        file    = pending.substr(2);
    if (status.indexOf('M') !== -1) {
        var file = ajaxurl + '?action=view_diff&file=' + file.trim() + '&commit=' + commit;
        tb_show('View Diff', file);
    }
});
