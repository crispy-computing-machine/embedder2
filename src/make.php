<?php

/**
 * Make a "stub" app exe that includes some stuff and runs main app
 *
 * @param $BODY
 * @param $key
 * @return string
 */
function make_c_file($BODY, $key)
{

    // Key to encrypt with
    $key = c_escape($key);

    // Body of app to encrypt/embed
    $BODY = c_escape(rc4_encode('?>' . $BODY, $key));

    // C file with template tags to replace
    $CFILE = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'main.c');
    $CFILE = str_replace(['{KEY}', '{BODY}'], [$key, $BODY], $CFILE);


    return $CFILE;
}

/**
 * Make a bat file to run cl.exe and build app using generated c code
 * @param $DIR
 * @param $inc
 * @return string
 */
function makeBuildBatFile($DIR, $inc)
{
    // /Fe is file output EXE
    $BATFILE = '
    if not defined VisualStudioVersion call "C:\Program Files (x86)\Microsoft Visual Studio 15.0\VC\vcvarsall.bat"
    cl '.$DIR . DIRECTORY_SEPARATOR.'app.c /MD /nologo /I '.$inc.'main /I '.$inc.'TSRM /I  '.$inc.'Zend /I '.$inc.'ext\standard /I '.$inc.'sapi\embed /I '.$inc.' /I '.$inc.'\Release_TS C:\obj\Release_TS\php7embed.lib C:\obj\Release_TS\php7ts.lib /Fe'.$DIR . DIRECTORY_SEPARATOR.'app.exe';
    return $BATFILE;
}

/**
 * Process the input for this program
 * @param $BODY
 * @param $output_dir
 * @param $inc
 * @return void
 * @throws Exception
 */
function process($BODY, $output_dir, $inc)
{

    // Temporary C file
    $key = make_key();
    $CFILE = make_c_file($BODY, $key);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR . 'app.c', $CFILE);

    // Temporary bat file
    $BATFILE = makeBuildBatFile($output_dir, $inc);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR. 'vsbuild.cmd', $BATFILE);

}

/**
 * Usage display
 * @return void
 */
function usage()
{
    echo <<<EOD
Usage:
  php2exe [options] file.php
  
Options:
  File to build                          PHP File to build into exe
  Lib Directory                          PHP src .h files etc
EOD;
    die();
}

/**
 * Main program entry - Read args
 * @param $argv
 * @return void
 */
function main($argv)
{

    $filename = $argv[0];
    $inc = $argv[1];
    $output_dir = __DIR__;

    $BODY = @file_get_contents($filename);
    if (!is_string($BODY)) {
        echo "FATAL: Couldn't load contents of '$filename'\n";
        die();
    }

    process($BODY, $output_dir, $inc);
}

/**
 * Helper: Delete tree
 * @param $dir
 * @return bool
 */
function delTree($dir)
{
    foreach (scandir($dir) as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir("$dir/$file")) {
            delTree("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }
    return rmdir($dir);
}

/**
 * Helper: escape c string
 * @param $str
 * @return string
 */
function c_escape($str)
{
    $ret = '"';
    for ($i = 0, $e = strlen($str); $i !== $e; ++$i) {
        $ret .= sprintf('\\x%02x', ord($str[$i]));
    }
    $ret .= '"';
    return $ret;
}

/**
 * Make 256 length RC4 key
 * @return string
 * @throws Exception
 */
function make_key()
{
    $ret = '';
    for ($i = 0; $i < 256; ++$i) {
        $ret .= chr(random_int(0, 255));
    }
    return $ret;
}

/**
 * Encode sting in RC4 using 256 provided key
 * @param $str
 * @param $key
 * @return mixed
 */
function rc4_encode($str, $key /* must be 256 bytes long */)
{
    $i = 0;
    $j = 0;

    $key = array_map('ord', str_split($key));

    for ($p = 0, $e = strlen($str); $p !== $e; ++$p) {

        $i = ($i + 1) % 256;
        $j = ($j + $key[$i]) % 256;

        $tmp = $key[$i];
        $key[$i] = $key[$j];
        $key[$j] = $tmp;

        $z = $key[($key[$i] + $key[$j]) % 256];

        $str[$p] = $str[$p] ^ chr($z);
    }

    return $str;
}

// Run Program minus this script arg
main(array_splice($argv, 1));