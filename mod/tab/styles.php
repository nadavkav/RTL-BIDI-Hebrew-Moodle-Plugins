<?php 
if (right_to_left()){
?>
form.mform .fitemtitle {
text-align:right !important;
direction:rtl !important;
}
<?php
}
else {
?>
form.mform .fitemtitle {
text-align:left !important;
direction:ltr !important;
}
<?php
}
?>