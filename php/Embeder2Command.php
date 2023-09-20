<?php /** @noinspection PhpUnused */

namespace Embeder;

/**
 * Class Embeder2
 */
class Embeder2Command
{

    /**
     * Current argv
     *
     * @var array
     */
    private $argv;

    /**
     * Command line options
     *
     * @var array
     */
    private $actions = [
        'new' => ['new_file', ['path'], '[path] - Create Base EXE.'],
        'main' => ['add_main', ['path', 'file'], '[path, file] - Add main PHP file to exe.'],
        'add' => ['add_file', ['path', 'file', 'alias'], '[path, file,alias] - Add file to exe.'],
        'type' => ['change_type', ['path', 'type'], '[path, type] Change EXE type.'],
        'list' => ['display_list', ['path'], '[path] List contents of EXE.'],
        'view' => ['display_resource', ['path', 'section', 'value', 'lang'], '[path, section, value, lang] - View EXE file content.'],
        'build' => ['build_dir', ['path', 'main', 'directory', 'type'], '[path, main, directory, type] - Build EXE from folder content.'],
        'validate' => ['validate', ['path', 'main', 'directory', 'type'], '[path, main, directory, type] - Validate EXE from folder content.'],
        'info' => ['info', [], 'Show embeded php info [.\\' . PHP_BINARY . ' info > info.html]']
    ];

    /**
     * @param $argv
     */
    public function __construct($argv)
    {

        // Script args
        $this->argv = $argv;

        // Ensure win32std installed
        if (!extension_loaded('win32std')) {
            $this->message('win32std not found.', $error = true);
        }

        // Check valid action passed into script, if bad...exit
        $this->checkUsage();

        // Check arguments for action, Run command with passed in arguments
        $this->run();

    }

    /**
     * Help banner
     */
    public function banner()
    {
        echo strtolower(basename($this->argv[0])) . ' ' . (defined('EMBEDED') ? '(embeded) ' : '') . '- Powered by PHP version ' . PHP_VERSION . PHP_EOL;
    }

    /**
     * Message output
     *
     * @param $message
     * @param bool $error
     */
    public function message($message, $error = false)
    {
        if ($error) {
            $message = 'ERROR: ' . $message;
        }
        echo $message . PHP_EOL;
    }

    /**
     * Include resource file if we are embeded
     *
     * @param $file
     * @param bool $force
     * @return string
     */
    public function resource_include($file, $force = false)
    {
        return $force || defined('EMBEDED') ? 'res:///PHP/' . md5($file) : $file;
    }

    /**
     * Create a new EXE
     * @param $file - Full path and extension
     * @param string $type
     */
    public function new_file($file, $type = 'console')
    {

        // Relative path to stub file - should always be in: out/console.exe|window.exe
        $base_exe = $this->resource_include('out/' . $type . '.exe');

        // Check the exe doesnt already exist
        $this->check_exe($file, true);

        // Copy base exe to current working directory
        if (!copy($base_exe, $file)) {
            $this->message("new_file: Can't create '$file'", $error = true);
        }
        $this->message("new_file: '$file' created");
    }

    /**
     * Add main bootstrap file for application to EXE
     *
     * @param $exeFile - Full path and extension
     * @param $newFile - Full path and extension
     * @return bool
     */
    public function add_main($exeFile, $newFile)
    {

        // if it doesnt exist fail
        $this->check_exe($exeFile, false);

        $this->message('add_main: ' . $newFile . ' to ' . $exeFile);
        return $this->update_resource($exeFile, 'PHP', 'RUN', file_get_contents($newFile), 1036);
    }

    /**
     * Add a file to an existing EXE
     *
     * @param $exeFile - Full path and extension
     * @param $newFile - Full path and extension
     * @param $alias
     * @return bool
     */
    public function add_file($exeFile, $newFile, $alias)
    {

        $this->check_exe($exeFile);
        $md5 = md5($alias);
        $resourceContents = $this->composerFileCheck($alias, file_get_contents($newFile));
        $this->message('add_file: ' . $newFile . ' to ' . $exeFile . ' as ' . $alias . ' [' . strtoupper($md5) . ']');
        return $this->update_resource($exeFile, 'PHP', $md5, $resourceContents);
    }

