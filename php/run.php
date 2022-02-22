<?php
echo "Powered by PHP Embedded (".(var_export(defined('EMBEDED'),TRUE)).") " . phpversion() . "(".(PHP_INT_SIZE === 4 ? '32bit' : '64bit').")" . "\n";
echo "Empty base binary\n";
?>