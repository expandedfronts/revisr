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

	if ( jQuery("#db-tracking-select").val() == 'custom' ) {
		jQuery("#advanced-db-tracking").show();
	} else {
		jQuery('#advanced-db-tracking').hide();		
	}

	jQuery( '#post-hook' ).hide();

	if ( jQuery("#auto_pull").prop('checked') === true ) {
		jQuery( '#post-hook').show();
	}


	jQuery( '#auto_pull' ).change( function() {
  		if ( this.checked ) {
    		jQuery( '#post-hook' ).fadeIn( 'fast' );
  		} else {
    		jQuery( '#post-hook' ).fadeOut( 'fast' );
  		}                   
	});
	jQuery('#db-tracking-select').change(function(){
		if (this.value == 'custom') {
			jQuery('#advanced-db-tracking').fadeIn('fast');
		} else {
			jQuery('#advanced-db-tracking').fadeOut('fast');
		}
	});