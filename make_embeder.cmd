@echo off
SETLOCAL

IF NOT EXIST "out/" MD "out/"
popd

IF NOT EXIST "php.exe" echo Error, PHP not found. && exit /b 1
php.exe php/embeder2.php new embeder2
php.exe php/embeder2.php main embeder2 php/embeder2.php
php.exe php/embeder2.php add embeder2 out/console.exe out/console.exe

echo Done
ENDLOCAL