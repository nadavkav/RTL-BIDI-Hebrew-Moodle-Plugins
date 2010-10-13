jQuery(document).ready(
function() {
	
	jQuery('#removeSubmit').removeAttr("disabled");
	jQuery('#addSubmit').removeAttr("disabled");
	
	jQuery('#removeSubmit').click(function(event) {
		jQuery('#removeSubmit').attr("disabled", "true");
		jQuery('#addSubmit').attr("disabled", "true");
		$('input[name=submitvalue]').val("remove");
		$('#participantForm').submit();
	});
	
	jQuery('#addSubmit').click(function(event) {
		jQuery('#removeSubmit').attr("disabled", "true");
		jQuery('#addSubmit').attr("disabled", "true");
		$('input[name=submitvalue]').val("add");
		$('#participantForm').submit();
	});	
});