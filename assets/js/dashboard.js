jQuery(document).ready(function() {

	var recent_data = {
		action: 'recent_activity'
	};

	jQuery.post(ajaxurl, recent_data, function(response) {
		document.getElementById("revisr_activity").innerHTML = response;
	});	

	var unpushed = {
		action: 'count_unpushed',
		should_exit: 'true'
	};

	jQuery.post(ajaxurl, unpushed, function(response) {
		document.getElementById("unpushed").innerHTML = response;
	});

	var unpulled = {
		action: 'count_unpulled',
		should_exit: 'true'
	};

	jQuery.post(ajaxurl, unpulled, function(response) {
		document.getElementById("unpulled").innerHTML = response;
	});

	jQuery('#loader').hide();

	jQuery("#commit-btn").click(function() { 
		window.location = "post-new.php?post_type=revisr_commits";
	});

	jQuery("#discard-btn").click(function() { 

	    if (confirm("Are you sure you want to discard your uncommitted changes?") == true) {
	    	jQuery('#loader').show();
	   		var data = {
				action: 'discard',
				security: dashboard_vars.ajax_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				var error_div = document.getElementById("revisr_alert");
				if (response.indexOf('error') !== -1) {
					error_div.className = "error";
				}
				else {
					error_div.className = "updated";
				}
				error_div.innerHTML = response;
				jQuery('#loader').hide();
				jQuery.post(ajaxurl, recent_data, function(response) {
					document.getElementById("revisr_activity").innerHTML = response;
				});
			});

	    } 
	    else {
	        console.log("Revert cancelled.");
	    }
	});

	jQuery("#backup-btn").click(function() {
		jQuery("#loader").show();
		var data = {
			action: 'backup_db',
			source: 'ajax_button'
		}
		jQuery.post(ajaxurl, data, function(response) {
			var error_div = document.getElementById("revisr_alert");
			if (response.indexOf('error') !== -1) {
				error_div.className = "error";
			}
			else {
				error_div.className = "updated";
			}
			error_div.innerHTML = response;
			jQuery("#loader").hide();
			jQuery.post(ajaxurl, recent_data, function(response) {
				document.getElementById("revisr_activity").innerHTML = response;
			});
		});
	});

	jQuery("#push-btn").click(function() { 
	 
	    if (confirm("Are you sure you want to discard your uncommitted changes and push to the remote?") == true) {
	   		jQuery('#loader').show();
	   		var data = {
				action: 'push'
			};
			jQuery.post(ajaxurl, data, function(response) {
				var error_div = document.getElementById("revisr_alert");
				if (response.indexOf('error') !== -1) {
					error_div.className = "error";
				}
				else {
					error_div.className = "updated";
				}
				error_div.innerHTML = response;
				jQuery('#loader').hide();
				jQuery.post(ajaxurl, recent_data, function(response) {
					document.getElementById("revisr_activity").innerHTML = response;
						jQuery.post(ajaxurl, unpushed, function(response) {
							document.getElementById("unpushed").innerHTML = response;
						});
				});	
			});
	    } 
	    else {
	        console.log("Push cancelled.");
	    }
	});

	jQuery("#pull-btn").click(function() { 

	    if (confirm("Are you sure you want to discard your uncommitted changes and pull from the remote?") == true) {
	    	jQuery('#loader').show();
	   		var data = {
				action: 'pull',
				from_dash: 'true',
				security: dashboard_vars.ajax_nonce
			};
			
			jQuery.post(ajaxurl, data, function(response) {
				var error_div = document.getElementById("revisr_alert");
				if (response.indexOf('revisr_error') !== -1) {
					error_div.className = "error";
				}
				else {
					error_div.className = "updated";
				}
				error_div.innerHTML = response;
				jQuery('#loader').hide();
				jQuery.post(ajaxurl, recent_data, function(response) {
					document.getElementById("revisr_activity").innerHTML = response;
						jQuery.post(ajaxurl, unpulled, function(response) {
							document.getElementById("unpulled").innerHTML = response;
						});
				});
			});
	    } 
	    else {
	        console.log("Pull cancelled.");
	    }
	});

}); // end ready 