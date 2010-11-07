<?php
//connected sipds
    require_once("../../config.php");
    require_once("lib.php");
	

    if (isset($id)) {
        if (!$cm = get_coursemodule_from_id('wiki',$id)) {
            error("Course Module ID was incorrect");
        }
        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $dfwiki = get_record('wiki', "id", $cm->instance)) {
            error("Course module is incorrect");
        }

    } else {
        if (! $dfwiki = get_record('wiki', "id", $a)) {
            error("Course module is incorrect");
        }
        if (! $course = get_record("course", "id", $dfwiki->course)) {
            error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance('wiki', $dfwiki->id, $course->id)) {
            error("Course Module ID was incorrect");
        }
    }

    //get course activity log
    $logs = get_records("log", "course", $dfwiki->course);

    //arrays
    $users = array();
    $ips = array();
    $ipsb = array();
    $total = 0;

    $show = optional_param('show', 'usr',PARAM_ALPHA);

    foreach ($logs as $log) {
        $usr = get_record("user", "id", $log->userid);
        $ind = $usr->username;
        $ip = $log->ip;

        $total++;

        //load user array
        if ($show=='usr') {
            //confirm the user is logged in
            if (isset($users[$ind])) {
                $users[$ind]->total++;

                if (isset($users[$ind]->ips[$ip])){
                    $users[$ind]->ips[$ip]++;
                }else{
                    $users[$ind]->ips[$ip] = 1;
                }
            } else {
                $users[$ind]->total = 1;
                $users[$ind]->id = $usr->id;
                $users[$ind]->ips = array();
                $users[$ind]->ips[$ip] = 1;
            }
        }

        //place the ip in the array
        if ($show=='ip') {
            if (isset($ips[$ip])) {
                $ips[$ip]++;
            }else{
                $ips[$ip] = 1;
            }
        }

        //place the ip in the ipsb array
        if ($show=='ipb') {
            //erase the latest IP number
            $treu = strrchr($ip,'.');
            $ipn = substr($ip,0,strlen($ip)-strlen($treu));

            if (isset($ipsb[$ipn])) {
                $ipsb[$ipn]->num++;
            }else{
                $ipsb[$ipn]->ex = $ip;
                $ipsb[$ipn]->num = 1;
            }
        }
    }

    //sort the given array
    if ($show=='usr'){
        ksort($users);
    }else{
        ksort($ips);
    }

    //INTERFACE
    echo '<html><body>';

    //switch from IP to USER
    echo '<a href="'.$CFG->wwwroot.'/mod/wiki/logs.php?id='.$cm->id.'&amp;show=ip">Passar a veure IPs</a><br />';
    echo '<a href="'.$CFG->wwwroot.'/mod/wiki/logs.php?id='.$cm->id.'&amp;show=ipb">Passar a veure IPs simplificades</a><br />';
    echo '<a href="'.$CFG->wwwroot.'/mod/wiki/logs.php?id='.$cm->id.'&amp;show=usr">Passar a veure Usuaris</a><br />';

    echo '<h1>Registres del Log('.$show.'):</h1>';

    echo 'Total d\'entrades: '.$total.'<br /><br />';

    if ($show=='usr'){
        //print out the user data
        echo '<table border="2" width=60%>
            <tr>
                <th>USER</th><th>TOTAL</th><th>PERCENT</th>
            </tr>';
        foreach ($users as $user => $dats){
            echo '<tr>
                    <td>'.$user.'</td>
                    <td>Total: '.$dats->total.'
                        <table border="1" width=100%>';
                        //IP table
                        ksort($dats->ips);
                        foreach ($dats->ips as $ip => $num){
                            echo '<tr><td>';
                                link_to_popup_window ('/iplookup/index.php?ip='.$ip.'&amp;user='.$dats->id,
                                            'localitzar', $ip,
                                            $height=400, $width=500, 'localitzar');
                            echo '</td><td>'.$num.'</td>
                                <td>'.(round($num/$total*10000)/100).'%</td>
                                </tr>';
                        }
                    echo '</table>';
            echo '<td>'.(round($dats->total/$total*10000)/100).'%</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    if($show=='ip'){
        echo '<table border="2" width=60%>
            <tr>
                <th>IP</th><th>TOTAL</th><th>PERCENT</th>
            </tr>';

            foreach ($ips as $ip => $num){
                echo '<tr>
                        <td>';
                        link_to_popup_window ('/iplookup/index.php?ip='.$ip,
                                            'localitzar', $ip,
                                            $height=400, $width=500, 'localitzar');
                echo'</td>
                        <td>'.$num.'</td>
                        <td>'.(round($num/$total*10000)/100).'%</td>
                    </tr>';
            }

        echo '</table>';
    }

    if($show=='ipb'){
        echo '<table border=2 width=60%>
            <tr>
                <th>IP</th><th>TOTAL</th><th>PERCENT</th>
            </tr>';

            foreach ($ipsb as $ipb => $dat){
                echo '<tr>
                        <td>';
                        link_to_popup_window ('/iplookup/index.php?ip='.$dat->ex,
                                            'localitzar', $ipb.'.X',
                                            $height=400, $width=500, 'localitzar');
                echo'</td>
                        <td>'.$dat->num.'</td>
                        <td>'.(round($dat->num/$total*10000)/100).'%</td>
                    </tr>';
            }

        echo '</table>';
    }

    echo '</body></html>';

?>
