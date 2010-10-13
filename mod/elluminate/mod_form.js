jQuery(document).ready(
function() {
	
	$("#id_sessionname_state").hide();
	$("label[for='id_sessionname_state']").hide();
	$("#id_groupingid option[value='0']").remove();	
	
	if(jQuery("#id_sessiontype").val() == '0') {  //Course		
		$("#id_groupingid").attr("disabled", "disabled");
		$("#id_customname").attr("disabled", "disabled");
		$("#id_groupmode").attr("disabled", "disabled");
	} else if (jQuery("#id_sessiontype").val() == '1') { //Private
		$("#id_groupingid").attr("disabled", "disabled");
		$("#id_customname").attr("disabled", "disabled");
		$("#id_groupmode").attr("disabled", "disabled");
	} else if (jQuery("#id_sessiontype").val() == '2') { //Group
		$("#id_groupingid").attr("disabled", "disabled");
		$("#id_customname").removeAttr('disabled');		
		$("#id_groupmode").removeAttr('disabled');
		generate_group_box();
	} else if (jQuery("#id_sessiontype").val() == '3') { //Grouping
		$("#id_groupingid").removeAttr('disabled');
		$("#id_customname").removeAttr('disabled');		
		$("#id_groupmode").removeAttr('disabled');
		generate_group_box();
	}
	
	// id_groupmode
	// id_customname
	// id_groupingid
	//
	jQuery('#id_sessiontype').change(function(event) {		
		if($(this).val() == '0') {  //Course
			$("#id_groupingid").attr("disabled", "disabled");
			$("#id_customname").attr("disabled", "disabled");
			$("#id_groupmode").attr("disabled", "disabled");
		} else if ($(this).val() == '1') { //Private
			$("#id_groupingid").attr("disabled", "disabled");
			$("#id_customname").attr("disabled", "disabled");
			$("#id_groupmode").attr("disabled", "disabled");
		} else if ($(this).val() == '2') { //Group
			$("#id_groupingid").attr("disabled", "disabled");
			$("#id_customname").removeAttr('disabled');		
			$("#id_groupmode").removeAttr('disabled');
			generate_group_box();
		}  else if ($(this).val() == '3') { //Grouping
			$("#id_groupingid").removeAttr('disabled');
			$("#id_customname").removeAttr('disabled');		
			$("#id_groupmode").removeAttr('disabled');
			generate_group_box();		
		}
	});
	
	jQuery('#id_customname').change(function(event) {
		if($(this).val() == '0') {  //Course
			jQuery('#id_sessionname').val($('#id_sessionname_state').val());
		} else if ($(this).val() == '1') { //Private
			if($('#id_sessionname').val().length > 0) {
				jQuery('#id_sessionname_state').val($('#id_sessionname').val());
			}
			jQuery('#id_sessionname').val('');			
		} else if ($(this).val() == '2') { //Group
			if($('#id_sessionname').val().length > 0) {
				jQuery('#id_sessionname_state').val($('#id_sessionname').val());
			}
			jQuery('#id_sessionname').val('');			
		}
	});
	
});

function generate_group_box() {
	$("#id_groupmode").find('option').remove().end();	
	var nogroups = "<option value='0'>No Groups</option>"
	var seperategroups = "<option value='1'>Separate Groups</option>"
	var visiblegroups = "<option value='2'>Visible Groups</option>"
		
	if(jQuery('#id_sessiontype').val() == '3') {
		$("#id_groupmode").append(nogroups);
	}
	
	$("#id_groupmode").append(seperategroups);
	$("#id_groupmode").append(visiblegroups);
	
	$("#id_groupmode").val($('input[name=edit_groupmode]').val());
}