    /**
     * Change between console and windows type program
     *
     * @param $exeFile - Full path and extension
     * @param $type
     */
    public function change_type($exeFile, $type)
    {

        $types = array('CONSOLE', 'WINDOWS');

        // Check if EXE exists
        $this->check_exe($exeFile, false);

        // Check TYPE paramater
        if (!in_array($new_format = strtoupper($type), $types)) {
            $this->message('change_type: Type not supported', $error = true);
        }

        // Open file handle in r+b mode
        $f = fopen($exeFile, 'r+b');

        // Change EXE type
        $type_record = unpack('Smagic/x58/Loffset', fread($f, 32 * 4));
        if ($type_record['magic'] != 0x5a4d) {
            $this->message('change_type: Not an MSDOS executable file', $error = true);
        }
        if (fseek($f, $type_record['offset'], SEEK_SET) != 0) {
            $this->message("change_type: Seeking error (+{$type_record['offset']})", $error = true);
        }

        // PE Record
        $pe_record = unpack('Lmagic/x16/Ssize', fread($f, 24));
        if ($pe_record['magic'] != 0x4550) {
            $this->message('change_type: PE header not found', $error = true);
        }
        if ($pe_record['size'] != 224) {
            $this->message('change_type: Optional header not in NT32 format', $error = true);
        }
        if (fseek($f, $type_record['offset'] + 24 + 68, SEEK_SET) != 0) {
            $this->message("change_type: Seeking error (+{$type_record['offset']})", $error = true);
        }
        if (fwrite($f, pack('S', $new_format === 'CONSOLE' ? 3 : 2)) === false) {
            $this->message('change_type: Write error', $error = true);
        }

        // Close file handle
        fclose($f);

        $this->message("change_type: File type changed too '" . $new_format . "'");
    }

    /**
     * Update existing resource file within an EXE
     *
     * @param $exeFile - Full path and extension
     * @param $section
     * @param $name
     * @param $data
     * @param null $lang
     * @return bool
     */
    public function update_resource($exeFile, $section, $name, $data, $lang = 0)
    {

        // Path to resource
        $res = "res:///$section/$name";

        // Set resource
        $reset = res_set($exeFile, $section, $name, $data, $lang);
        if (!$reset) {
            $this->message("update_resource: Can't update " . $res, $error = true);
        }
        $this->message("update_resource: '" . $exeFile . "' -> '" . $res . "' (" . strlen($data) . ' bytes)');

        // @todo debug timing of adding resources? based on load of CPU/DISK?
        #usleep(500000);
        usleep(1000000);

        return $reset;
    }

    /**
     * Check if exe does or doesnt exist
     *
     * @param $exe - Full path and extension
     * @param bool $exists
     */
    public function check_exe($exe, $exists = false)
    {
        if ($exists) {
            if (file_exists($exe)) {
                $this->message("check_exe: '$exe' already exists.", $error = true);
            }
        } else {
            if (!file_exists($exe)) {
                $this->message("check_exe: '$exe' doesn't exist.", $error = true);
            }
        }
    }

    /**
     * List Resources of EXE file
     *
     * @param $exeFile - Full path and extension
     */
    public function display_list($exeFile)
    {

        $this->check_exe($exeFile);

        $h = res_open($exeFile);
        if (!$h) {
            $this->message("display_list: can't open '$exeFile'", $error = true);
        }

        $this->message("display_list: Res list of '$exeFile': ");
        $list = res_list_type($h, true);
        if ($list === FALSE) {
            $this->message("display_list: Can't list type", $error = true);
        }

        foreach ($list as $i => $iValue) {
            echo $iValue;
            $res = res_list($h, $list[$i]);
            foreach ($res as $jValue) {
                echo "\t" . $jValue;
            }
        }
        res_close($h);
    }


    /**
     * Display a resource from an EXE
     *
     * @param $exeFile -- Full path and extension
     * @param $section
     * @param $value
     * @param string $lang
     */
    public function display_resource($exeFile, $section, $value, $lang = '')
    {

        $this->check_exe($exeFile);
        $res = "res:///{$section}/{$value}" . ($lang ?? ('/' . $lang));
        $this->message(str_repeat('-', 10) . ' display_resource:' . $res . ' ' . str_repeat('-', 10));
        $this->message(file_get_contents($res));
        $this->message(str_repeat('-', 10) . ' End ' . str_repeat('-', 10));
    }

    /**
     * Check argument passed in is valid action for embed2
     *
     */
    public function checkUsage()
    {
        if (!isset($this->argv[1])) {
            $this->banner();

            $this->message("Usage: {$this->argv[0]} action [parameters...]");
            $this->message('Available commands:');

            // Print out num args, command, desc with formatting
            foreach ($this->actions as $action => $actionData) {
                $i = 0;
                $cl = strlen($action);
                $cld = 11 - $cl;
                $description = $actionData[2];

                echo $action;
                while ($i !== $cld) {
                    ++$i;
                    echo ' ';
                }
                echo "{$description}\n";
            }

            exit(1);
        }
    }

