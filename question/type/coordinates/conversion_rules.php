<?php
/* Each entry of $basic_unit_conversion_rules is a pair:
 *  - The first string is the name of the rule, which is used when editing the form
 *  - The second string is the actual rule that will be parsed and used as unit conversion
 *  - The array index is the unique id for the rule, which will be stored in the database
 * Note: the id 0 to 99 is reserved, please do not use to create you own rule
 */
$basic_unit_conversion_rules = array();
$basic_unit_conversion_rules[0] = array('None', '');
$basic_unit_conversion_rules[1] = array('Common SI unit','
m: k c d m n;
s: m n p f a;
g: k m;
A: m;
C: m;
J: k m;
W: k m n p M G T P;
');
// $basic_unit_conversion_rules[100] = array(
//  $basic_unit_conversion_rules[1][0] + ' and your own conversion rules',
//  $basic_unit_conversion_rules[1][1] + '');
?>
