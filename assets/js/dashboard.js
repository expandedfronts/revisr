var alert_div 			= document.getElementById("revisr_alert");
var alert_wrapper_div 	= document.getElementById("revisr-alert-container");
var activity_div 		= document.getElementById("revisr_activity");
var unpulled_div 		= document.getElementById("unpulled");
var unpushed_div 		= document.getElementById("unpushed");
var alert_request 		= {action: 'render_alert'};
var activity_request 	= {action: 'recent_activity'};

function delayed_refresh() {
	setTimeout(function(){
		update_alert();
	}, 4000 );
}
function update_alert() {
	jQuery.ajax({
		type : 'POST',
		url : ajaxurl,
		data : alert_request,
		success : function(response){ 
			jQuery("#revisr-loading-alert").hide();
			jQuery("#revisr-processing-request").hide();
			alert_wrapper_div.innerHTML = response; 
		}
	});
}
function update_recent_activity() {
	jQuery.ajax({
		type : 'POST',
		url : ajaxurl,
		data : activity_request,
		success : function(response){
			activity_div.innerHTML = response;
		}
	});
}
function update_counts() {
	var unpulled_request = {action: 'ajax_button_count',data: 'unpulled'};
	jQuery.post(ajaxurl, unpulled_request, function(response) {
		unpulled_div.innerHTML = response;
	});
	var unpushed_request = {action: 'ajax_button_count',data: 'unpushed'};
	jQuery.post(ajaxurl, unpushed_request, function(response) {
		unpushed_div.innerHTML = response;
	});
}
function processing() {
	jQuery('.revisr-alert').hide();
	jQuery("#loader").show();
	jQuery("#revisr-processing-request").show();
}
function process_complete() {
	jQuery("#loader").hide();
	update_alert();
	update_recent_activity();
	update_counts();
	delayed_refresh();
}
update_alert();
update_recent_activity();
update_counts();
jQuery('#loader').hide();

jQuery(document).ready(function() {
	jQuery("#commit-btn").click(function() { 
		window.location = "post-new.php?post_type=revisr_commits";
	});
	jQuery("#discard-btn").click(function() { 
	    if (confirm(dashboard_vars.discard_msg) == true) {
	    	processing();
	   		var discard_request = {action: 'discard',security: dashboard_vars.ajax_nonce};
			jQuery.post(ajaxurl, discard_request, function(response) {
				process_complete();
			});
	    } 
	});
	jQuery("#backup-btn").click(function() {
		processing();
		var data = {action: 'backup_db',source: 'ajax_button'}
		jQuery.post(ajaxurl, data, function(response) {
			process_complete();
		});
	});
	jQuery("#push-btn").click(function() { 
	    if (confirm(dashboard_vars.push_msg) == true) {
	   		processing();
	   		var push_request = {action: 'process_push'};
			jQuery.post(ajaxurl, push_request, function(response) {
				process_complete();
			});
	    } 
	});
	jQuery("#pull-btn").click(function() { 
	    if (confirm(dashboard_vars.pull_msg) == true) {
	    	processing();
	   		var pull_request = {action: 'process_pull',from_dash: 'true',security: dashboard_vars.ajax_nonce};
			jQuery.post(ajaxurl, pull_request, function(response) {
				process_complete();
			});
	    } 
	});
	jQuery("#branches-link").click(function() {
		jQuery("#tags-tab").attr('class', 'hide-if-no-js');
		jQuery("#branches-tab").attr('class', 'tabs');
		jQuery("#tags").hide();
		jQuery("#branches").show();
	});
	jQuery("#tags-link").click(function() {
		jQuery("#branches-tab").attr('class', 'hide-if-no-js');
		jQuery("#tags-tab").attr('class', 'tabs');
		jQuery("#branches").hide();
		jQuery("#tags").show();
	});
}); // end ready 