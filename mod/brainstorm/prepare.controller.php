<?php

/********************************** Enable an operator ********************************/
if ($action == 'enable'){
    $operatorid = required_param('operatorid', PARAM_ALPHA);
    $oprecord = get_record('brainstorm_operators', 'brainstormid', $brainstorm->id, 'operatorid', $operatorid);
    if ($oprecord){
        $oprecord->active = 1;
        if (!update_record('brainstorm_operators', $oprecord)){
            error("could not update record");
        }        
    }
    else{
        $oprecord->brainstormid = $brainstorm->id;
        $oprecord->operatorid = $operatorid;
        $oprecord->active = 1;
        $oprecord->configdata = serialize(new Object());
        if (!insert_record('brainstorm_operators', $oprecord)){
            error("could not insert record");
        }
    }
}
/********************************** Disable an operator ********************************/
if ($action == 'disable'){
    $operatorid = required_param('operatorid', PARAM_ALPHA);
    $oprecordid = get_field('brainstorm_operators', 'id', 'brainstormid', $brainstorm->id, 'operatorid', $operatorid);
    $oprecord->id = $oprecordid;
    $oprecord->active = 0;
    if (!update_record('brainstorm_operators', $oprecord)){
        error("could not update record");
    }
}
?>