<?php  // $Id: treelib.php,v 1.00 2005/07/18 07:19:40 fremaux Exp $

// Project : Technical Project Manager (IEEE like)
// Author : Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
// Contributors : LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit

/// Library of tree dedicated operations. This library is adapted from the treelib.php in techproject, 
/// but wrapped back for brainstorm. The standard API for tree operation has been respected, although
/// the function parameters may have some changes.

/**
Major change resides in where the tree is stored. In the brainstorm module, the tree is stored
as records in operatordata. The itemsource field identifies the node. The itemdest stands for father's
id. The intvalue will serve as ordering
*/

/*** Index of functions **********************
/// the function parameters may have some changes.
function brainstorm_tree_delete($brainstormid, $userid, $groupid, $id){
function tree_delete_rec($id){
function brainstorm_tree_updateordering($brainstormid, $groupid, $userid, $id, $istree){
function brainstorm_tree_up($brainstormid, $userid, $groupid, $id, $istree = 1){
function brainstorm_tree_down($brainstormid, $userid, $groupid, $id, $istree=1){
function brainstorm_tree_left($brainstormid, $userid, $groupid, $id){
function brainstorm_tree_right($brainstormid, $userid, $groupid, $id){
function brainstorm_get_subtree_list($table, $id){
function brainstorm_count_subs($id){
function brainstorm_tree_get_upper_branch($id, $includeStart = false, $returnordering = false){
function brainstorm_tree_get_max_ordering($brainstormid, $userid=null, $groupid=0, $istree = false, $fatherid = 0){
**********************************************/

/**
* deletes into tree a full branch. note that it will work either
* @param id the root node id
* @param table the table where the tree is in 
* @param istree if istree is not set, considers table as a simple ordered list
* @return an array of deleted ids
*/
function brainstorm_tree_delete($brainstormid, $userid, $groupid, $id){
		brainstorm_tree_updateordering($brainstormid, $userid, $groupid, $id, 1);
		return tree_delete_rec($id);
}

/**
* deletes recursively a node and its subnodes. this is the recursion deletion
* @return an array of deleted ids
*/
function tree_delete_rec($id){
	global $CFG;

    $deleted = array();
    if (empty($id)) return $deleted;    

	// echo "deleting $id<br/>";
	
	// getting all subnodes to delete if is tree.
	if ($istree){
    	$sql = "
    	    SELECT 
    	        id
    	    FROM 
    	        {$CFG->prefix}{$table}brainstorm_operatordata
    	    WHERE
    	        operatorid = 'hierarchize' AND
    	        itemdest = {$id}
    	";
    
    	// deleting subnodes if any
    	if ($subs = get_record_sql($sql)) {
    		foreach($subs as $aSub){
    			$deleted = array_merge($deleted, tree_delete_rec($aSub->id));
    		}
    	}
    }
	// deleting current node
	delete_records('brainstorm_operatordata', 'id', $id); 
	$deleted[] = $id;
	return $deleted;
}

/**
* updates ordering of a tree branch from a specific node, reordering 
* all subsequent siblings. 
* @param id the node from where to reorder
* @param table the table-tree
*/
function brainstorm_tree_updateordering($brainstormid, $groupid, $userid, $id, $istree){

	// getting ordering value of the current node
	global $CFG;

	$res =  get_record('brainstorm_operatordata', 'id', $id);
	if (!$res) return;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid);
	$treeClause = ($istree) ? "     AND itemdest = {$res->itemdest} " : '';

	// getting subsequent nodes that have same father
	$sql = "
	    SELECT 
	        id   
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        brainstormid = {$brainstormid} AND
	        operatorid = 'hierarchize' AND
	        intvalue > {$res->intvalue}
	        {$treeClause}
	        {$accessClause}
	    ORDER BY 
	        intvalue
	";

	// reordering subsequent nodes using an object
	if( $nextsubs = get_record_sql($sql)) {
	    $ordering = $res->intvalue + 1;
		foreach($nextsubs as $asub){
			$objet->id = $asub->id;
			$objet->intvalue = $ordering;
			update_record('brainstorm_operatordata', $objet);
			$ordering++;
		}
	}
}

/**
* raises a node in the tree, reordering all what needed
* @param id the id of the raised node
* @param table the table-tree where to operate
* @param istree true if is a table-tree rather than a table-list
* @return void
*/
function brainstorm_tree_up($brainstormid, $userid, $groupid, $id, $istree = 1){
	global $CFG;

	$res =  get_record('brainstorm_operatordata', 'id', $id);
	if (!$res) return;
    
    $operator = ($istree) ? 'hierarchize' : 'order' ;
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, false);
	$treeClause = ($istree) ? "     AND itemdest = {$res->itemdest} " : '';

	if($res->intvalue > 1){
		$newordering = $res->intvalue - 1;

		$sql = "
		    SELECT 
		        id
		    FROM 
		        {$CFG->prefix}brainstorm_operatordata AS od
		    WHERE 		        
		        brainstormid = {$brainstormid} AND
		        operatorid = '{$operator}' AND
		        intvalue = {$newordering}
		        {$treeClause}
		        {$accessClause}
		";
		
		// echo $sql;
		
		$result =  get_record_sql($sql);
		$resid = $result->id;

        // swapping
		$objet->id = $resid;
		$objet->intvalue = $res->intvalue;
		update_record('brainstorm_operatordata', $objet);

		$objet->id = $id;
		$objet->intvalue = $newordering;
		update_record('brainstorm_operatordata', $objet);
	}
}

