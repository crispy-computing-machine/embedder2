<?php
echo "Powered by PHP Embedded " . phpversion() . "(".(PHP_INT_SIZE === 4 ? '32bit' : '64bit').")" . "\n";
echo "win32std ".var_export(extension_loaded('win32std'), TRUE)."\n";
echo "Embedded ".var_export(defined('EMBEDED'), TRUE)."\n";
?>