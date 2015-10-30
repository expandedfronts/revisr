function stage_file() {
    jQuery('option:selected', ('#unstaged')).each(function () {
        jQuery(this).remove().appendTo('#staged');
    });
}

function unstage_file() {
    jQuery('option:selected', ('#staged')).each(function () {
        jQuery(this).remove().appendTo('#unstaged');
    });
}

function stage_all() {
    jQuery('option', ('#unstaged')).each(function () {
        jQuery(this).remove().appendTo('#staged');
    });
}

function unstage_all() {
    jQuery('option', ('#staged')).each(function () {
        jQuery(this).remove().appendTo('#unstaged');
    });
}

function commit_files() {
    jQuery('option', ('#staged')).each(function() {
        jQuery(this).attr('selected', 'selected');
    });
    jQuery('option', ('#unstaged')).each(function() {
        jQuery(this).attr('selected', 'selected');
    });
    jQuery('#commit').val('Committing...');
    jQuery('#revisr-spinner').css('visibility', 'visible');
}

jQuery(document).ready(function($) {

    // Load any pending/untracked files via AJAX in case there are a lot of results.
    if ( 'admin_page_revisr_new_commit' === adminpage ) {

        var data = {
            action: 'pending_files',
            security: staging_vars.ajax_nonce
        };

        $.post(ajaxurl, data, function(response) {
            document.getElementById('pending_files_result').innerHTML = response;
        });

    }

});

jQuery(document).on('dblclick', '.pending', function (event) {
    var pending = event.target.value,
        status  = pending.substr(0, 3);
    if ( status.indexOf( 'M' ) !== -1 ) {
        var file = ajaxurl + '?action=process_view_diff&security=' + staging_vars.ajax_nonce + '&file=' + pending.substr(3);
        tb_show(staging_vars.view_diff, file);
    }
});

jQuery(document).on('dblclick', '.committed', function (event) {
    var pending = event.target.value,
        commit  = document.getElementById('commit_hash').value,
        status  = pending.substr(0, 2),
        file    = pending.substr(2);
    if ( status.indexOf( 'M' ) !== -1 ) {
        var file = ajaxurl + '?action=process_view_diff&security=' + staging_vars.ajax_nonce + '&file=' + file.trim() + '&commit=' + commit;
        tb_show('View Diff', file);
    }
});
