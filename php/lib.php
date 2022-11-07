<?php

use Embeder\Interceptor;
use Embeder\FileFilter;

/*
|--------------------------------------------------------------------------
| Configure the application
|--------------------------------------------------------------------------
|
| Define constant to determine embedded from PHP code
|
*/

define('EMBEDED', TRUE);
define('EMBEDED_DEBUG',
    file_exists($iniFile = getcwd() . DIRECTORY_SEPARATOR . 'php.ini') ?
        (@parse_ini_file($iniFile, true, INI_SCANNER_TYPED)['embeded_debug'] ?? false)
        : false
);

/*
|--------------------------------------------------------------------------
| Interceptor Library
|--------------------------------------------------------------------------
|
| For embeded() file actions
|
*/
require 'res:///PHP/INC_INTERCEPT';
require 'res:///PHP/INC_STREAM';

/*
|--------------------------------------------------------------------------
| Interceptor Config
|--------------------------------------------------------------------------
|
| Intercept logic for embeded() function
|
*/
$interceptor = new Interceptor(function (string $path) {

    $originalFile = $path;

    // Protected files from file interception (Intercept library) to be ignored
    $protectedFile = in_array($originalFile, ['res:///PHP/RUN', 'res:///PHP/LIB', 'res:///PHP/INC_INTERCEPT', 'res:///PHP/INC_STREAM']);
    if (!$protectedFile) {
        $path = 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $path));
    }
    echo EMBEDED_DEBUG ? ('Intercepting: ' . $originalFile . ' (' . $path . ')' . PHP_EOL) : null;
    $isMediaFile = in_array(pathinfo($originalFile, PATHINFO_EXTENSION), ['bmp', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'wav', 'midi', 'cur']);
    if ($isMediaFile) {
        $copied = copy($path, $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($originalFile, PATHINFO_BASENAME));
        echo EMBEDED_DEBUG ? (('Intercepting: Media file ' . $originalFile . ' copied to ' . $tempFilename . ' (' . $path . ') -> ') . ($copied ? 'Success' : 'Failed') . PHP_EOL) : null;
        return file_get_contents($tempFilename);
    }
    return file_get_contents($path);

});

/*
|--------------------------------------------------------------------------
| Start intercepting includes
|--------------------------------------------------------------------------
*/
$interceptor->setUp();

/*
|--------------------------------------------------------------------------
| Start application
|--------------------------------------------------------------------------
*/
require 'res:///PHP/RUN';

/*
|--------------------------------------------------------------------------
| Stop intercepting includes
|--------------------------------------------------------------------------
*/
$interceptor->tearDown();
