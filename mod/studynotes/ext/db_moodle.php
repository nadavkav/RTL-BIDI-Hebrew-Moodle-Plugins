<?php

class MediabirdDboMoodle extends MediabirdDbo {
	public function connect() {
		return true;
	}
	public function disconnect() {
		return true;
	}

	function getRecordset($sql,$limit_from='',$limit='') {
		return get_recordset_sql($sql,$limit_from,$limit);
	}

	function fetchNextRecord($result) {
		return rs_fetch_next_record($result);
	}

	function getRecord($table,$select) {
		return get_record_select($table,$select);
	}

	function getRecords($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
		return get_records_select($table,$select,$sort,$fields,$limitfrom,$limitnum);
	}

	function deleteRecords($table,$select) {
		return delete_records_select($table,$select);
	}

	function _escapeObj($obj) {
		$o = (object) null;
		foreach ($obj as $key=>$value) {
			$o->$key = is_int($value) ? $value : $this->escape($value);
		}
		return $o;
	}
	
	function updateRecord($table,$record) {
		return update_record($table,$this->_escapeObj($record));
	}

	function insertRecord($table, $dataobject, $returnid = true, $primarykey = 'id') {
		return insert_record($table,$this->_escapeObj($dataobject),$returnid,$primarykey);
	}


	function recordToArray($obj) {
		if(!$obj) {
			return null;
		}

		$arr = array();
		foreach($obj as $key => $value) {
			$arr[$key]=$value;
		}
		return $arr;
	}

	function recordLength($obj) {
		return $obj->RecordCount();
	}

	function escape($str) {
		global $CFG;

		switch ($CFG->dbfamily) {
			case 'mysqli':
				global $mysqli;
				$s = $mysqli->real_escape_string($str);
				break;
			case 'mysql':
				$s = mysql_real_escape_string($str);
				break;
			case 'postgres':
				$s = pg_escape_string($str);
				break;
			default:
				$s = addslashes($str);
		}
		return $s;
	}

	function timestamp($date) {
		return intval($date);
	}
	function datetime($time) {
		return "$time";
	}
}
?>
