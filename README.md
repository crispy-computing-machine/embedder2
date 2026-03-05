# Embeder

Embeder uses the `phpembed` SAPI from PHP inorder to embed a version of PHP into itself.
From there is uses `win32std` to access a PHP file and run it from it's internal res.

## Limitations
Currently, there is one *major* limitation: It does not embed PHP *completely*, rather it ouputs PHP into a DLL, as of now called php7ts.dll
This is the same for extensions, shared ones must be loaded via `php.ini`

## Compiliation Requirements
See appveyor...

## Current release
Extensions are compiled statically - See [release notes](https://github.com/crispy-computing-machine/embedder2/releases) for extensions

Additional extensions included:
- Winbinder
- Win32ps

## Additional library's
- FreeImage - Use with Winbinder or FFI

## Bootstrap override and exit codes
By default, the embedded bootstrap include remains:

`include 'res:///PHP/LIB';`

You can override the include target through PHP INI with:

`embeder.bootstrap=/path/to/bootstrap.php`

`embeder.bootstrap` defaults to an empty value, so if it is unset/empty the embedder falls back to `res:///PHP/LIB`.

Exit-code behavior is deterministic:
- If `zend_eval_string(...)` fails, the process exits with failure (`EXIT_FAILURE`).
- If the eval result is an integer/long, that value is returned (clamped to the platform `int` range).
- If the eval result is not an integer, it is converted safely to a long and then clamped to `int` before returning.
- If no usable eval value is available, the process exits with failure (`EXIT_FAILURE`).

## Old Credits
- [Eric Colinet](mailto:e.colinet@laposte.net) - For the original concept & code!
- [Jared Allard](mailto:jaredallard@outlook.com) - Mantaining the project, improving the features, and more!
- See: http://wildphp.free.fr/wiki/doku.php?id=win32std:embeder

