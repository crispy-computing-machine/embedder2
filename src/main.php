<?php
try {
    echo $argv[1] . PHP_EOL;
    if(file_exists($argv[1])){
        include $argv[1];
    } else{
        echo 'No file provided...';
    }
} catch (Throwable $e){
    die($e);
}
