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
$interceptor = new Interceptor(function(string $path) {

    $originalFile = $path;

    if(stripos($path, 'RUN') === false && stripos($path, 'LIB') === false &&
        stripos($path, 'INC_INTERCEPT') === false && stripos($path, 'INC_STREAM') === false){
        $path = 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $path));

    }
    echo 'Intercepting: ' . $originalFile . '('.$path.')' . PHP_EOL;
    if (in_array(pathinfo($originalFile, PATHINFO_EXTENSION), ['bmp', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'wav', 'midi', 'cur'])) {
        copy($path, $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($originalFile, PATHINFO_BASENAME));
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
