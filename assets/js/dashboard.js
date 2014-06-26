jQuery(document).ready(function() {

	var recent_data = {
		action: 'recent_activity'
	};

	jQuery.post(ajaxurl, recent_data, function(response) {
		document.getElementById("revisr_activity").innerHTML = response;
	});	

	jQuery('#loader').hide();

	jQuery("#commit-btn").click(function() { 
		window.location = "post-new.php?post_type=revisr_commits";
	});

	jQuery("#discard-btn").click(function() { 

	    if (confirm("Are you sure you want to discard your uncommitted changes?") == true) {
	    	jQuery('#loader').show();
	   		var data = {
				action: 'discard'
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
				action: 'pull'
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
	        console.log("Pull cancelled.");
	    }
	});

}); // end ready 