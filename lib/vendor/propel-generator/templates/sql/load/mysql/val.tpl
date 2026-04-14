<?php
if (in_array($column->getColumn()->getPropelType(), array('VARCHAR', 'LONGVARCHAR', 'DATE', 'DATETIME','CHAR'))) { 
    print "'" . addslashes($column->getValue()) . "'";
} else {
    print $column->getValue();
}
?>
