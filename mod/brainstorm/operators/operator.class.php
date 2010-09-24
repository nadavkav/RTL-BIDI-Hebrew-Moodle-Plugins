<?php

class BrainstormOperator{

    var $id;
    var $brainstormid;
    var $configdata;

    function BrainstormOperator($brainstormid, $id){
        $this->id = $id;
        if ($id){
            $this->brainstormid = $brainstormid;
            $oprecord = get_record('brainstorm_operators', 'brainstormid', $brainstormid, 'operatorid', $id);
            $this->configdata = (isset($oprecord->configdata)) ? unserialize($oprecord->configdata) : new Object() ;
            $this->active = ($oprecord) ? $oprecord->active : 1 ;
        }
    }
}
?>