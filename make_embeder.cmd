@echo off
SETLOCAL

IF NOT EXIST "out/" MD "out/"

echo Running make_embedder.cmd
call clean.cmd
pushd "src"
call vcvarsall.bat
MSBuild.exe embeder.sln /p:Configuration="Release console" /p:Platform="Win32"
copy "Release console\embeder.exe" "../out/console.exe" || exit /b 1
popd

del /q /f ".\embeder2.exe" 2>nul
IF NOT EXIST "php.exe" echo Error, PHP not found. && exit /b 1
php.exe php/Embeder2Command.php new embeder2
php.exe php/Embeder2Command.php main embeder2 php/Embeder2Command.php
php.exe php/Embeder2Command.php add embeder2 out/console.exe out/console.exe

echo embeder2.exe built
ENDLOCAL