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
#include <TSRM.h>
#include <SAPI.h>
#include <zend_ini.h>
#include <php.h>
#include <php_ini.h>
#include <php_string.h>

int main(int argc, char **argv)
{
    int retval = SUCCESS;

    char *code = "include 'res:///PHP/LIB';";

    php_embed_module.php_ini_ignore = 0;
    php_embed_module.php_ini_path_override = "./php.ini";

    PHP_EMBED_START_BLOCK(argc,argv);

    zend_try {
        /* Try to execute something that will fail */
        /* We are embeded */
        zend_eval_string("define('EMBEDED', 1);", retval, "main" TSRMLS_CC);
        // zend_alter_ini_entry("extension_dir", 14, dir, strlen(dir), PHP_INI_ALL, PHP_INI_STAGE_ACTIVATE);
        // zend_alter_ini_entry("error_reporting", 16, "0", 1, PHP_INI_ALL, PHP_INI_STAGE_ACTIVATE);
        retval = zend_eval_string(
                                code,
                                 NULL,
                                  (char *)"" TSRMLS_CC
                                  ) == SUCCESS ? EXIT_SUCCESS : EXIT_FAILURE;
     } zend_catch {
         /* There was an error!
         * Try a different line instead */
        exit(retval);
     } zend_end_try();


    PHP_EMBED_END_BLOCK();

    exit(retval);
}