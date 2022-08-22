@echo off
SETLOCAL

IF NOT EXIST "out/" MD "out/"

echo Running make_embedder.cmd
call clean.cmd
pushd "src"
call vcvarsall.bat
MSBuild.exe embeder.sln /p:Configuration="Debug console" /p:Platform="Win32"
copy "Debug console\embeder.exe" "../out/console.exe" || exit /b 1
popd

del /q /f ".\embeder2.exe" 2>nul
IF NOT EXIST "php.exe" echo Error, PHP not found. && exit /b 1
php.exe php/embeder2.php new embeder2
php.exe php/embeder2.php main embeder2 php/embeder2.php
php.exe php/embeder2.php add embeder2 out/console.exe out/console.exe

echo embeder2.exe built
ENDLOCAL