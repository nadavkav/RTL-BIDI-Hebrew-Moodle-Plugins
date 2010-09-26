<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$roles_ids = optional_param('roles_ids');
$repeat_each = optional_param('repeat_each', 20, PARAM_INT);

admin_externalpage_setup('reportrolescapabilities');
$CFG->stylesheets[] = $CFG->wwwroot.'/admin/report/rolescapabilities/styles.css';
admin_externalpage_print_header();

echo '<div id="legend_container">',
       '<h3>', get_string('legend_title', 'report_rolescapabilities'), '</h3>',
       '<dl id="legend">',
         '<dt><span class="not_set">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('notset', 'role'), '</dd>',

         '<dt><span class="allow">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('allow', 'role'), '</dd>',

         '<dt><span class="prevent">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('prevent', 'role'), '</dd>',

         '<dt><span class="deny">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('prohibit', 'role'), '</dd>',
       '</dl>',
     '</div>';

echo '<div id="options_container">',
     '<form action="index.php" method="post">',
     '<select multiple="multiple" name="roles_ids[]" size="10" id="roles_ids">';

$sql = "SELECT id, name 
          FROM {$CFG->dbname}.{$CFG->prefix}role
         WHERE id IN ({$CFG->report_rolescapabilities_available_roles})
      ORDER BY sortorder ASC";
echo($sql);
$available_roles = get_records_sql($sql);

if (!empty($available_roles)) {
    foreach ($available_roles as $rid => $r) {
        $selected = '';
        if (!empty($roles_ids)) {
            $selected = in_array($rid, $roles_ids) ? 'selected="selected"' : '';
        }
        echo "<option value=\"{$rid}\" {$selected}>{$r->name}</option>";
    }
}
echo '</select>',
     '<p>',
         '<label for="repeat_each">', get_string('repeat_each', 'report_rolescapabilities'), '</label>',
         '<input type="text" id="repeat_each" name="repeat_each" value="', $repeat_each, '" size="2" />',
     '</p>',
     '<input type="submit" value="', get_string('show'), '">',
     '</form></div>';

if (empty($roles_ids)) {
    print_heading(get_string('no_roles_selected', 'report_rolescapabilities'));
} else {

    $roles_list = implode(',', $roles_ids);

    $sql = "SELECT id,shortname, name
              FROM {$CFG->dbname}.{$CFG->prefix}role
             WHERE id IN ({$roles_list})
          ORDER BY sortorder";

    $roles = get_records_sql($sql);

    echo '<table id="roles_capabilities">';

    $th = '<tr><th class="action">'.get_string('capability','role').'</th>'; 
    foreach ($roles as $rid => $r) {
        $th .= "<th class=\"role\">{$r->name}</th>";
    }
    $th .= '<th class="action">'.get_string('capability','role').'</th></tr>';

    $capabilities = array_chunk(get_moodle_capabilities($roles), $repeat_each);
    foreach ($capabilities as $chunk) {

        echo $th;

        foreach ($chunk as $capability) {

            $cap_string = get_cap_string($capability);
            echo '<tr>', $cap_string;
            foreach ($roles as $role) {
                if (isset($capability[$role->shortname])) {
                    echo '<td class="role cap_', $capability[$role->shortname] , '">';
                } else {
                    echo '<td class="role cap_not_set">';
                }
                echo '</td>';
            }   
            echo $cap_string, '</tr>';
        }
    }
    echo '</table>';
}

admin_externalpage_print_footer();

function get_moodle_capabilities($roles) {
    global $CFG;

    $sql = "SELECT id, name, component, contextlevel, riskbitmask
              FROM {$CFG->dbname}.{$CFG->prefix}capabilities
             WHERE name NOT LIKE 'moodle/legacy%'
          ORDER BY contextlevel, name";

    // first, all capabilities
    $records = get_records_sql($sql);
    $capabilities = array();
    foreach ($records as $cap) {
        $capabilities[$cap->name] = array('component' => $cap->component,
                                          'contextlevel' => $cap->contextlevel,
                                          'riskbitmask' => $cap->riskbitmask,
                                          'name' => $cap->name);
    }

    // now, the permissions by role
    foreach ($roles as $role) {

        $sql = "SELECT rc.capability, rc.permission
                  FROM {$CFG->dbname}.{$CFG->prefix}role_capabilities rc
                  JOIN  {$CFG->dbname}.{$CFG->prefix}capabilities c
                    ON c.name = rc.capability
                 WHERE rc.contextid = 1
                   AND rc.roleid = {$role->id}
                   AND rc.capability NOT LIKE 'moodle/legacy%'
              ORDER BY c.contextlevel,c.name";

        $records = get_records_sql($sql);

        foreach ($records as $capability) {
            $capabilities[$capability->capability][$role->shortname] = $capability->permission;
        }
    }
    return $capabilities;
}

