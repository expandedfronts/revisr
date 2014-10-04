	var input_field = document.getElementById("remote_url");
	var result_span = document.getElementById("verify-remote");

	jQuery(input_field).keyup(function () {
		var input_value = document.getElementById("remote_url").value;
		var data = {
			action: 'verify_remote',
			remote: input_value

		};
		jQuery.post(ajaxurl, data, function(response) {
			if (response.indexOf('Success') !== -1) {
				result_span.className = "verify-remote-success";
			}
			else {
				result_span.className = "verify-remote-error";
			}
			result_span.innerHTML = response;
		});
	});

	jQuery(".merge-btn").click(function() {
		var target_branch = jQuery(this).attr("value");
		if (confirm("Are you sure you want to merge branch " + target_branch + " into your current branch?")) {
			return;
		} else {
			return false;
		}
	});
	jQuery(document).ready(function() {
		jQuery('#post-hook').hide();
		jQuery('#auto_pull').change(function(){
	  		if (this.checked) {
	    		jQuery('#post-hook').fadeIn('slow');
	  		} else {
	    		jQuery('#post-hook').fadeOut('slow');
	  		}                   
		});
	});