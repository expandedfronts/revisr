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