jQuery(document).ready(
function() {
	
	jQuery('#userfile').change(function(event) {
		jQuery('#userfilename').val(jQuery('#userfile').val());
	});
});