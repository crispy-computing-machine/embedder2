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
#include <limits.h>
#include <stdbool.h>
#include <string.h>

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

	ZVAL_NEW_STR(&ini_value, zend_string_init(ZEND_STRL(""), /* persistent */ 1));
	zend_hash_str_update(configuration_hash, ZEND_STRL("embeder.bootstrap"), &ini_value);
}

static int clamp_zend_long_to_int(zend_long value)
{
	if (value > INT_MAX) {
		return INT_MAX;
	}

	if (value < INT_MIN) {
		return INT_MIN;
	}

	return (int) value;
}

static bool build_eval_include_command(const char *target, char *buffer, size_t buffer_size)
{
	const char *prefix = "include '";
	const char *suffix = "';";
	size_t out = 0;
	const unsigned char *cursor;

	if (target == NULL || buffer == NULL || buffer_size == 0) {
		return false;
	}

	while (*prefix != '\0') {
		if (out + 1 >= buffer_size) {
			return false;
		}
		buffer[out++] = *prefix++;
	}

	cursor = (const unsigned char *) target;
	while (*cursor != '\0') {
		if ((*cursor == '\'' || *cursor == '\\') && out + 1 >= buffer_size) {
			return false;
		}

		if (*cursor == '\'' || *cursor == '\\') {
			buffer[out++] = '\\';
		}

		if (out + 1 >= buffer_size) {
			return false;
		}

		buffer[out++] = (char) *cursor;
		cursor++;
	}

	while (*suffix != '\0') {
		if (out + 1 >= buffer_size) {
			return false;
		}
		buffer[out++] = *suffix++;
	}

	buffer[out] = '\0';
	return true;
}

static const char *resolve_bootstrap_target(void)
{
	const char *ini_override = INI_STR("embeder.bootstrap");

	if (ini_override != NULL && ini_override[0] != '\0') {
		return ini_override;
	}

	return "res:///PHP/LIB";
}

/* Main */
int main(int argc, char** argv) {
	char eval_string[8192];
	const char *bootstrap_target;
	zval ret_value;
	int exit_status = EXIT_FAILURE;

	ZVAL_UNDEF(&ret_value);

	php_embed_module.ini_defaults = embeded_ini_defaults;
    //php_embed_module.php_ini_ignore = 0;
    //php_embed_module.php_ini_path_override = "./php.ini";

	/* Start PHP embed */
	PHP_EMBED_START_BLOCK(argc, argv); // PHP_EMBED_START_BLOCK(argc, argv)
 
	zend_first_try {
		PG(during_request_startup) = 0;

		bootstrap_target = resolve_bootstrap_target();

		/* Execute */
		if (!build_eval_include_command(bootstrap_target, eval_string, sizeof(eval_string)) ||
			zend_eval_string(eval_string, &ret_value, "main") == FAILURE) {
			php_printf("Failed to eval.\n");
			exit_status = EXIT_FAILURE;
		} else if (Z_TYPE(ret_value) == IS_LONG) {
			exit_status = clamp_zend_long_to_int(Z_LVAL(ret_value));
		} else if (!Z_ISUNDEF(ret_value)) {
			zval converted_value;
			ZVAL_COPY(&converted_value, &ret_value);
			convert_to_long(&converted_value);
			exit_status = clamp_zend_long_to_int(Z_LVAL(converted_value));
			zval_ptr_dtor(&converted_value);
		} else {
			exit_status = EXIT_FAILURE;
		}

		if (!Z_ISUNDEF(ret_value)) {
			zval_ptr_dtor(&ret_value);
			ZVAL_UNDEF(&ret_value);
		}
	} zend_catch {
	    /* Catch Exit status */
		exit_status = clamp_zend_long_to_int((zend_long) EG(exit_status));

		if (!Z_ISUNDEF(ret_value)) {
			zval_ptr_dtor(&ret_value);
			ZVAL_UNDEF(&ret_value);
		}
	}
	zend_end_try();

	/* Stop PHP embed */
	PHP_EMBED_END_BLOCK(); // PHP_EMBED_END_BLOCK()


	/* Return exit status */
	return exit_status;
}
