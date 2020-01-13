# Embeder

Embeder uses the `phpembed` SAPI from PHP inorder to embed a version of PHP into itself.
From there is uses `win32std` to access a PHP file and run it from it's internal res.

## Limitations

Currently, there is one *major* limitation: It does not embed PHP *completely*, rather it ouputs PHP into a DLL, as of now called php5ts.dll

This is the same for extensions, shared ones must be loaded via `php-embed.ini`

## Compiliation Requirements
You'll need:

 * Microsoft Visual Studio 2012 for PHP 5.6, 2015 for PHP 7.
 * PHP Source code w/ it being compilied

## How-To Compile

1. Download this repo.
2. Open up `src/embeder.sln`
3. Modify the projects includes/libs path to reflect your php source location (must be compilied with atleast `--enable-embed`)
4. Run `make_embeder.cmd`

## FAQ

### How do I modify the includes/libs path?

Includes: `Project > embeder Properties > Configuration Properties > VC++ Directories > Include Directories`

Libraries: `Project > embeder Properties > Configuration Properties > VC++ Directories > Library Directories`

### How do I add extensions?

Modify `php-embed.ini`
     
    [PHP]
    extensions_dir='./'
    extension=php_someextension.dll

### I get afxres.h not found!

You're using an old version, `afxres.h` has been replaced by `winres.h`

## Credits

[Eric Colinet](mailto:e.colinet@laposte.net) - For the original concept & code!

[Jared Allard](mailto:jaredallard@outlook.com) - Mantaining the project, improving the features, and more!
