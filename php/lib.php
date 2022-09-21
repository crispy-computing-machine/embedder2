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
require 'res:///PHP/INC_FILTER';
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
$fileFilter = FileFilter::createAllWhitelisted();
$fileFilter->whitelistExtension('php');

// Media
$fileFilter->whitelistExtension('bmp');
$fileFilter->whitelistExtension('gif');
$fileFilter->whitelistExtension('jpg');
$fileFilter->whitelistExtension('jpeg');
$fileFilter->whitelistExtension('png');
$fileFilter->whitelistExtension('ico');
$fileFilter->whitelistExtension('wav');
$fileFilter->whitelistExtension('midi');
$fileFilter->whitelistExtension('cur');

$interceptor = new Interceptor(function(string $path) use($fileFilter) {

    if($fileFilter->test($path)){
        $originalFile = $path;
        $file = 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $path));
        echo 'Intercepting: ' . $path . '('.$file.')' . PHP_EOL;
        if (in_array(pathinfo($originalFile, PATHINFO_EXTENSION), ['bmp', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'wav', 'midi', 'cur'])) {
            copy($file, $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($originalFile, PATHINFO_BASENAME));
            return file_get_contents($tempFilename);
        }
        return file_get_contents($file);

    }

    return null;
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
