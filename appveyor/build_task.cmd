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

        rem Get https://github.com/crispy-computing-machine/win32std/releases/download/dll/php_win32std.dll
        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/dll/php_win32std.dll

        rem nmake ini to download res dll
        type nul > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension_dir="./ext" > php.ini
        echo extension=php_win32std.dll >> php.ini

		rem @todo
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\php.exe" echo Error, PHP not found. && exit /b 1
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" -i

        %APPVEYOR_BUILD_FOLDER%\build\php.exe %APPVEYOR_BUILD_FOLDER%\php\embeder2.php new %APPVEYOR_BUILD_FOLDER%\php\embeder2.exe
        %APPVEYOR_BUILD_FOLDER%\build\php.exe %APPVEYOR_BUILD_FOLDER%\php\embeder2.php main embeder2 %APPVEYOR_BUILD_FOLDER%\php\embeder2.php
        %APPVEYOR_BUILD_FOLDER%\build\php.exe %APPVEYOR_BUILD_FOLDER%\php\embeder2.php add embeder2 %APPVEYOR_BUILD_FOLDER%\out\console.exe %APPVEYOR_BUILD_FOLDER%\out\console.exe

		rem embed.exe that was built
		echo Zipping Assets...
		rem 7z a embedder.zip C:\projects\embeder2\src\Debug console\embeder.exe
		rem 7z a embedder.zip C:\projects\embeder2\build\php7ts.dll
		rem 7z a embedder.zip C:\projects\embeder2\build\php.exe
		7z a embedder.zip C:\projects\*


		appveyor PushArtifact embedder.zip -FileName embedder.zip
	)
endlocal