    /**
     * Check arguments are correct and run command if they are
     */
    public function run()
    {
        foreach ($this->actions as $action => $actionData) {

            if ($action == $this->argv[1]) {
                $temp = $this->argv;

                // Shift array - Remove script.php arg & remove function name and count number of parameters (whats left in the array)
                array_shift($temp);
                array_shift($temp);
                if (count($temp) !== count($actionData[1])) {
                    $this->message('Bad number of parameters, ' . count($temp) . ' provided, ' . count($actionData[1]) . ' needed! "' . $action . '"  needs: ' . implode(', ', $actionData[1]), $error = true);
                }

                // Call Function
                call_user_func_array([$this, $actionData[0]], $temp);

                // Exit with zero code
                exit(0);
            }
        }
        $this->message("Unknown command '" . $this->argv[1] . "'", $error = true);
    }


    /**
     * @param $path - .\full\path\to\some.exe
     * @param $main - .\full\path\to\main.php
     * @param $rootDirectory - .\full\path\to\project\
     * @return void
     */
    public function build_dir($path, $main, $rootDirectory, $type = 'CONSOLE')
    {

        file_put_contents('manifest.json', '');
        $this->message('build_dir: Creating new exe ' . $path . ' from  directory ' . $rootDirectory . ', Main file: ' . $main . ' (Type:' . $type . ')');
        $this->new_file($path);
        $this->add_main($path, $main);

        // Stats
        $totalFiles = 0;
        $filesAdded = 0;
        $failedFiles = 0;
        $manifestFiles = [];
        $buildFiles = $this->filesInDir($rootDirectory);
        foreach ($buildFiles as $file) {
            $originalFullPath = $file;
            $relativePath = str_replace($rootDirectory, '', $originalFullPath);
            $embedPath = $this->unleadingSlash($this->linux_path($relativePath));

            // No hidden files, No git files, No directories // @todo pass as arguments to embedder
            if (strpos($embedPath, '.') === 0 || strpos($embedPath, '.git') !== FALSE || strpos($embedPath, 'php7') !== FALSE || is_dir($originalFullPath)) {
                continue;
            }

            // Update resource
            $added = $this->add_file($path, $originalFullPath, $embedPath);
            $filesAdded += $added;
            $failedFiles += !$added;
            $totalFiles++;
            $manifestFiles[] = [$originalFullPath, $embedPath];
            if ($totalFiles % 100 === 0) {
                $this->message('build_dir: ' . $path . ' Total: ' . $totalFiles . '/Success: ' . $filesAdded . '/Failed: ' . $failedFiles);
            }

        }

        // update manifest
        file_put_contents('manifest.json', json_encode($manifestFiles));

        $this->message('build_dir: ' . $path . ' Total: ' . $totalFiles . '/Success: ' . $filesAdded . '/Failed: ' . $failedFiles);
        $this->change_type($path, $type);

        $this->message('build_dir: Build complete!');

    }

    /**
     * // @param $path
     * @param $main
     * @param $rootDirectory
     * @param $type
     * @return void
     * @todo validate exe and add any missing resources
     */
    public function validate($path, $main, $rootDirectory, $type = 'CONSOLE')
    {

        $this->message('build_dir: ' . $path . ' Validating...');

        // Check Main
        // Check and Add missing resource (res_open() corrupts exe, and cant update exe while checking.... use temp!)
        $missingTmpFile = str_replace('.exe', '-missing.exe', $path);
        copy($path, $missingTmpFile);

        // Check PHP RES
        $buildFiles = $this->filesInDir($rootDirectory);
        $missingFiles = [];
        foreach ($buildFiles as $file) {

            $originalFullPath = $file;
            $relativePath = str_replace($rootDirectory, '', $originalFullPath);
            $embedPath = $this->unleadingSlash($this->linux_path($relativePath));

            // No hidden files, No git files, No directories
            if (strpos($embedPath, '.') === 0 || strpos($embedPath, '.git') !== FALSE || strpos($embedPath, 'php7') !== FALSE || is_dir($originalFullPath)) {
                continue;
            }

            // see if file is in "mising" tmp file, if not, add it to original exe...
            $res = strlen(file_get_contents('res://' . $missingTmpFile . '/PHP/' . md5($embedPath)));
            $this->message('build_dir: ' . $path . ' Validating ' . $embedPath . ' -> ' . $res);
            if ($res === 0) {
                $this->message('build_dir: ' . $path . ' Missing, Adding ' . $embedPath . ' -> ' . $res);

                // Can't add to it if its open, so we are reading from a temp file instead
                $this->add_file($path, $originalFullPath, $embedPath);
                $missingFiles[] = [$path, $originalFullPath, $embedPath];
            }
        }

        // Missing files array to process
        $numberOfMissingFiles = count($missingFiles);
        $this->message('build_dir: ' . $path . ' Missing files ' . $numberOfMissingFiles);
        $missingAdded = 0;
        foreach ($missingFiles as $file) {
            [$path, $originalFullPath, $embedPath] = $file;
            $missingAdded += $this->add_file($path, $originalFullPath, $embedPath);
        }

        // Clear up temp file/handles
        unlink($missingTmpFile);

        $this->message('build_dir: ' . $path . ' Validation complete! ' . $missingAdded . '/' . $numberOfMissingFiles . ' missing resources added!');

    }