/**
* lowers a node on its branch. this is done by swapping ordering.
* @param project the current project
* @param group the current group
* @param id the node id
* @param table the table-tree where to perform swap
* @param istree if not set, performs swapping on a single list
*/
function brainstorm_tree_down($brainstormid, $userid, $groupid, $id, $istree=1){
	global $CFG;

	$res =  get_record('brainstorm_operatordata', 'id', $id);
    $operator = ($istree) ? 'hierarchize' : 'order' ;
    $accessClause = brainstorm_get_accessclauses($userid, $groupid, false);
	$treeClause = ($istree) ? " AND itemdest = {$res->itemdest} " : '';

	$sql = "
	    SELECT 
	        MAX(intvalue) AS ordering
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE
	        brainstormid = {$brainstormid} AND
	        operatorid = '{$operator}'
	        {$treeClause}
	        {$accessClause}
	";
	
	$resmaxordering = get_record_sql($sql);
	$maxordering = $resmaxordering->ordering;
	
	if($res->intvalue < $maxordering){
		$newordering = $res->intvalue + 1;

		$sql = "
		    SELECT 
		        id
		    FROM    
		        {$CFG->prefix}brainstorm_operatordata AS od
		    WHERE 
    	        brainstormid = {$brainstormid} AND
    	        operatorid = '{$operator}' AND
		        intvalue = {$newordering}
		        {$treeClause}
		        {$accessClause}
		";
		$result =  get_record_sql($sql);
		$resid = $result->id;

        // swapping
		$objet->id = $resid;
		$objet->intvalue = $res->intvalue;
		update_record('brainstorm_operatordata', $objet);

		$objet->id = $id;
		$objet->intvalue = $newordering;
		update_record('brainstorm_operatordata', $objet);
	}
}

/**
* raises a node to the upper level. Subsequent nodes become sons of the raised node
* @param int brainstormid the current module
* @param int $groupid the current group
* @param int $id the node to be raised
*/
function brainstorm_tree_left($brainstormid, $userid, $groupid, $id){
	global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, false);

	$sql = "
	    SELECT 
	        itemdest, 
	        intvalue
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        id = $id
	";
	$res =  get_record_sql($sql);
	$ordering = $res->intvalue;
	$fatherid = $res->itemdest;

	$sql = "
	    SELECT 
	        id,
	        itemdest
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata as od
	    WHERE 
	        id = $fatherid
	";
	$resfatherid =  get_record_sql($sql);
	if (!$resfatherid) return; // this protects against bouncing left request
	$fatheridbis = $resfatherid->itemdest; //id grand pere

	$sql = "
	    SELECT 
	        id,
	        intvalue
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
        WHERE 
	        brainstormid = {$brainstormid} AND
	        operatorid = 'hierarchize' AND
            intvalue > $ordering AND 
            itemdest = $fatherid
            {$accessClause}
        ORDER BY 
            intvalue
    ";
	$newbrotherordering = $ordering;

	if($ress = get_records_sql($sql)){
		foreach($ress as $res){
			$objet->id = $res->id;
			$objet->intvalue = $newbrotherordering;
			update_record('brainstorm_operatordata AS od', $objet);
			$newbrotherordering = $newbrotherordering + 1;
		}
	}

	// getting father's ordering
	$sql = "
	    SELECT
	        id, 
	        intvalue
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        brainstormid = {$brainstormid} AND
	        operatorid = 'hierarchize' AND
	        id = $fatherid
	        {$accessClause}
	";
	$resorderingfather =  get_record_sql($sql);
	$orderingfather = $resorderingfather->intvalue;

	// reordering uncles
	$sql = "
	    SELECT 
	        id,
	        intvalue
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        brainstormid = {$brainstormid} AND
	        operatorid = 'hierarchize' AND
	        intvalue > {$orderingfather} AND 
	        itemdest = {$fatheridbis}
	        {$accessClause}
	    ORDER BY 
	        intvalue
	";
	if ($resbrotherfathers = get_records_sql($sql)) {
		foreach($resbrotherfathers as $resbrotherfather){
			$idbrotherfather = $resbrotherfather->id;
			$nextordering = $resbrotherfather->intvalue + 1;

			$objet->id = $idbrotherfather;
			$objet->intvalue = $nextordering;
			update_record('brainstorm_operatordata', $objet);
		}
	}

	// reordering
	$newordering = $orderingfather + 1;

	$objet->id = $id;
	$objet->intvalue = $newordering;
	$objet->itemdest = $fatheridbis;
	update_record('brainstorm_operatordata', $objet);
}

