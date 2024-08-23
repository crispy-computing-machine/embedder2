<?php

use Embeder\Interceptor;

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
| Helper functions
|--------------------------------------------------------------------------
|
| For getting the path to media files or ini files
|
*/
/**
 * Get the path to an embedded media file that is copied to the temporary directory
 * This is for use with Menu and Toolbar image parameters and wb_load_image
 *
 * Note: Not very efficient as it copies the file
 * @param $path
 * @return mixed|string
 */
function embedded_file_exists($path)
{
    if(!defined('EMBEDED')) {
        return file_exists($path);
    }
    $path = 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $path));
    return file_get_contents($path) > 0;
}

/**
 * Get the path to an embedded media file that is copied to the temporary directory
 * This is for use with Menu and Toolbar image parameters and wb_load_image
 *
 * Note: Not very efficient as it copies the file
 * @param $path
 * @return mixed|string
 */
function embedded_media($path)
{
    if(!defined('EMBEDED')) {
        return $path;
    }
    $originalPath = $path;
    $path = 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $path));
    echo EMBEDED_DEBUG ? ('embedded_media: Intercepting: Media file ' . $path . PHP_EOL) : null;
    $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($originalPath, PATHINFO_BASENAME);
    $copied = file_put_contents($tempFilename, file_get_contents($path));
    echo EMBEDED_DEBUG ? (('embedded_media: Intercepted: Media file ' . $path . ' copied to ' . $tempFilename . ' (' . $path . ') -> ') . ($copied ? 'Success' : 'Failed') . PHP_EOL) : null;
    return $tempFilename; // return path
}

/**
 * Helper for when embedded, searches manifest
 * @param string $pattern
 * @param string $manifestFile
 * @return array
 */
function embed_glob($pattern)
{

    // No embed
    if (!defined('EMBEDED')) {
        return glob($pattern);
    }

    // Embed
    $pattern = str_replace('\\', '/', $pattern);
    $manifest = json_decode(file_get_contents('manifest.json'), true);
    $matchedFiles = [];
    foreach ($manifest as $embed) {
        foreach($embed as $hash => $embedPath) {

            #echo print_r($pattern, true) . ' == ' . print_r($embedPath, true) . '(' . var_export(fnmatch($pattern, $embedPath), true) . ')' . PHP_EOL;

            // Use preg_match for regular expression matching
            if (fnmatch($pattern, $embedPath)) {
                $matchedFiles[] = $embedPath;
            }
        }
    }

    return $matchedFiles;
}

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
        return $tempFilename; // return path
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