    /**
     * Helper: Memory safe directory/file iterator
     * @param $directory
     * @param $fileExtension
     * @return \Generator
     */
    function filesInDir($directory)
    {

        if (!is_dir($directory)) {
            return;
        }

        yield from new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * @return string
     * @todo Override composer autoloader class/methods to "embed" https://github.com/ircmaxell/PhpGenerics/blob/master/lib/PhpGenerics/Autoloader.php
     */
    function composerFileCheck($fileName, $fileContents)
    {

        // composer specific includes
        if (strpos($fileName, 'autoload_real.php') !== FALSE || strpos($fileName, 'autoload.php') !== FALSE) {
            $fileContents = str_replace("require __DIR__ . '/ClassLoader.php';", "require 'vendor/composer/ClassLoader.php';", $fileContents);
            $fileContents = str_replace("require __DIR__ . '/platform_check.php';", "require 'vendor/composer/platform_check.php';", $fileContents);
            $fileContents = str_replace("require __DIR__ . '/autoload_static.php';", "require 'vendor/composer/autoload_static.php';", $fileContents);
            $fileContents = str_replace("\$map = require __DIR__ . '/autoload_namespaces.php';", "\$map = require 'vendor/composer/autoload_namespaces.php';", $fileContents);
            $fileContents = str_replace("\$map = require __DIR__ . '/autoload_psr4.php';", "\$map = require 'vendor/composer/autoload_psr4.php';", $fileContents);
            $fileContents = str_replace("\$classMap = require __DIR__ . '/autoload_classmap.php';", "\$classMap = require 'vendor/composer/autoload_classmap.php';", $fileContents);
            $fileContents = str_replace("require_once __DIR__ . '/composer/autoload_real.php';", "require_once 'vendor/composer/autoload_real.php';", $fileContents);
            $fileContents = str_replace("\$includeFiles = require __DIR__ . '/autoload_files.php';", "\$includeFiles = require 'vendor/composer/autoload_files.php';", $fileContents);
        }

        // Main include function for compose
        #if (strpos($fileName, 'ClassLoader.php') !== FALSE) {
        #    $fileContents = str_replace("include \$file;", "include \$file;", $fileContents);
        #}

        // Replace dunder path constants
        if (strpos($fileName, 'autoload_static.php') !== FALSE) {
            $fileContents = str_replace("__DIR__ . '/../..' . '/", "'", $fileContents);
            $fileContents = str_replace("__DIR__ . '/..' . '/", "'", $fileContents);
        }

        return $fileContents;

    }

    /**
     *
     * Helper: Ensure path is a linux path
     * @param $path
     * @return string
     */
    function linux_path($path)
    {
        $path = str_replace($backslash = chr(92), $forwardSlash = chr(47), $path);
        $path = str_replace($forwardSlash . $forwardSlash, $forwardSlash, $path); // remove doubles
        return $path;
    }

    /**
     * Helper: Add trailing slash to path if it doesn't have one
     * @param string $path
     * @return string
     */
    function leadingSlash(string $path)
    {
        return $forwardSlash = chr(47) . $this->unleadingSlash($path);
    }

    /**
     * Helper: Add trailing slash to path if it doesn't have one
     * @param string $path
     * @return string
     */
    function trailingSlash(string $path)
    {
        return $this->untrailingslash($path) . $forwardSlash = chr(47);
    }

    /**
     * Helper: Remove trailing slash from path
     * @param string $path
     * @return string
     */
    function untrailingSlash(string $path)
    {
        return rtrim($path, $forwardSlash = chr(47));
    }

    /**
     * Helper: Remove trailing slash from path
     * @param string $path
     * @return string
     */
    function unleadingSlash(string $path)
    {
        return ltrim($path, $forwardSlash = chr(47));
    }

    /**
     * Embeded info
     * @return bool
     */
    public function info()
    {
        /** @noinspection ForgottenDebugOutputInspection */
        return phpinfo();
    }


}

// New command with arguments
new Embeder2Command($argv);