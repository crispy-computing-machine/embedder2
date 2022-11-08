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

#include <sapi/embed/php_embed.h>

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

	zval ret_value;
    int exit_status;

	char key[256] = {KEY};
	char application_source[] = {BODY};

    printf("Before: %s\n", application_source);
	rc4_encode_inplace(application_source, sizeof(application_source)-1, key);
    printf("After: %s\n", application_source);

	/* Start PHP embed */
	php_embed_init(argc, argv TSRMLS_CC); // PHP_EMBED_START_BLOCK(argc, argv)

	zend_first_try {
		PG(during_request_startup) = 0;

		/* Execute */
		zend_eval_string(application_source, &ret_value, "main" TSRMLS_CC);

		/* Get Exit Status */
		exit_status = Z_LVAL(ret_value);
	} zend_catch {
	    /* Catch Exit status */
		exit_status = EG(exit_status);
	}
	zend_end_try();

	/* Stop PHP embed */
	php_embed_shutdown(TSRMLS_C); // PHP_EMBED_END_BLOCK()

	/* Return exit status */
	return exit_status;
}