function get_cap_string($capability) {
    global $CFG;

    $doc_ref = 'http://docs.moodle.org/'.$CFG->lang.'/Capabilities/'.$capability['name'];
    return "<td class=\"action\">
             <span class=\"cap_friendly_name\"><a href=\"{$doc_ref}\">".get_capability_string($capability['name'])."</a></span>
             <span class=\"cap_name\">{$capability['name']}</span>".
             get_risks_images($capability).
           '</td>';
}

function get_risks_images($capability) {
    global $CFG;

    $strrisks = s(get_string('risks', 'role'));
    $riskinfo = '<span class="risk managetrust">';
    $rowclasses = '';
    if (RISK_MANAGETRUST & (int)$capability['riskbitmask']) {
        $riskinfo .= '<a onclick="this.target=\'docspopup\'" title="'.get_string('riskmanagetrust', 'admin').'" href="'.$CFG->docroot.'/'.$CFG->lang.'/'.$strrisks.'">';
        $riskinfo .= '<img src="'.$CFG->pixpath.'/i/risk_managetrust.gif" alt="'.get_string('riskmanagetrustshort', 'admin').'" /></a>';
        $rowclasses .= ' riskmanagetrust';
    }
    $riskinfo .= '</span><span class="risk config">';
    if (RISK_CONFIG & (int)$capability['riskbitmask']) {
        $riskinfo .= '<a onclick="this.target=\'docspopup\'" title="'.get_string('riskconfig', 'admin').'" href="'.$CFG->docroot.'/'.$CFG->lang.'/'.$strrisks.'">';
        $riskinfo .= '<img src="'.$CFG->pixpath.'/i/risk_config.gif" alt="'.get_string('riskconfigshort', 'admin').'" /></a>';
        $rowclasses .= ' riskconfig';
    }
    $riskinfo .= '</span><span class="risk xss">';
    if (RISK_XSS & (int)$capability['riskbitmask']) {
        $riskinfo .= '<a onclick="this.target=\'docspopup\'" title="'.get_string('riskxss', 'admin').'" href="'.$CFG->docroot.'/'.$CFG->lang.'/'.$strrisks.'">';
        $riskinfo .= '<img src="'.$CFG->pixpath.'/i/risk_xss.gif" alt="'.get_string('riskxssshort', 'admin').'" /></a>';
        $rowclasses .= ' riskxss';
    }
    $riskinfo .= '</span><span class="risk personal">';
    if (RISK_PERSONAL & (int)$capability['riskbitmask']) {
        $riskinfo .= '<a onclick="this.target=\'docspopup\'" title="'.get_string('riskpersonal', 'admin').'" href="'.$CFG->docroot.'/'.$CFG->lang.'/'.$strrisks.'">';
        $riskinfo .= '<img src="'.$CFG->pixpath.'/i/risk_personal.gif" alt="'.get_string('riskpersonalshort', 'admin').'" /></a>';
        $rowclasses .= ' riskpersonal';
    }
    $riskinfo .= '</span><span class="risk spam">';
    if (RISK_SPAM & (int)$capability['riskbitmask']) {
        $riskinfo .= '<a onclick="this.target=\'docspopup\'" title="'.get_string('riskspam', 'admin').'" href="'.$CFG->docroot.'/'.$CFG->lang.'/'.$strrisks.'">';
        $riskinfo .= '<img src="'.$CFG->pixpath.'/i/risk_spam.gif" alt="'.get_string('riskspamshort', 'admin').'" /></a>';
        $rowclasses .= ' riskspam';
    }
    $riskinfo .= '</span>';
    return $riskinfo;
}
?>
