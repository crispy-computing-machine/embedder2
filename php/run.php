<?php
//========================================================================
//       Embeder - Make an executable Windows-binary file from a PHP script
//
//       License : PHP License (http://www.php.net/license/3_0.txt)
//       Author : Eric Colinet <e dot colinet at laposte dot net>
//       Author : Jared Allard <jaredallard at outlooke dot com>
//       http://wildphp.free.fr/wiki/doku?id=win32std:embeder
//========================================================================

/** Base file for "empty" consoles **/

echo basename($argv[0])." - Powered by PHP version ".phpversion()."\n";
echo "Empty base binary, did you make sure too add a php file via 'embeder2 main ".basename($argv[0])." phpfile.php'?\n";

?>