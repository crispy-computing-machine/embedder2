<?php

namespace Embeder;

use Exception;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

define("DOSHEADER",
	"a2Id/" .
	"x22/" .
	"vWinHeader/" .
	"x34/" .
	"vTableOffset"
);
define("DOSHEADER_SIZE", 62);

define("WINHEADER",
	"a2Signature/" .
	"x18/" .
	"vNT32/" .
	"x70/" .
	"CConsole"
);
define("WINHEADER_SIZE", 93);

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
        $resourceContents = file_get_contents($newFile);
        $this->message('add_file: ' . $newFile . ' to ' . $exeFile . ' as ' . $alias . ' [' . strtoupper($md5) . ']');
        return $this->update_resource($exeFile, 'PHP', $md5, $resourceContents);
    }

    /**
     * Change between console and windows type program
     *
     * @param string $file - Full path and extension
     * @param string $new_format
     */
    public function change_type($file, $new_format = 'GUI')
    {
        try {
            $this->convert($file, $new_format);
        } catch (Exception $e) {
            echo $e;
        }
    }

    /**
     * Convert console ot GUI
     *
     * @param $file
     * @param $new_format
     * @return void
     */
    public function convert($file, $new_format = 'GUI')
    {
        // Open the binary file in read-write mode
        $f = fopen($file, 'r+b');
        if (!$f) {
            $this->message("Can't open '$file'", true);

        }

        // Read the DOS header to find the PE header offset
        $type_record = unpack('Smagic/x58/Loffset', fread($f, 32*4));
        if ($type_record['magic'] != 0x5a4d) {
            $this->message("Not an MSDOS executable file", true);
        }

        // Seek to the PE header offset
        if (fseek($f, $type_record['offset']) != 0) {
            $this->message("Seeking error (+{$type_record['offset']})", true);
        }

        // Read the PE header to verify its format
        $pe_record = unpack('Lmagic/x16/Ssize', fread($f, 24));
        if ($pe_record['magic'] != 0x4550) {
            $this->message("PE header not found", true);
        }

        // After reading the PE header size
        $optionalHeaderSize = $pe_record['size'];
        $subsystemOffset = $type_record['offset'] + 24 + $optionalHeaderSize - 16; // Assuming Subsystem is always 16 bytes from the end of the optional header

        // Seek to the Subsystem field
        if (fseek($f, $subsystemOffset) != 0) {
            $this->message("Seeking error to Subsystem field", true);
        }

        // Determine the new subsystem type and write it
        $subsystemType = ($new_format === 'CONSOLE' ? 3 : 2);
        if (fwrite($f, pack('S', $subsystemType)) === false) {
            $this->message("Write error", true);
        }

        // Close the file
        fclose($f);

        $this->message("Subsystem updated successfully to " . ($subsystemType == 3 ? 'CONSOLE' : 'GUI'));
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
        $list = res_list_type($h, false);
        if ($list === FALSE) {
            $this->message("display_list: Can't list type", $error = true);
        }

        foreach ($list as $i => $iValue) {
            $this->message($iValue);
            $res = res_list($h, $iValue);
            foreach ($res as $jValue) {
                $this->message($jValue);
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
        $res = "res:///$section/$value" . ($lang ?? ('/' . $lang));
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
                echo "$description\n";
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
    public function build_dir($path, $main, $rootDirectory, $type = 'GUI')
    {

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
            $originalFullPath = $file->getRealpath();
            $relativePath = str_replace($rootDirectory, '', $originalFullPath);
            $embedPath = $this->unleadingSlash($this->linux_path($relativePath));

            // No hidden files, No git files, No directories
            if (strpos($embedPath, '.git') !== FALSE) {
                $this->message('build_dir: Skipping GIT: ' . $embedPath);
                continue;
            }
            if (strpos($embedPath, '.idea') !== FALSE) {
                $this->message('build_dir: Skipping IDEA: ' . $embedPath);
                continue;
            }
            if (basename($embedPath) === 'manifest.json') {
                $this->message('build_dir: Skipping MANIFEST: ' . $embedPath);
                continue;
            }
            if (is_dir($originalFullPath)) {
                $this->message('build_dir: Skipping DIRECTORY: ' . $embedPath);
                continue;
            }

            // Update resource
            $added = $this->add_file($path, $file, $embedPath);
            $filesAdded += $added;
            $failedFiles += !$added;
            $totalFiles++;
            $manifestFiles[] = [md5($embedPath) => $embedPath];
            if ($totalFiles % 100 === 0) {
                $this->message('build_dir: ' . $path . ' Total: ' . $totalFiles . '/Success: ' . $filesAdded . '/Failed: ' . $failedFiles);
            }

        }

        // update manifest
        file_put_contents($manifestFile = dirname($path) . DIRECTORY_SEPARATOR . 'manifest.json', '');
        if(file_put_contents($manifestFile, json_encode($manifestFiles))){
            $this->message('build_dir: Build manifest file complete!');
        }

        // Stats
        $this->message('build_dir: ' . $path . ' Total: ' . $totalFiles . '/Success: ' . $filesAdded . '/Failed: ' . $failedFiles);
        $this->message('build_dir: Build complete!');

    }

    /**
     * @param $path
     * @param $main
     * @param $rootDirectory
     * @return void
     */
    public function validate($path, $main, $rootDirectory)
    {

        $this->message('validate: ' . $path . ' Validating...');

        // Check Main
        // Check and Add missing resource (res_open() corrupts exe, and cant update exe while checking.... use temp!)
        $missingTmpFile = str_replace('.exe', '-missing.exe', $path);
        copy($path, $missingTmpFile);

        // Check PHP RES
        $buildFiles = $this->filesInDir($rootDirectory);
        $missingFiles = [];
        foreach ($buildFiles as $file) {

            $originalFullPath = $file->getRealpath();
            $relativePath = str_replace($rootDirectory, '', $originalFullPath);
            $embedPath = $this->unleadingSlash($this->linux_path($relativePath));

            // No hidden files, No git files, No directories
            if (strpos($embedPath, '.') === 0 || strpos($embedPath, '.git') !== FALSE || strpos($embedPath, 'php7') !== FALSE || is_dir($originalFullPath)) {
                continue;
            }

            // see if file is in "mising" tmp file, if not, add it to original exe...
            $res = strlen(@file_get_contents('res://' . $missingTmpFile . '/PHP/' . md5($embedPath)));
            $this->message('validate: ' . $path . ' Validating ' . $embedPath . ' -> ' . $res);
            if ($res === 0) {
                $this->message('validate: ' . $path . ' Missing, Adding ' . $embedPath . ' -> ' . $res);
                $missingFiles[] = [$path, $originalFullPath, $embedPath];
            }
        }

        // Missing files array to process
        $numberOfMissingFiles = count($missingFiles);
        $this->message('validate: ' . $path . ' Missing files ' . $numberOfMissingFiles);
        $missingAdded = 0;
        foreach ($missingFiles as $file) {
            [$path, $originalFullPath, $embedPath] = $file;
            $missingAdded += $this->add_file($path, $originalFullPath, $embedPath);
        }

        // Clear up temp file/handles
        unlink($missingTmpFile);

        $this->message('validate: ' . $path . ' Validation complete! ' . $missingAdded . '/' . $numberOfMissingFiles . ' missing resources added!');

    }

    /**
     * Helper: Memory safe directory/file iterator
     * @param $directory
     * @return Generator
     */
    public function filesInDir($directory)
    {

        if (!is_dir($directory)) {
            return;
        }

        yield from new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     *
     * Helper: Ensure path is a linux path
     * @param $path
     * @return string
     */
    public function linux_path($path)
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
    public function leadingSlash(string $path)
    {
        return $forwardSlash = chr(47) . $this->unleadingSlash($path);
    }

    /**
     * Helper: Add trailing slash to path if it doesn't have one
     * @param string $path
     * @return string
     */
    public function trailingSlash(string $path)
    {
        return $this->untrailingslash($path) . $forwardSlash = chr(47);
    }

    /**
     * Helper: Remove trailing slash from path
     * @param string $path
     * @return string
     */
    public function untrailingSlash(string $path)
    {
        return rtrim($path, $forwardSlash = chr(47));
    }

    /**
     * Helper: Remove trailing slash from path
     * @param string $path
     * @return string
     */
    public function unleadingSlash(string $path)
    {
        return ltrim($path, $forwardSlash = chr(47));
    }

    /**
     * Embeded info
     * @return bool
     */
    public function info()
    {
        return phpinfo();
    }
}

// New command with arguments
new Embeder2Command($argv);