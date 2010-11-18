<?PHP  
// Uses a template to create and edit new items
// The template is an item with the name 'xxx_template'
// Any properties associated with this item are taken
// to be default props for a new item of this type
// The form will have fields for all these props for an item 
//

	require_once("../../config.php");
    require_once("lib.php");

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // bookings ID

    $item_type      = optional_param('item_type'); 
    $kombotype      = optional_param('kombotype'); 
    $komboroom      = optional_param('komboroom'); 
    $new            = optional_param('new'); 
    $newchild       = optional_param('newchild'); 
    $adoptchild     = optional_param('adoptchild'); 
    $delete         = optional_param('delete'); 
    $newid          = optional_param('newid'); 
    $orphan         = optional_param('orphan'); 
    $save           = optional_param('save'); 
	$rname          = optional_param('rname');
	$newp           = optional_param('newp');
	$newv           = optional_param('newv');
	
    // $anyitem        = optional_param('anyitem'); 

    if (!isset($anyitem)) { 
        if ($id) {
            if (! $cm = get_record("course_modules", "id", $id)) {
                error("Course Module ID was incorrect");
            }
    
            if (! $course = get_record("course", "id", $cm->course)) {
                error("Course is misconfigured");
            }
        
            if (! $bookings = get_record("bookings", "id", $cm->instance)) {
                error("Course module is incorrect");
            }

        } else {
            if (! $bookings = get_record("bookings", "id", $a)) {
                error("Course module is incorrect");
            }
            if (! $course = get_record("course", "id", $bookings->course)) {
                error("Course is misconfigured");
            }
            if (! $cm = get_coursemodule_from_instance("bookings", $bookings->id, $course->id)) {
                error("Course Module ID was incorrect");
            }
        }
    }

    require_login($course->id);


    $username = $USER->username;
    $UID = $USER->id;
    $firstname = $USER->firstname;
    $lastname = $USER->lastname;
    if ($firstname == '' or $lastname == '') exit;

    if (!isteacherinanycourse($USER->id) AND !isadmin()) { 
        print_header_simple(format_string('ItemEditor'), "",
                 "$navigation ".'ItemEditor', "", "", true);
        // print $html;
        print_footer($course);
        exit; 
    }

    /// require_once($CFG->dirroot.'/blocks/timetable/locallib.php');

    if(!isset($item_type)) $item_type = 'room';

    if (isset($kombotype)) {
        $item_type = $kombotype;
    }



    if (isadmin()) { 
        $can_edit = 1;
    } else {
        $can_edit = isteacherinanycourse($USER->id) ? 1 : 0;   // default is that teachers can edit
    }
    $roomid = NULL;

    if (isset($komboroom)) {
        $roomid = $komboroom;
        // build array of props for this item (room)
        $proplist = array();
        $sql = 'SELECT p.id,p.name,p.value FROM '.$CFG->prefix.'bookings_item_property p WHERE p.itemid='.$roomid;
        if ($p = get_records_sql($sql)) {
            foreach($p as $prop) {
                $proplist[$prop->name] = $prop->value;
            }
        }
        if (isset($proplist['edit_group'])) {
            // $can_edit = 0;   // default is no edit (that is: edit_group != teacher|student )
            if ($proplist['edit_group'] == 'teacher' and isteacherinanycourse($USER->id) ) {
                $can_edit = 1;
            } else if ($proplist['edit_group'] == 'student') {
                $can_edit = 1;
            }
        }
        if (isset($proplist['edit_list'])) {
            if (strstr($proplist['edit_list'],$username)) {
                $can_edit = 1;
            }
        }
    }

    if (isset($new)) {
        $item->name = 'ChangeMe';
        $item->type = $item_type;
        $roomid = insert_record('bookings_item', $item);
    }

    if (isset($newchild)) {
        if (!isset($childtype)) {
            $childtype = $item_type;
        }
        $item->name = 'New-'.$childtype;
        $item->type = $childtype;
        $item->parent = $roomid;
        insert_record('bookings_item', $item);
    }

    if (isset($adoptchild) and isset($childtype)) {
        $sql = 'UPDATE '.$CFG->prefix.'bookings_item SET parent='.$roomid.' WHERE id='.$adoptchild;
        execute_sql($sql,0);
    }

    if (isset($delete) and $can_edit) {
        $sql = 'SELECT id,name,type,parent
            FROM '.$CFG->prefix.'bookings_item i
            WHERE i.id = '.$roomid;
        if ($r = get_record_sql($sql)) {
            $parent = $r->parent;
        }
        $sql = 'DELETE FROM '.$CFG->prefix.'bookings_item_property WHERE itemid='.$roomid;
        execute_sql($sql,0);
        $sql = 'UPDATE '.$CFG->prefix.'bookings_item SET parent=0 WHERE parent ='.$roomid;
        execute_sql($sql,0);
        $sql = 'DELETE FROM '.$CFG->prefix.'bookings_item WHERE id='.$roomid;
        execute_sql($sql,0);
        unset($roomid);
        if ($parent) {
            $newid = $parent;
        }
    }

    if (isset($newid)) {
        $roomid = $newid;
        $sql = 'SELECT id,name,type,parent
            FROM '.$CFG->prefix.'bookings_item i
            WHERE i.id = '.$roomid;
        if ($r = get_record_sql($sql)) {
            $item_type = $r->type;
        }
    }
  
    $html = '<center><h2>'.$item_type;
    $html .= helpbutton('itemeditor', get_string('itemeditor', 'bookings'), 'bookings',true,false,false,true);
    $html .= '</h2></center>';
    $html .= '<form name=myform id=myform method=post action="itemeditor.php?id='.$id.'">';
    $html .= '<input type=hidden name="item_type" value="'.$item_type.'">';


    if (isset($orphan) and $can_edit) {
        $sql = 'UPDATE '.$CFG->prefix.'bookings_item SET parent=0 WHERE id='.$roomid;
        execute_sql($sql,0);
    }
  
    if (isset($save) and $can_edit) {

	    $sql = 'UPDATE '.$CFG->prefix.'bookings_item SET name="'.$rname.'" WHERE id='.$roomid;		
        execute_sql($sql,0);

        // adding new property
        if (isset($newp) and isset($newv) ) {
            if ($newp != '' and $newv != '') {
                $status[] = "Adding new property: $newp = $newv";
                $sql = 'INSERT INTO '.$CFG->prefix.'bookings_item_property (itemid,name,value) VALUES ('.$roomid.',\''.$newp.'\',\''.$newv.'\')';
                execute_sql($sql,0);
            }            
        }

        // editing of existing properties
        $propname = array();
        $propval = array();
        $newprops = array();

        foreach ($_POST as $key => $val) {
            if (substr($key,0,2) == 'zp') {
                $propname[substr($key,2)] = $val;
            }
            if (substr($key,0,2) == 'zv') {
                $propval[substr($key,2)] = $val;
            }
            if (substr($key,0,4) == 'new_') {
                $newprops[substr($key,4)] = $val;
            }
        }
        foreach ($propname as $k => $pn) {
            if ($propval[$k] == '') {
                $sql = 'DELETE FROM '.$CFG->prefix.'bookings_item_property WHERE itemid='.$roomid.' AND name = "'.$pn.'" ';
            } else {
                $sql = 'UPDATE '.$CFG->prefix.'bookings_item_property set name=\''.$pn.'\',value=\''.$propval[$k].'\' WHERE id='.$k;
            }            
            execute_sql($sql,0);
        }
        foreach ($newprops as $n => $v) {
            if ($v == '') {
                $sql = 'DELETE FROM '.$CFG->prefix.'bookings_item_property WHERE itemid='.$roomid.' AND name = "'.$n.'" ';
            } else {
                $sql = 'INSERT INTO '.$CFG->prefix.'bookings_item_property (itemid,name,value) values ('.$roomid.',\''.$n.'\',\''.$v.'\')';
            }
            execute_sql($sql,0);
        }

    }
    $html .= '<div class="mod-bookings itemeditor combo">';            
    $thistype = '';
    $sql = 'SELECT DISTINCT r.type, r.type
            FROM '.$CFG->prefix.'bookings_item r
            ORDER BY r.type';
    if ($typelist = get_records_sql($sql)) {
        $kombo = "<select name=\"kombotype\" onchange=\"document.myform.reload.click()\">";
        $kombo .= "<option value=\" \"> -- ".get_string("selecttype","bookings")." -- </option>\n ";
        foreach ($typelist as $type) {
                $selected = "";
                if ($type->type == $item_type) { 
                    $selected = "selected";
                    $thistype = $type->type;
                }    
                $kombo .= '<option value="'.$type->type.'" '.$selected.'>'.$type->type.'</option>'."\n ";
        }
        $kombo .= '</select>'."\n";
    }
    $html .= $kombo;
    unset($kombo);
    if (!$thistype) {
        $item_type = 'room';
    }    

    $rname = '';
    $sql = 'SELECT r.id, r.name, r.parent
            FROM '.$CFG->prefix.'bookings_item r
            WHERE r.type = "'.$item_type.'"
            ORDER BY r.name';
    /// print $sql;            
    if ($roomlist = get_records_sql($sql)) {
        $kombo = "<select name=\"komboroom\" onchange=\"document.myform.reload.click()\">";
        $kombo .= "<option value=\" \"> -- ".get_string("select","bookings")." $item_type -- </option>\n ";
        foreach ($roomlist as $room) {
                $selected = "";
                if ($room->id == $roomid) { 
                    $selected = "selected";
                    $rname = $room->name;
                    $parent = $room->parent;
                }
                $kombo .= '<option value="'.$room->id.'" '.$selected.'>'.$room->name.'</option>'."\n ";
        }
        $kombo .= '</select>'."\n";
    }
    $html .= $kombo;
    if (!isset($roomid) || $rname == '') {
        $html .= '<input id="reload" type=submit name="reload">';
        $html .= '<input type=submit value="'.get_string('new').'" name="new"></div>';
        print_header_simple(format_string('ItemEditor'), "",
                 "$navigation ".'ItemEditor', "", "", true);
        print $html . "</form>";
        print_footer($course);
        return;
    }
    $html .= '<input type=submit value="'.get_string('savechanges').'" name="save">';
    $html .= '<input type=submit value="'.get_string('new').'" name="new">';
    $html .= '<input type=submit value="'.get_string('delete').'" name="delete">';
    $html .= '<input id="reload" type=submit name="reload"></div>';
    if ($parent) {
        $html .= '<a href="?id='.$id.'&newid='.$parent.'">'.get_string('parent','quiz').'</a> ';
        $html .= '&nbsp; <a href="?id='.$id.'&newid='.$roomid.'&orphan=yes">'.get_string('makeorphan','bookings').'</a>';
    }
    // pick out all props from room_template
    // the names are fieldnames, values are format-string
    // seats -> textfield size=20
    // exclusive -> checkbox
    $fields = array();
    $sql = 'SELECT id,type
            FROM '.$CFG->prefix.'bookings_item i
            WHERE i.name = \''.$item_type.'_template\'';
    if ($r = get_record_sql($sql)) {
        $sql = 'SELECT id,name,value
            FROM '.$CFG->prefix.'bookings_item_property p
            WHERE p.itemid = '.$r->id.'
            ORDER BY p.name';
        if ($proplist = get_records_sql($sql)) {
            foreach($proplist as $prop) {
                $fields[$prop->name]=$prop->value;
            }
        } else {
            $fields = array ('seats'=>'"textfield" size="20"');
        }
        $template_id = $r->id;
    } else {
        $fields = array ('seats'=>'"textfield" size="20"');
        $template_id = 0;
    }



  
    // pick up all properties belonging to this item
    // certain properties have meaning in this script
    // they include  childtype,scheduled
    // others may be added
    $sql = 'SELECT id,name,value
            FROM '.$CFG->prefix.'bookings_item_property p
            WHERE p.itemid = '.$roomid.'
            ORDER BY p.name';
    $values =array();
    $chidltype = '';
    $scheduled = '';
    unset($proplist);
    if ($proplist = get_records_sql($sql)) {
        foreach($proplist as $prop) {
            $values[$prop->name] = $prop;
            // this may become a foreach/function
            if ($prop->name == 'scheduled' and substr($prop->value,0,9) != 'textfield' ) {
                $scheduled = $prop->value;
            }
            if ($prop->name == 'childtype' and substr($prop->value,0,9) != 'textfield' ) {
                $childtype = $prop->value;
            }
        }
    }
    $html .= '<div class="mod-bookings itemeditor edit">';
    $html .= '<table><tr><th>'.$item_type.' name</td><td>&nbsp;</td>
             <td><input type=textfield name="rname" value="'.$rname.'" size="20"></td>';
    if ($scheduled) {
        $html .= '<td> &nbsp; <a href="view.php?id='.$id.'">'.get_string('schedule','bookings').'</a></td>';
    }
    $html .= '</tr></table>';

    // template properties
    $i = 0;
    $html .= '<table width="100%" class="template"><tr><td width=33%>';
    $html .= '<div class="mod-bookings itemeditor template"><table><caption>From template</caption><tr><th class="left">'.get_string('property','bookings').'</th><th>Value</th></tr>';
    foreach ($fields as $prop => $value) {
        list($itype,$isize,$idefault) = split(',',$value,3);
        $html .= '<tr class="stripe'.$i.'"><td>'.$prop ; 
        if (isset($values[$prop] )) {   // a predefined-field exists 
            $html .= '<input type="hidden" name="zp'.$values[$prop]->id.'" value="'.$prop.'" >'."\n";
            if ($roomid == $template_id or $itype == 0 ) {
                $html .= '</td><td><input type="textfield" name="zv'.$values[$prop]->id.'" value="'.$values[$prop]->value.'" size="'.$isize.'" >';
            } else {
                $html .= '</td><td><input type="checkbox" name="zv'.$values[$prop]->id.'" value="1" '. ($values[$prop]->value ? '"checked"' : '' ) .' >';
            }
        } else if($itype == 0) {   // no such field, must create if value entered
            $html .= '</td><td><input type="textfield" name="new_'.$prop.'" value="'.$idefault.'" size="'.$isize.'" >'."\n";
        } else {
            $html .= '</td><td><input type="checkbox" name="new_'.$prop.'" value="1" '. ($idefault ? '"checked"' : '' ) .'>'."\n";
        }
        $html .= '</td></tr>'; 
        $i = ($i+1) % 2;
    }
    $html .= "</table></div>";

    // properies that are not part of the template
    $i = 0; $j = 0;
    $html .= '</td>';
    $html1 = '<td width=33%><div class="mod-bookings itemeditor extra"><table><caption>Extra fields</caption><tr><th>Property</th><th>Value</th></tr>';
    foreach ($values as $prop) {
        if (!isset($fields[$prop->name] )) {   // a predefined-field doesn't exist 
            $html1 .= '<tr class="stripe'.$i.'"><td>'.$prop->name ; 
            $html1 .= '<input type="hidden" name="zp'.$prop->id.'" value="'.$prop->name.'" >'."\n";
            $html1 .= '</td><td><input type="textfield" name="zv'.$prop->id.'" value="'.$prop->value.'" >'."\n";
            $html1 .= '</td></tr>';
            $i = ($i+1) % 2;
            $j++;
        }
    }
    $html1 .= "</table></div></td>";
    if ($j>0) {
        $html .= $html1;
    }

    /// list of children
    $sql = 'SELECT id,name,type,parent
            FROM '.$CFG->prefix.'bookings_item i
            WHERE i.parent = '.$roomid.'
            ORDER BY i.name';
    $values =array();            
    $html .= '<td width=33%>';
    if ($childlist = get_records_sql($sql)) {
        $html .= '<div class="mod-bookings itemeditor extra"><table><caption>';
        if ($childtype != '') {
            $html .= '<a href="?id='.$id.'&item_type='.$item_type.'&childtype='.$childtype.'&newchild=1&komboroom='.$roomid.'">Create</a> Child';
        } else {
            $html .= "Children";
        }
        $html .= '</caption><tr><th>Name</th><th>Type</th></tr>';
        foreach($childlist as $child) {
            $html .= '<tr><td><a href="?id='.$id.'&newtype='.$child->type.'&newid='.$child->id.'">'.$child->name.'</a></td><td>'.$child->type.'</td></tr>';
        }
        $html .= "</table>";
        $html .= "</div>";
    } else {
        if ($childtype != '') {
            $html .= '<a href="?id='.$id.'&childtype='.$childtype.'&newchild=1&komboroom='.$roomid.'">Create</a> Child';
        }
    }
    if ($childtype != '') {
        $sql = 'SELECT r.id, r.name, r.parent
                FROM '.$CFG->prefix.'bookings_item r
                WHERE r.type = "'.$childtype.'"
                    AND r.parent = 0
                    AND r.id != '.$roomid.'
                    AND r.name != "'.$childtype.'_template"
                ORDER BY r.name';
        $kombo = '';
        if ($roomlist = get_records_sql($sql)) {
            $kombo = "<select name=\"adoptchild\" onchange=\"document.myform.reload.click()\">";
            $kombo .= "<option value=\" \"> -- Adopt $childtype -- </option>\n ";
            foreach ($roomlist as $room) {
                    $kombo .= '<option value="'.$room->id.'" > '.$room->name.' </option>'."\n ";
            }
            $kombo .= '</select>'."\n";
        }
        $html .= '<br>'.$kombo;
    }            
    $html .= '</td>';
    $html .= '</tr></table>';
    $html .= '<p>Add New Property';
    $html .= '<input type=textfield name="newp" value="" size="20">';
    $html .= '<input type=textfield name="newv" value="" size="20">';
    $html .= '<input type=hidden name="childtype" value="'.$childtype.'">';
    $html .=  "</form>";



print_header_simple(format_string('ItemEditor'), "",
                 "$navigation ".'ItemEditor', "", "", true);

    print $html;

print_footer($course);


?>
