//========================================================================
//       Embeder - Make an executable Windows-binary file from a PHP script
//
//       License : PHP License (http://www.php.net/license/3_0.txt)
//       Author : Eric Colinet <e dot colinet at laposte dot net>
//       http://wildphp.free.fr/wiki/doku?id=win32std:embeder
//========================================================================

/* PHP Conf */
#ifndef ZEND_WIN32
#define ZEND_WIN32
#endif
#ifndef PHP_WIN32
#define PHP_WIN32
#endif
#ifndef ZTS
#define ZTS 1
#endif
#ifndef ZEND_DEBUG
#define ZEND_DEBUG 0
#endif

/* PHP Includes */
#include <php_embed.h>

/* Main */
int main(int argc, char** argv) {
	zval ret_value;
	int exit_status;
	char *eval_string = "include 'res:///PHP/LIB';";

	/* Start PHP embed */
	php_embed_init(argc, argv TSRMLS_CC);

    php_embed_module.php_ini_ignore = 0;
    php_embed_module.php_ini_path_override = "./php.ini";
    // zend_alter_ini_entry("extension_dir", 14, dir, strlen(dir), PHP_INI_ALL, PHP_INI_STAGE_ACTIVATE);
    // zend_alter_ini_entry("error_reporting", 16, "0", 1, PHP_INI_ALL, PHP_INI_STAGE_ACTIVATE);


	zend_first_try {
		PG(during_request_startup) = 0;

		/* We are embeded */
		zend_eval_string("define('EMBEDED', 1);", &ret_value, "main" TSRMLS_CC);
        zend_eval_string("function embedded($file, $force = false) { return $force || defined('EMBEDDED') ? 'res:///PHP/' . md5($file) : $file; };", NULL, "main" TSRMLS_CC);

		/* Execute */
		zend_eval_string(eval_string, &ret_value, "main" TSRMLS_CC);

		/* Get Exit Status */
		exit_status= Z_LVAL(ret_value);
	}

	/* Catch Exit status */
	zend_catch {
		exit_status = EG(exit_status);
	}
	zend_end_try();

	/* Stop PHP embed */
	php_embed_shutdown(TSRMLS_C);

	/* Return exit status */
	return exit_status;
}
