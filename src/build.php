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
#ifdef PHP_WIN32
#include <io.h>
#include <fcntl.h>
#endif

#include <sapi/embed/php_embed.h>

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

function make_bat_file($DIR, $inc)
{
    // /Fe is file output EXE
    $BATFILE = '
    if not defined VisualStudioVersion call "C:\Program Files (x86)\Microsoft Visual Studio 15.0\VC\vcvarsall.bat"
    cl '.$DIR . DIRECTORY_SEPARATOR.'myapp.c /MD /nologo /I '.$inc.'main /I '.$inc.'TSRM /I  '.$inc.'Zend /I '.$inc.'ext\standard /I '.$inc.'sapi\embed /I '.$inc.' /I '.$inc.'\Release_TS '.$DIR.'\lib\php7embed.lib '.$DIR.'\lib\php7ts.lib  /Fe'.dirname($DIR) . DIRECTORY_SEPARATOR.'output.exe';
    return $BATFILE;
}

function process($BODY, $output_dir, $inc)
{

    // Temporary C file
    $key = make_key();
    $CFILE = make_c_file($BODY, $key);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR . 'myapp.c', $CFILE);

    // Temporary bat file
    $BATFILE = make_bat_file($output_dir, $inc);
    file_put_contents($output_dir.DIRECTORY_SEPARATOR. 'vsbuild.cmd', $BATFILE);

    // Run bat file
    #passthru($output_dir . DIRECTORY_SEPARATOR . 'vsbuild.cmd');

}


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

main(array_splice($argv, 1));
