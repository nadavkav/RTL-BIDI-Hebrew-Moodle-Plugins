<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_years extends plugin_base{

	function init(){
		$this->form = false;
		$this->unique = true;
		$this->fullname = get_string('filteryears','block_configurable_reports');
		$this->reporttypes = array('years','sql');
	}

	function summary($data){
		return get_string('filteryears_summary','block_configurable_reports');
	}

	function execute($finalelements, $data){

		$filter_years = optional_param('filter_years','',PARAM_RAW);
		if(!$filter_years)
			return $finalelements;

		if($this->report->type != 'sql'){
				return array($filter_years);
		}
		else{
            $nYears = count($filter_years);
            if ($nYears > 1) { // Multiple years selected
                $aggYears = " REGEXP '";
                for($i=0; $i < $nYears; $i++) {
                    $aggYears .= $filter_years[$i] . "|";
                }
                $aggYears = rtrim($aggYears, '|');
                $aggYears .= "'";

            } else { // Single year selected
                $aggYears = " LIKE '%".$filter_years[0]."' ";
            }

			if(preg_match("/%%FILTER_YEARS:([^%]+)%%/i",$finalelements, $output)){
				$replace = ' AND '.$output[1].$aggYears;
				$temp = str_replace('%%FILTER_YEARS:'.$output[1].'%%',$replace,$finalelements);
                //echo '<div style="float:left;direction:ltr;">'.$temp.'</div><hr/>';
                debugging('<div style="float:left;direction:ltr;">'.$temp.'</div><hr/>', DEBUG_DEVELOPER);
                return $temp;
			}
		}
		return $finalelements;
	}

	function print_filter(&$mform){
		global $CFG;

		$filter_years = optional_param('filter_years',0,PARAM_INT);

		$reportclassname = 'report_'.$this->report->type;
		$reportclass = new $reportclassname($this->report);

		if($this->report->type != 'sql'){
			$components = cr_unserialize($this->report->components);
			$conditions = $components['conditions'];

			$yearslist = $reportclass->elements_by_conditions($conditions);
		}
		else{
			$yearslist = true; //array_keys(get_records('course'));
		}

		$courseoptions = array();
		$courseoptions[0] = get_string('choose');

		//if(!empty($yearslist)){
			//$years = get_records_select('course','id in ('.(implode(',',$yearslist)).')');
            $years = explode(',',get_string('filteryears_list','block_configurable_reports'));
        //print_object($years);die;
			foreach($years as $c){
				$courseoptions[$c] = format_string($c);
			}
		//}

		$select = &$mform->addElement('select', 'filter_years', get_string('filteryears','block_configurable_reports'), $courseoptions);
        $select->setMultiple(true);
		$mform->setType('filter_years', PARAM_RAW);

	}

}

?>