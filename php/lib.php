<?php

define('EMBEDED', TRUE);

/**
 * include/require replacement function
 * @param $file
 * @param $force
 * @return mixed|string
 */
function embeded($file, $force = false)
{
    $originalFile = $file;
    $file = $force || defined('EMBEDED') ? 'res:///PHP/' . md5(str_replace($backslash = chr(92), $forwardSlash = chr(47), $file)) : $file;
    if (in_array(pathinfo($originalFile, PATHINFO_EXTENSION), ['bmp', 'gif', 'jpg', 'jpeg', 'png', 'ico', 'wav', 'midi', 'cur'])) {
        copy($file, $tempFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($originalFile, PATHINFO_BASENAME));
        return $tempFilename;
    }
    if (php_sapi_name() === 'cli') {
        echo 'embeded(' . $file . ') Including file ' . $originalFile . ' = ' . strlen(@(int)file_get_contents($file)) . PHP_EOL;
    }
    return $file;
}

/**
 * Embedded equivalent of file_exists()
 * @param $file
 * @return bool
 */
function embeded_file_exists($file)
{
    return file_get_contents(embeded($file)) !== false;
}
require 'res:///PHP/RUN';
