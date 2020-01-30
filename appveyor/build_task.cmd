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

		cmd /c configure.bat --disable-all --!ZTS_STATE!-zts --enable-embed --enable-cli --enable-object-out-dir=%PHP_BUILD_OBJ_DIR% --with-config-file-scan-dir=%APPVEYOR_BUILD_FOLDER%\build\modules.d --with-prefix=%APPVEYOR_BUILD_FOLDER%\build --with-php-build=%DEPS_DIR%

		if %errorlevel% neq 0 exit /b 3

		nmake /NOLOGO

		if %errorlevel% neq 0 exit /b 3

		nmake install

		if %errorlevel% neq 0 exit /b 3

		cd /d %APPVEYOR_BUILD_FOLDER%

        MSBuild.exe %APPVEYOR_BUILD_FOLDER%\src\embeder.sln /p:Configuration="Debug console" /p:Platform="Win32"

        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\php.exe" echo Error, PHP not found. && exit /b 1

        echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/dll/php_win32std.dll
        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/dll/php_win32std.dll

        echo Make ini reference to download res dll
        type nul > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension_dir="%APPVEYOR_BUILD_FOLDER%\build\ext" > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension=php_win32std.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

		echo Make embeder2.exe
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" -m
        copy "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe"
        rem %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" new "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" main "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" add "%APPVEYOR_BUILD_FOLDER%\php\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe" "/out/console.exe"

		echo Zipping Assets...

		echo Zipping: %APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe
		7z a embedder.zip "%APPVEYOR_BUILD_FOLDER%\src\Debug console\embeder.exe"

		echo Zipping: %APPVEYOR_BUILD_FOLDER%\build\php7ts.dll
		7z a embedder.zip "%APPVEYOR_BUILD_FOLDER%\build\php7ts.dll"

		echo Zipping: %APPVEYOR_BUILD_FOLDER%\build\php.exe
		7z a embedder.zip "%APPVEYOR_BUILD_FOLDER%\build\php.exe"

		appveyor PushArtifact embedder.zip -FileName embedder.zip
	)
endlocal
