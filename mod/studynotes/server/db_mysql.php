<?php

/**
 * Implements the database interface for the MySQL database
 * Documented in base class
 * @author fabian
 */
class MediabirdDboMySql extends MediabirdDbo {
	private $dblink;
	public function connect() {
		if ($this->dblink) {
			mysql_close($this->dblink);
		}
		$link = mysql_connect(MediabirdConfig::$database_hostname, MediabirdConfig::$database_username, MediabirdConfig::$database_password, true);
		if ($link !== false) {
			mysql_query("USE ".MediabirdConfig::$database_name,$link);
			$this->dblink = $link;
			return true;
		}
		else {
			return false;
		} 
	}
	public function disconnect() {
		if ($this->dblink) {
			return mysql_close($this->dblink) !== false;
		}
		return false;
	}
	
	function getRecordset($sql,$limit_from='',$limit='') {
		if($limit!='') {
			$sql .= " LIMIT $limit";
		} 
		if ($result = mysql_query($sql,$this->dblink)) {
			return $result;
		}
		else {
			error_log($sql);
			return false;
		}
	}

	function fetchNextRecord($result) {
		if (!$result) {
			return false;
		}
		if ($results = mysql_fetch_assoc($result)) {
			$obj = (object)null;
			foreach ($results as $key=>$value) {
				$obj->$key = $value;
			}
			return $obj;
		}
		return false;
	}
	
	function getRecord($table,$select) {
		$table=MediabirdConfig::$database_table_prefix.$table;
		$sql="SELECT * FROM ".$table." WHERE ".$select;
		if($res=$this->getRecordSet($sql)) {
			return $this->fetchNextRecord($res);
		}
		return false;
	}
	
	function getRecords($table, $select='', $sort='', $fields='*', $limitfrom='', $limitnum='') {
		$sql="SELECT $fields FROM ".MediabirdConfig::$database_table_prefix.$table." WHERE ".$select;
		if($sort!='') {
			$sql.=" ORDER BY ".$sort;
		}
		if($limitnum!='') {
			$sql.=" LIMIT ".$limitnum;
		}
		if($res=$this->getRecordSet($sql)) {
			$records = false;
			while($record = $this->fetchNextRecord($res)) {
				if($records===false) {
					$records=array();
				}
				$records[]=$record;
			}
			return $records;
		}
	}
	
	function deleteRecords($table,$select) {
		$sql="DELETE FROM ".MediabirdConfig::$database_table_prefix.$table." WHERE ".$select;
		if(mysql_query($sql,$this->dblink)) {
			return true;
		}
		else {
			return false;
		}
	}

	function recordToArray($obj) {
		if(!$obj) {
			return false;
		}
		
		$arr = array ();
		foreach ($obj as $key=>$value) {
			$arr[$key] = $value;
		}
		return $arr;
	}

	function recordLength($result) {
		return mysql_num_rows($result);
	}

	function escape($str) {
		$s = mysql_real_escape_string($str,$this->dblink);
		return $s;
	}

	function updateRecord($table,$record) {
		$table=MediabirdConfig::$database_table_prefix.$table;
		
		foreach ($record as $key=>$value) {
			if ($value === null) {
				$value = "NULL";
			}
			else if (!is_int($value)) {
				$value = "'".$this->escape($value)."'";
			}
			$queries []= "$key=$value";
		}
		if(count($queries)>0) {
			$query = "UPDATE $table SET ";
			$query .= join(",",$queries);
			$query .= " WHERE id=".$record->id;
			if(mysql_query($query,$this->dblink)) {
				return true;
			}
			else {
				error_log($query);
				return false;
			}
		}
	}
	
	function insertRecord($table, $dataobject, $returnid = true, $primarykey = 'id') {
		$table=MediabirdConfig::$database_table_prefix.$table;
		
		$keys = array ();
		$values = array ();
		foreach ($dataobject as $key=>$value) {
			array_push($keys, $key);
			if ($value === null) {
				$value = "NULL";
			}
			else if (!is_int($value)) {
				$value = "'".$this->escape($value)."'";
			}
			array_push($values, $value);
		}
		$query = "INSERT INTO $table (".join(",", $keys).") VALUES (".join(",", $values).")";
		if (mysql_query($query, $this->dblink)) {
			if ($returnid) {
				return mysql_insert_id($this->dblink);
			}
			else {
				return true;
			}
		}
		else {
			error_log($query);
			return false;
		}
	}
	
	function timestamp($date) {
		return strtotime($date);
	}
	function datetime($time) {
		return date("Y-m-d H:i:s",$time);
	}
}
?>