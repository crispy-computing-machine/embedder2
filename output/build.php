<?php

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

function c_escape($str)
{
    $ret = '"';
    for ($i = 0, $e = strlen($str); $i !== $e; ++$i) {
        $ret .= sprintf('\\x%02x', ord($str[$i]));
    }
    $ret .= '"';
    return $ret;
}

function make_key()
{
    $ret = '';
    for ($i = 0; $i < 256; ++$i) {
        $ret .= chr(random_int(0, 255));
    }
    return $ret;
}

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

function make_c_file($BODY, $key)
{

    $CFILE = '
/* PHP Includes */
#include <sapi/embed/php_embed.h>
#include "ext/standard/php_standard.h"
#include "zend_smart_str.h"

#ifdef PHP_WIN32
#include <io.h>
#include <fcntl.h>
#endif
    
typedef unsigned int uid_t;
typedef unsigned int gid_t;
void rc4_encode_inplace(char* str, size_t str_len, char* key) {

	unsigned char i = 0;
	unsigned char j = 0;
	size_t p = 0;
	
	for (p = 0; p != str_len; ++p) {
	
		i = i + 1;
		j = j + key[i];
		
		unsigned char tmp = key[i];
		key[i] = key[j];
		key[j] = tmp;
	
		unsigned char z = key[ (unsigned char)( key[i] + key[j] ) ];
	
		str[p] = str[p] ^ z;
	}
	 
}

int main(int argc, char** argv) {
	
	int ret = 0;
	
	char key[256] = ';
    $CFILE .= c_escape($key);
    $CFILE .= ';
	
	char application_source[] = ';

    $CFILE .= c_escape(rc4_encode('?>' . $BODY, $key));

    $CFILE .= ';

	rc4_encode_inplace(application_source, sizeof(application_source)-1, key);

	PHP_EMBED_START_BLOCK(argc, argv)

		ret = zend_eval_string((char*)application_source, NULL, (char*)"" TSRMLS_CC);
	
	PHP_EMBED_END_BLOCK()
	
	return ret;
}
';

    return $CFILE;
}

function make_bat_file($DIR, $VC_VER)
{
    // /Fe is file output EXE
    $BATFILE = '
    if not defined VisualStudioVersion call "C:\Program Files (x86)\Microsoft Visual Studio ' . $VC_VER . '.0\VC\vcvarsall.bat"
    cl '.dirname($DIR) . DIRECTORY_SEPARATOR.'myapp.c /MD /nologo /I ' . $DIR . '\include /I ' . $DIR . '\include\Zend /I ' . $DIR . '\include\TSRM /I ' . $DIR . '\include\main /I ' . $DIR . '\main ' . $DIR . '\lib\php7embed.lib /I ' . $DIR . '\include\sapi\embed /I' . $DIR . '\lib\php7.lib /Fe'.dirname($DIR) . DIRECTORY_SEPARATOR.'output.exe';
    return $BATFILE;
}

