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
#include "zend_smart_str.h"

/* This callback is invoked as soon as the configuration hash table is
 * allocated so any INI settings added via this callback will have the lowest
 * precedence and will allow INI files to overwrite them.
 */
static void embeded_ini_defaults(HashTable *configuration_hash)
{
	zval ini_value;
	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("Embed SAPI error:"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("error_prepend_string"), &ini_value);
}

/* Main */
int main(int argc, char** argv) {
	zval ret_value;
	int exit_status;
	char *eval_string = "include 'res:///PHP/LIB';";
    zend_string *ini_name;

	/* Start PHP embed */
	php_embed_init(argc, argv TSRMLS_CC); // PHP_EMBED_START_BLOCK(argc, argv)
    php_embed_module.php_ini_ignore = 0;
    php_embed_module.ini_defaults = embeded_ini_defaults;
    php_embed_module.php_ini_path_override = "./php.ini";

	zend_first_try {
		PG(during_request_startup) = 0;

		/* We are embeded */
		zend_eval_string("define('EMBEDED', 1);", &ret_value, "main" TSRMLS_CC);

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
	php_embed_shutdown(TSRMLS_C); // PHP_EMBED_END_BLOCK()

	/* Return exit status */
	return exit_status;
}


