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

		cmd /c configure.bat --!ZTS_STATE!-zts --enable-embed --enable-cli --enable-object-out-dir=%PHP_BUILD_OBJ_DIR% --with-config-file-scan-dir=%APPVEYOR_BUILD_FOLDER%\build\modules.d --with-prefix=%APPVEYOR_BUILD_FOLDER%\build --with-php-build=%DEPS_DIR%

		if %errorlevel% neq 0 exit /b 3

		nmake /NOLOGO

		if %errorlevel% neq 0 exit /b 3

		nmake install

		if %errorlevel% neq 0 exit /b 3
        IF EXIST "%APPVEYOR_BUILD_FOLDER%\build\php.exe" echo PHP has been built.

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
		cd /d %APPVEYOR_BUILD_FOLDER%
		set BUILT_FILE = %APPVEYOR_BUILD_FOLDER%\src\x64\Debug console\embeder.exe
        MSBuild.exe -detailedSummary %APPVEYOR_BUILD_FOLDER%\src\x64\embeder.sln /p:Configuration="Debug console" /p:Platform="x64"
        IF EXIST %BUILT_FILE% echo Embeder has been built.

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        rem 7.4 version
        echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/php_win32std-x64-7.4-ts-vc15-x64/php_win32std.dll
        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/php_win32std-x64-7.4-ts-vc15-x64/php_win32std.dll

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo Make ini reference to download res dll
        type nul > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension_dir=".\ext" > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension=php_win32std.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
		echo List build files and built php config
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" -m

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo Make embeder2.exe
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" new "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" main "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" add "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\src\x64\Debug console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\build\out\console.exe"
        copy %BUILT_FILE% "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe"
        copy %BUILT_FILE% "%APPVEYOR_BUILD_FOLDER%\build\stub.exe"

        echo ---------------------------------------------------------------------------------------------------------------------------------------------
        echo ---------------------------------------------------------------------------------------------------------------------------------------------
		echo Zipping Assets...
        7z a %APPVEYOR_BUILD_FOLDER%\embedder.zip %APPVEYOR_BUILD_FOLDER%\*
        rem @todo add win32std extension & directory
		appveyor PushArtifact %APPVEYOR_BUILD_FOLDER%\embedder.zip -FileName embedder%PHP_REL%-%PHP_BUILD_CRT%-%PHP_SDK_ARCH%.zip
	)
endlocal
