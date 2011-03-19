<?php 

/*
 
 *  @copyright 2011 Victor Bautista (victor [at] sinkia [dt] com)
 *  
 *  This file is free software: you may copy, redistribute and/or modify it  
 *  under the terms of the GNU General Public License as published by the  
 *  Free Software Foundation, either version 2 of the License, or any later version.  
 *  
 *  This file is distributed in the hope that it will be useful, but  
 *  WITHOUT ANY WARRANTY; without even the implied warranty of  
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU  
 *  General Public License for more details.  
 *  
 *  You should have received a copy of the GNU General Public License  
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.  
 *
 *  This file incorporates work covered by the following copyright and permission notice:
 *
 *  Copyright 2010 Blindside Networks Inc.
 *  Initial version:
        Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 */

    //This php script contains all the stuff to backup/restore
    //livewebteaching mods

    //This is the "graphical" structure of the livewebteaching mod:
    //
    //                       livewebteaching
    //                     (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the backup procedure about this mod
    function livewebteaching_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over livewebteaching table
        if ($livewebteachings = get_records ("livewebteaching","course", $preferences->backup_course,"id")) {
            foreach ($livewebteachings as $livewebteaching) {
                if (backup_mod_selected($preferences,'livewebteaching',$livewebteaching->id)) {
                    $status = livewebteaching_backup_one_mod($bf,$preferences,$livewebteaching);
                }
            }
        }
        return $status;
    }
   
    function livewebteaching_backup_one_mod($bf,$preferences,$livewebteaching) {

        global $CFG;
    
        if (is_numeric($livewebteaching)) {
            $livewebteaching = get_record('livewebteaching','id',$livewebteaching);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$livewebteaching->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"livewebteaching"));
        fwrite ($bf,full_tag("NAME",4,false,$livewebteaching->name));
        fwrite ($bf,full_tag("MODERATORPASS",4,false,$livewebteaching->moderatorpass));
        fwrite ($bf,full_tag("VIEWERPASS",4,false,$livewebteaching->viewerpass));
        fwrite ($bf,full_tag("WAIT",4,false,$livewebteaching->wait));
        fwrite ($bf,full_tag("MEETINGID",4,false,$livewebteaching->meetingid));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$livewebteaching->timemodified));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function livewebteaching_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += livewebteaching_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
         //First the course data
         $info[0][0] = get_string("modulenameplural","livewebteaching");
         $info[0][1] = count_records("livewebteaching", "course", "$course");
         return $info;
    } 

    ////Return an array of info (name,value)
    function livewebteaching_check_backup_mods_instances($instance,$backup_unique_code) {
         //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

?>