function process($PHPVER, $BODY, $force_create_dir, $output_dir, $VC_VER, $should_cleanup)
{

    if (!is_dir($output_dir) && !$force_create_dir) {
        die($output_dir . ' already exists, use --force');
    } elseif(!is_dir($output_dir)) {
        mkdir($output_dir);
    }

    // https://windows.php.net/downloads/releases/php-7.4.32-Win32-vc15-x64.zip
    $URL_MAIN = 'https://windows.php.net/downloads/releases/php-' . $PHPVER . '-Win32-vc15-x86.zip';
    $ZIP_MAIN = 'php-' . $PHPVER . '.zip';

    // https://windows.php.net/downloads/releases/php-devel-pack-7.4.32-Win32-vc15-x86.zip
    $URL_DEVEL = 'https://windows.php.net/downloads/releases/php-devel-pack-' . $PHPVER . '-Win32-vc15-x86.zip';
    $ZIP_DEVEL = 'php-devel-pack-' . $PHPVER . '.zip';

    $main = file_put_contents($output_dir . DIRECTORY_SEPARATOR . $ZIP_MAIN, get($URL_MAIN));
    $dev = file_put_contents($output_dir . DIRECTORY_SEPARATOR . $ZIP_DEVEL, get($URL_DEVEL));

    // Unzip
    $zip = new \ZipArchive;
    if (!$zip->open($output_dir . DIRECTORY_SEPARATOR . $ZIP_DEVEL)) {
        die("FATAL: Couldn't open zip file '$ZIP_DEVEL'\n");
    }

    $firstEntry = $zip->statIndex(0)['name'];
    $DIR = substr($firstEntry, 0, strrpos($firstEntry, '/'));

    $zip->extractTo($output_dir);
    $zip->close();

    $zip = new \ZipArchive;
    if (!$zip->open($output_dir . DIRECTORY_SEPARATOR . $ZIP_MAIN)) {
        die("FATAL: Couldn't open zip file '$ZIP_MAIN'\n");
    }
    $zip->extractTo($output_dir . '/' . $DIR . '/lib/', 'php7embed.lib');
    $zip->extractTo($output_dir, 'php7ts.dll');

    // Patch

    copy("$output_dir/$DIR/include/Zend/zend_config.w32.h", "$output_dir/$DIR/include/Zend/zend_config.h");
    copy("$output_dir/$DIR/include/main/config.w32.h", "$output_dir/$DIR/include/php_config.h");

    // Temporary C file

    $key = make_key();

    $CFILE = make_c_file($BODY, $key);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR . 'myapp.c', $CFILE);

    // Temporary bat file

    $BATFILE = make_bat_file($output_dir . DIRECTORY_SEPARATOR. 'php-devel-pack-' . $PHPVER . '-Win32-vc15-x86', $VC_VER);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR. 'vsbuild.cmd', $BATFILE);

    // Run bat file

    passthru($output_dir . DIRECTORY_SEPARATOR . 'vsbuild.cmd');

    // Clean up

    if ($should_cleanup) {
        delTree($output_dir . '/' . $DIR);
        unlink($output_dir . '/myapp.c');
        unlink($output_dir . '/myapp.obj');
        unlink($output_dir . '/vsbuild.cmd');
    }

}

/**
 * @param $url
 * @return bool|string
 */
function get($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: PHP Script'
    ));        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $output = curl_exec($ch);
    $err = curl_error($ch);
    if(trim($err)){
        die($err);
    }

    curl_close($ch);
    return $output;
}

function usage()
{
    echo <<<EOD
Usage:
  php2exe [options] file.php
  
Options:
  --force                          Delete target directory if it exists
  --help                           Display usage
  --cleanup                        Remove temporary files
  --output {directory}             Set target directory (default 'output')
  --vc-ver {number}                Set target VS version (default '12' ie 2013)
  --version {string}               Set target PHP runtime
                                    (default '7.4.32-nts-Win32-VC11-x86')
EOD;
    die();
}

function main($argv)
{

    $filename = null;
    $force_create_dir = true;
    $output_dir = __DIR__;
    $PHPVER = '7.4.32';
    $VC_VER = '15';
    $should_cleanup = false;

    for ($i = 0, $e = count($argv); $i !== $e; ++$i) {
        $arg = $argv[$i];

        if ($arg == '--help') {
            usage();

        } elseif ($arg == '--force') {
            $force_create_dir = true;

        } elseif ($arg == '--output') {
            $output_dir = $argv[++$i];

        } elseif ($arg == '--vc-ver') {
            $VC_VER = $argv[++$i];

        } elseif ($arg == '--version') {
            $PHPVER = $argv[++$i];

        } elseif ($arg == '--cleanup') {
            $should_cleanup = true;

        } else {
            if (!is_null($filename)) {
                echo "FATAL: Multiple files specified ('$filename' and '$arg')\n";
                die();
            }

            $filename = $arg;
            if (!is_file($filename)) {
                echo "FATAL: Invalid file '$filename'\n";
                die();
            }

        }
    }

    if (is_null($filename)) {
        echo "FATAL: No file selected\n";
        usage();
    }

    $BODY = @file_get_contents($filename);
    if (!is_string($BODY)) {
        echo "FATAL: Couldn't load contents of '$filename'\n";
        die();
    }

    process($PHPVER, $BODY, $force_create_dir, $output_dir, $VC_VER, $should_cleanup);
}

main(array_splice($argv, 1));
