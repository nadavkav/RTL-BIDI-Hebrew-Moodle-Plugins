/**
 * Javascript function for the coordinates question type
 *    
 * @copyright &copy; 2010 Hon Wai, Lau
 * @author Hon Wai, Lau <lau65536@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 */

function coordinates_submit(submit_location_id, submit_button_name, subans_track_id, subans_num, qid) {
    var insert_location = document.getElementById(submit_location_id);
    insert_location.innerHTML = "<input name='" + submit_button_name + "' value=''>";
    var subans_tracking = document.getElementById(subans_track_id);
    subans_tracking.value = subans_num;
    var responseform = document.getElementById('responseform');
    responseform.action = responseform.action + '#q' + qid;
    responseform.submit();
}

function coordinates_form_correctness(checked) {
    var err_names = new Array('Relative error', 'Absolute error');
    var id = 0;
    while (true) {
        var nid = 'correctness[' + id + ']';
        var n = document.getElementsByName(nid)[0];
        if (n == null)  break;
        var bid = 'correctness[' + id + ']_buttons';
        var b = document.getElementById(bid);
        if (b == null) {
            var tmp = document.createElement('div');
            tmp.id = bid;
            b = n.parentNode.appendChild(tmp);
        }
        var use_raw_input = checked;
        if (!use_raw_input) {
            var res = /^\s*({_relerr}|{_err})\s*(<|==)\s*([-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?)\s*$/.exec(n.value);
            if (res == null) {
                if (n.value.replace(/^\s+|\s+$/g,"").length == 0) {
                    res = ['','{_relerr}','<','0.01'];
                    n.value = '{_relerr} < 0.01';
                }
            }
            if (res == null)
                use_raw_input = true;
            else {
                var s = '<select id="'+bid+'_type" onchange="coordinates_form_merge('+id+')">';
                s += '<option value="{_relerr}"' + (res[1] == '{_relerr}' ? ' selected="selected"' : '') + '>'+err_names[0]+'</option>';
                s += '<option value="{_err}"' + (res[1] == '{_err}'    ? ' selected="selected"' : '') + '>'+err_names[1]+'</option>';
                s += '</select><select id="'+bid+'_op" onchange="coordinates_form_merge('+id+')">';
                s += '<option value="<"' + (res[2] == '<' ? ' selected="selected"' : '') + '>&lt</option>';
                s += '<option value="=="' + (res[2] == '==' ? ' selected="selected"' : '') + '>==</option>';
                s += '</select><input id="'+bid+'_tol" value="' + res[3] + '" onchange="coordinates_form_merge('+id+')">';
                b.innerHTML = s;
            }
        }
        n.style.display = use_raw_input ? 'block' : 'none';
        b.style.display = use_raw_input ? 'none' : 'block';
        id++;
    }
}

function coordinates_form_merge(id) {
    var nid = 'correctness[' + id + ']';
    var n = document.getElementsByName(nid)[0];
    var bid = 'correctness[' + id + ']_buttons';
    var b = document.getElementById(bid);
    var error_type = document.getElementById(bid+'_type').value;
    var error_op   = document.getElementById(bid+'_op').value;
    var error_val  = document.getElementById(bid+'_tol').value;
    n.value = error_type + ' ' + error_op + ' ' + error_val;
}

function coordinates_form_display(id_prefix, checked) {
    var id = 0;
    while (true) {
        var n = document.getElementsByName(id_prefix + '[' + id + ']')[0];
        if (n == null)  break;
        n.parentNode.parentNode.style.display = checked ? 'block' : 'none';
        id++;
    }
}

function coordinates_form_simplify() {
    if (document.body.id != 'question-type-coordinates')  return;
    var d = document.getElementsByName('showoptions[vars2]')[0];
    coordinates_form_display('vars2', d.checked);
    var d = document.getElementsByName('showoptions[preunit]')[0];
    coordinates_form_display('preunit', d.checked);
    var d = document.getElementsByName('showoptions[correctnessraw]')[0];
    coordinates_form_correctness(d.checked);
    var d = document.getElementsByName('showoptions[otherrule]')[0];
    coordinates_form_display('otherrule', d.checked);
    //var d = document.getElementsByName('showoptions[subqtext]')[0];
    //coordinates_form_display('subqtext', d.checked);
    var d = document.getElementsByName('showoptions[feedback]')[0];
    coordinates_form_display('feedback', d.checked);
}

window.onload = setTimeout('setTimeout(\'coordinates_form_simplify()\',1000)',250);

