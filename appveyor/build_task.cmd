@echo off
setlocal enableextensions enabledelayedexpansion

	cd /D %APPVEYOR_BUILD_FOLDER%
	if %errorlevel% neq 0 exit /b 3

	set STABILITY=staging
	set DEPS_DIR=%PHP_BUILD_CACHE_BASE_DIR%\deps-%PHP_REL%-%PHP_SDK_VC%-%PHP_SDK_ARCH%
	rem SDK is cached, deps info is cached as well
	echo Updating dependencies in %DEPS_DIR%
	cmd /c phpsdk_deps --update --no-backup --branch %PHP_REL% --stability %STABILITY% --deps %DEPS_DIR% --crt %PHP_BUILD_CRT%
	if %errorlevel% neq 0 exit /b 3

	rem Something went wrong, most likely when concurrent builds were to fetch deps
	rem updates. It might be, that some locking mechanism is needed.
	if not exist "%DEPS_DIR%" (
		cmd /c phpsdk_deps --update --force --no-backup --branch %PHP_REL% --stability %STABILITY% --deps %DEPS_DIR% --crt %PHP_BUILD_CRT%
	)
	if %errorlevel% neq 0 exit /b 3

	for %%z in (%ZTS_STATES%) do (
		set ZTS_STATE=%%z
		if "!ZTS_STATE!"=="enable" set ZTS_SHORT=ts
		if "!ZTS_STATE!"=="disable" set ZTS_SHORT=nts

		cd /d C:\projects\php-src

		cmd /c buildconf.bat --force

		if %errorlevel% neq 0 exit /b 3

		cmd /c configure.bat --!ZTS_STATE!-zts --disable-all --enable-embed --enable-object-out-dir=%PHP_BUILD_OBJ_DIR% --with-config-file-scan-dir=%APPVEYOR_BUILD_FOLDER%\build\modules.d --with-prefix=%APPVEYOR_BUILD_FOLDER%\build --with-php-build=%DEPS_DIR%

		if %errorlevel% neq 0 exit /b 3

		nmake /NOLOGO

		if %errorlevel% neq 0 exit /b 3

		nmake install

		if %errorlevel% neq 0 exit /b 3

		cd /d %APPVEYOR_BUILD_FOLDER%
        MSBuild.exe %APPVEYOR_BUILD_FOLDER%\src\embeder.sln /p:Configuration="Debug console" /p:Platform="Win32"
        ls C:\projects\php-src
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\php.exe" echo Error, PHP not found. && exit /b 1

        rem 7.2 version
        rem echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/dll/php_win32std.dll

        rem 7.4 version
        echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/php7.4.16/php_win32std.dll

        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/php7.4.16/php_win32std.dll

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo Make ini reference to download res dll
        type nul > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension_dir=".\ext" > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension=php_win32std.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
		echo Make embeder2.exe
        rem %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" -m
        copy "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe"
        copy "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\php\stub.exe"
        rem %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" new "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" main "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" add "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe" "out/console.exe"
        copy "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" %APPVEYOR_BUILD_FOLDER%\build\
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo Quick cleanup
        del %APPVEYOR_BUILD_FOLDER%\build\php.exe
        rd /S /Q %APPVEYOR_BUILD_FOLDER%\build\SDK\
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
		echo Zipping Assets...
        7z a %APPVEYOR_BUILD_FOLDER%\embedder.zip %APPVEYOR_BUILD_FOLDER%\build\*
        rem @todo add win32std extension & directory
		appveyor PushArtifact %APPVEYOR_BUILD_FOLDER%\embedder.zip -FileName embedder%PHP_REL%-%PHP_BUILD_CRT%-%PHP_SDK_ARCH%.zip
	)
endlocal
