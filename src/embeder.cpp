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
#include "php_embed.h"
#include "ext/standard/php_standard.h"
#include "zend_smart_str.h"

#ifdef PHP_WIN32
#include <io.h>
#include <fcntl.h>
#endif


/* This callback is invoked as soon as the configuration hash table is
 * allocated so any INI settings added via this callback will have the lowest
 * precedence and will allow INI files to overwrite them.
 */
static void embeded_ini_defaults(HashTable *configuration_hash)
{
	zval ini_value;
	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("Embed SAPI error:"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("error_prepend_string"), &ini_value);

	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("E_ALL"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("error_reporting"), &ini_value);

	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("Off"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("display_errors"), &ini_value);

	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("On"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("log_errors"), &ini_value);

	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL("error.log"), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("error_log"), &ini_value);

}

/* Main */
int main(int argc, char** argv) {
	zval ret_value;
	int exit_status;
	char *eval_string = "include 'res:///PHP/LIB';";

	php_embed_module.ini_defaults = embeded_ini_defaults;
    //php_embed_module.php_ini_ignore = 0;
    //php_embed_module.php_ini_path_override = "./php.ini";

	/* Start PHP embed */
	PHP_EMBED_START_BLOCK(argc, argv); // PHP_EMBED_START_BLOCK(argc, argv)
 
	zend_first_try {
		PG(during_request_startup) = 0;

		/* Execute */
		if (zend_eval_string(eval_string, &ret_value, "main") == FAILURE) {
			php_printf("Failed to eval.\n");
		}

		/* Get Exit Status */
		exit_status = Z_LVAL(ret_value);
	} zend_catch {
	    /* Catch Exit status */
		exit_status = EG(exit_status);
	}
	zend_end_try();

	/* Stop PHP embed */
	PHP_EMBED_END_BLOCK(); // PHP_EMBED_END_BLOCK()

	/* Return exit status */
	return exit_status;
}


