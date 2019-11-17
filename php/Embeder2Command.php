<?php /** @noinspection PhpUnused */

namespace Embeder;

/**
 * Class Embeder2
 */
class Embeder2Command {

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
        'new'  => ['new_file',         ['path'], 'Create Base EXE'],
        'main' => ['add_main',         ['path', 'file'], 'Add main PHP file to exe'],
        'add'  => ['add_file',         ['path', 'file','alias'], 'Add file to exe'],
        'type' => ['change_type',      ['path', 'type'], 'Change EXE type.'],
        'list' => ['display_list',     ['path'], 'List contents of EXE'],
        'view' => ['display_resource', ['path', 'section', 'value', 'lang'], 'View EXE file content'],
    ];


    public function __construct($argv)
    {

        // Production/debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('error_log', 'error.log');

        // Script args
        $this->argv = $argv;

        // Ensure win32std installed
        if(!extension_loaded('win32std')) {
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
    public function banner() {
        echo strtolower(basename($this->argv[0])). ' ' .(defined('EMBEDED')?'(embeded) ':'').'- Powered by PHP version '. PHP_VERSION . PHP_EOL;
    }

    /**
     * Message output
     *
     * @param $message
     * @param bool $error
     */
    public function message($message, $error = false) {
        echo $message . PHP_EOL;
        if($error){
            die();
        }
    }

    /**
     * Include resource file if we are embeded
     *
     * @param $file
     * @param bool $force
     * @return string
     */
    public function resource_include($file, $force=false) {
        return $force || defined('EMBEDED') ? 'res:///PHP/' . md5($file) : $file;
    }

    /**
     * Create a new EXE
     * @param $file - Full path and extension
     * @param string $type
     */
    public function new_file($file, $type= 'console') {

        // Relative path to stub file - should always be in: out/console.exe|window.exe
        $base_exe = $this->resource_include('out/' . $type . '.exe');

        // Check the exe doesnt already exist
        $this->check_exe($file, true);

        // Copy base exe to current working directory
        if(!copy($base_exe, $file)){
            $this->message("Can't create '$file'", $error = true);
        }
        $this->message("'$file' created");
    }

    /**
     * Add main bootstrap file for application to EXE
     *
     * @param $exeFile - Full path and extension
     * @param $newFile - Full path and extension
     */
    public function add_main($exeFile, $newFile) {

        // if it doesnt exist fail
        $this->check_exe($exeFile, false);

        $this->message('Adding main file ' . $newFile . ' to ' . $exeFile);
        $this->update_resource($exeFile, 'PHP', 'RUN', file_get_contents($newFile), 1036);
    }

    /**
     * Add a file to an existing EXE
     *
     * @param $exeFile - Full path and extension
     * @param $newFile - Full path and extension
     * @param $alias
     */
    public function add_file($exeFile, $newFile, $alias) {

        $this->check_exe($exeFile);
        $md5 = md5($alias);
        $this->message('Adding aditional file ' . $newFile . ' to ' . $exeFile . ' as ' . $md5);
        $this->update_resource($exeFile, 'PHP', $md5, file_get_contents($newFile));
    }

    /**
     * Change between console and windows type program
     *
     * @param $exeFile - Full path and extension
     * @param $type
     */
    public function change_type($exeFile, $type) {

        $types = array('CONSOLE', 'WINDOWS');

        // Check if EXE exists
        $this->check_exe($exeFile, false);

        // Check TYPE paramater
        if(!in_array($new_format = strtoupper($type), $types)) {
            $this->message('Type not supported', $error = true);
        }

        // Open file handle in r+b mode
        $f = fopen($exeFile, 'r+b');

        // Change EXE type
        $type_record = unpack('Smagic/x58/Loffset', fread($f, 32*4));
        if($type_record['magic'] != 0x5a4d ) {
            $this->message('Not an MSDOS executable file', $error = true);
        }
        if(fseek($f, $type_record['offset'], SEEK_SET) != 0) {
            $this->message("Seeking error (+{$type_record['offset']})", $error = true);
        }

        // PE Record
        $pe_record = unpack('Lmagic/x16/Ssize', fread($f, 24));
        if($pe_record['magic'] != 0x4550 ) {
            $this->message('PE header not found', $error = true);
        }
        if($pe_record['size'] != 224 ) {
            $this->message('Optional header not in NT32 format', $error = true);
        }
        if(fseek($f, $type_record['offset'] + 24 + 68, SEEK_SET) != 0) {
            $this->message("Seeking error (+{$type_record['offset']})", $error = true);
        }
        if(fwrite($f, pack('S', $new_format === 'CONSOLE' ? 3:2 )) === false) {
            $this->message('Write error', $error = true);
        }

        // Close file handle
        fclose($f);

        $this->message("File type changed too '".$new_format."'");
    }

    /**
     * Update existing resource file within an EXE
     *
     * @param $exeFile - Full path and extension
     * @param $section
     * @param $name
     * @param $data
     * @param null $lang
     */
    public function update_resource($exeFile, $section, $name, $data, $lang=null) {

        // Path to resource
        $res = "res:///$section/$name";

        // Set resource
        if(!res_set($exeFile, $section, $name, $data, $lang)) {
            $this->message("Can't update " . $res, $error = true);
        }

        $this->message("Updated '" . $exeFile . "' -> '" . $res . "' (" . strlen($data) . ' bytes)');
    }

    /**
     * Check if exe does or doesnt exist
     *
     * @param $exe - Full path and extension
     * @param bool $exists
     */
    public function check_exe($exe, $exists=false) {
        if($exists) {
            if(file_exists($exe)) {
                $this->message("'$exe' already exists.", $error = true);
            }
        } else {
            if(!file_exists($exe)) {
                $this->message("'$exe' doesn't exist.", $error = true);
            }
        }
    }

    /**
     * List Resources of EXE file
     *
     * @param $exeFile - Full path and extension
     */
    public function display_list($exeFile) {

        $this->check_exe($exeFile);

        $h = res_open($exeFile);
        if(!$h) {
            $this->message("can't open '$exeFile'", $error = true);
        }

        $this->message( "Res list of '$exeFile': ");
        $list = res_list_type($h, true);
        if( $list === FALSE ){
            $this->message( "Can't list type", $error = true);
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
    public function display_resource($exeFile, $section, $value, $lang='') {

        $this->check_exe($exeFile);
        $res= "res:///{$section}/{$value}" . ($lang ?? ('/'.$lang));
        $this->message(str_repeat('-', 10) . ' Displaying:' . $res . ' ' . str_repeat('-', 10));
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

            $this->message("Usage: {$this->argv[0]} action [params...]");
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
                    $this->message('Bad number of parameters, ' . count($temp) . ' provided, ' . count($actionData[1]) . ' needed! "'. $action .'"  needs: ' . implode(', ', $actionData[1]), $error = true);
                }

                // Call Function
                call_user_func_array([$this,$actionData[0]], $temp);

                // Exit with zero code
                exit(0);
            }
        }
        $this->message("Unknown command '" . $this->argv[1] . "'", $error = true);
    }

}

// New command with arguments
new Embeder2Command($argv);