/**
* lowers a node within its own branch setting it as 
* sub node of the previous sibling. The first son cannot be lowered.
* @param project the current project
* @param group the current group
* @param id the node to be lowered
* @param table the table-tree name
*/
function brainstorm_tree_right($brainstormid, $userid, $groupid, $id){
	global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, false);

    /// get ordering and parent for the moving node
	$sql = "
	    SELECT 
	        itemdest, 
	        intvalue
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        id = $id
	";
	$res =  get_record_sql($sql);
	$ordering = $res->intvalue;
	$fatherid = $res->itemdest;

    /// get previous record if not first. It will become our parent.
	if($ordering > 1){
		$orderingbis = $ordering - 1;

		$sql = "
		    SELECT 
		        id,
		        id
		    FROM 
		        {$CFG->prefix}brainstorm_operatordata AS od
    		WHERE 
    	        brainstormid = {$brainstormid} AND
    	        operatorid = 'hierarchize' AND
    		    intvalue = $orderingbis AND 
    		    itemdest = $fatherid
    		    {$accessClause}
        ";
		$resid = get_record_sql($sql);
		$newfatherid = $resid->id;

        /// get our upward brothers. They should be ordered back from ordering
		$sql = "
		    SELECT 
		        id, 
		        intvalue
		    FROM 
		        {$CFG->prefix}brainstorm_operatordata AS od
		    WHERE 
    	        brainstormid = {$brainstormid} AND
    	        operatorid = 'hierarchize' AND
		        intvalue > $ordering AND 
		        itemdest = $fatherid 
		        {$accessClause}
		    ORDER BY 
		        intvalue
		";
		$newbrotherordering = $ordering;

        /// order back all upward brothers
		if ($resbrothers = get_records_sql($sql)) {
			foreach($resbrothers as $resbrother){
				$objet->id = $resbrother->id;
				$objet->intvalue = $newbrotherordering;
				update_record('brainstorm_operatordata', $objet);
				$newbrotherordering = $newbrotherordering + 1;
			}
		}

		$maxordering = brainstorm_tree_get_max_ordering($brainstormid, null, $groupid, true, $newfatherid);
		$newordering = $maxordering + 1;

		// assigning father's id
		$objet->id = $id;
		$objet->itemdest = $newfatherid;
		$objet->intvalue = $newordering;
		update_record('brainstorm_operatordata', $objet);
	}
}

/**
* get the full list of dependencies in a tree
* @param table the table-tree
* @param id the node from where to start of
* @return a comma separated list of nodes
*/
function brainstorm_get_subtree_list($table, $id){
    $res = get_records_menu($table, 'fatherid', $id);
    $ids = array();
    if (is_array($res)){
        foreach(array_keys($res) as $aSub){
            $ids[] = $aSub;
            $subs = brainstorm_get_subtree_list($table, $aSub);
            if (!empty($subs)) $ids[] = $subs;
        }
    }
    return(implode(',', $ids));
}

/**
* count direct subs in a tree
* @param table the table-tree
* @param the node
* @return the number of direct subs
*/
function brainstorm_count_subs($id){
    global $CFG;
    
    // counting direct subs
	$sql = "
	    SELECT 
	        COUNT(id)
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata
	    WHERE 
	        itemdest = {$id} AND
	        operatorid = 'hierarchize'
	";
	$res = count_records_sql($sql);
	return $res;
}

/**
* get upper branch to a node from root to node
* @param the table-tree where to oper
* @param id the node id to reach
* @param includeStart true if leaf node is in the list
* @return array of node ids
*/
function brainstorm_tree_get_upper_branch($id, $includeStart = false, $returnordering = false){
    global $CFG;

    $nodelist = array();
    $res = get_record('brainstorm_operatordata', 'id', $id);
    if ($includeStart) $nodelist[] = ($returnordering) ? $res->intvalue : $id ;    
    while($res->itemdest != 0){
        $res = get_record($table, 'id', $res->itemdest, 'operatorid', 'hierarchize');
        $nodelist[] = ($returnordering) ? $res->intvalue : $res->itemdest;
    }
    $nodelist = array_reverse($nodelist);
    return $nodelist;
}

/**
* get the max ordering available in sequence at a specified node
* @param int $brainstormid the current brainstorm context
* @param int $groupid the current group
* @param boolean $istree true id the entity is table-tree rather than table-list
* @param fatherid the parent node
* @return integer the max ordering found
*/
function brainstorm_tree_get_max_ordering($brainstormid, $userid=null, $groupid=0, $istree = false, $fatherid = 0){
    global $CFG;

    $accessClause = brainstorm_get_accessclauses($userid, $groupid, false);

    $operator = ($istree) ? 'hierarchize' : 'order' ;
    $treeClause = ($istree) ? "AND itemdest = {$fatherid}" : '';
	$sql = "
	    SELECT 
	        MAX(intvalue) as position
	    FROM 
	        {$CFG->prefix}brainstorm_operatordata AS od
	    WHERE 
	        brainstormid = {$brainstormid} AND
	        operatorid = '{$operator}'
	        {$accessClause}
	        {$treeClause}
	";

	if(!$result = get_record_sql($sql)){
		$result->position = 1;
	}
	return $result->position;
}
