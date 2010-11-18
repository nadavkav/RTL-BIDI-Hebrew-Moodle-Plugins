
function selectTag(label) {
	var labelInput = document.getElementById("id_tags");
	if (!labelInput) return;
	var curVal = Trim(labelInput.value);

	if (curVal == "") {
		labelInput.value = label.innerHTML;
	} else {
		// Remove excess whitespace
		var newLabel = Trim(label.innerHTML);
		var labels = curVal.split(',');
		var found = false;
		// See if the label already is in the text box
		for (var i=0; i < labels.length; i++) {
			labels[i] = Trim(labels[i]);
			if (labels[i] == newLabel) found = true;
		}
		// If not, add it.
		if (!found) {
			labels[labels.length] = newLabel;
		}
		// Remove any whitespace-only elements from the array.
		var newLabels = new Array();
		for (var i=0; i < labels.length; i++) {
			if (labels[i] != "") {
				newLabels[newLabels.length] = labels[i];
			}
		}
		// Put it back together.
		labelInput.value = newLabels.join(", ") + ", ";
	}
}

function Trim(str) {
	if (!str) return "";
	return str.replace(/^\s+/, "").replace(/\s+$/, "");
}
