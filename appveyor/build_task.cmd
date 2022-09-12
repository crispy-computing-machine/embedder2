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

    set BUILD_TYPE=Debug

	for %%z in (%ZTS_STATES%) do (
		set ZTS_STATE=%%z
		if "!ZTS_STATE!"=="enable" set ZTS_SHORT=ts
		if "!ZTS_STATE!"=="disable" set ZTS_SHORT=nts

		cd /d C:\projects\php-src

		cmd /c buildconf.bat --force
		if %errorlevel% neq 0 exit /b 3

		 cmd /c configure.bat --!ZTS_STATE!-zts --enable-object-out-dir=%PHP_BUILD_OBJ_DIR% --with-config-file-scan-dir=%APPVEYOR_BUILD_FOLDER%\build\modules.d --with-prefix=%APPVEYOR_BUILD_FOLDER%\build --with-php-build=%DEPS_DIR% ^
		--enable-snapshot-build ^
		--enable-embed ^
		--disable-debug-pack ^
		--enable-com-dotnet=shared ^
		--without-analyzer ^
		--disable-test-ini ^
		--disable-cgi ^
		--enable-com-dotnet ^
		--with-bz2=static ^
		--with-dba=static ^
		--with-curl=static ^
		--with-enchant=static ^
		--enable-mbstring=static ^
		--enable-exif=static ^
		--with-ffi=static ^
		--enable-fileinfo=static ^
		--enable-filter=static ^
		--enable-ftp=static ^
		--with-gd=static ^
		--with-gettext=static ^
		--with-gmp=static ^
		--with-imap=static ^
		--enable-intl=static ^
		--with-ldap=no ^
		--with-mysqli=static ^
		--enable-odbc=static ^
		--with-openssl=static ^
		--with-pdo-mysql=static ^
		--with-pdo-sqlite=static ^
		--with-pdo-firebird=no ^
		--with-pdo-odbc=static ^
		--with-pdo-pgsql=static ^
		--with-pgsql=static ^
		--enable-sockets=static ^
		--with-sodium=static ^
		--with-sqlite3=static ^
		--with-tidy=static ^
		--with-xmlrpc=static ^
		--with-xsl=static ^
		--enable-zip=static ^
		--enable-shmop=static ^
		--with-snmp=no ^
		--enable-soap=static ^
		--enable-sysvshm=static ^
		--enable-zend-test=no ^

		if %errorlevel% neq 0 exit /b 3

        rem Suppress logo output of nmake
		nmake /NOLOGO
		if %errorlevel% neq 0 exit /b 3

        nmake snap
		rem nmake install
		if %errorlevel% neq 0 exit /b 3

		cd /d %APPVEYOR_BUILD_FOLDER%

        MSBuild.exe %APPVEYOR_BUILD_FOLDER%\src\embeder.sln /p:Configuration="%BUILD_TYPE% console" /p:Platform="Win32"
        if %errorlevel% neq 0 exit /b 3

        echo Copying built files into build/asset dir C:\obj\Release_TS\php-7.*.*-dev-Win32-vc15-x86.zip
        rem xcopy "%PHP_BUILD_OBJ_DIR%\Release_TS\php-7.*.*-dev-Win32-vc15-x86.zip" "%APPVEYOR_BUILD_FOLDER%\build\" /s /i /Y
        powershell -NoP -NonI -Command "Expand-Archive -Force -Path '%PHP_BUILD_OBJ_DIR%\Release_TS\php-7.*.*-dev-Win32-vc15-x86.zip' -DestinationPath '%APPVEYOR_BUILD_FOLDER%\build\'"
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\php-win.exe" echo Error, PHP not found. && exit /b 1

        rem win32std
        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/latest/php_win32std.dll
        wget -O  "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/latest/php_win32std.dll -Q
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" echo Error, php_win32std not found. && exit /b 1

        rem Winbinder
        echo Downloading https://github.com/crispy-computing-machine/Winbinder/releases/download/latest/php_winbinder.dll
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_winbinder.dll" https://github.com/crispy-computing-machine/Winbinder/releases/download/latest/php_winbinder.dll -Q
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\php_winbinder.dll" echo Error, php_winbinder not found. && exit /b 1

        rem freeimage
        echo Downloading https://github.com/crispy-computing-machine/freeimage/blob/main/freeimage.dll
        wget -O "%APPVEYOR_BUILD_FOLDER%\build\ext\freeimage.dll" https://github.com/crispy-computing-machine/freeimage/blob/main/freeimage.dll -Q
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\freeimage.dll" echo Error, freeimage not found. && exit /b 1


        echo Make ini reference to extension .DLL's
        type nul > "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        echo extension_dir=.\ext >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        rem Debug
        echo error_reporting=E_ALL >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo display_errors=On >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo log_errors=On >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        rem Forced shared extensions
        echo extension=php_fileinfo.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension=php_intl.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        rem Zend Extensions
        echo zend_extension=php_opcache.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo opcache.enable_cli = 1 >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        rem Winbinder/Win32Std
        echo extension=php_winbinder.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo winbinder.debug_level = 0 >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo winbinder.low_level_functions = 1 >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        echo extension=php_win32std.dll >> "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

        Rem display
        type "%APPVEYOR_BUILD_FOLDER%\build\php.ini"

		echo Make embeder2.exe
		rem Copy MSBuild exe to build folder
        copy "%APPVEYOR_BUILD_FOLDER%\src\%BUILD_TYPE% console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe"

		rem Use built PHP to make Embeder2Command into an exe.
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" main "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" add "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\src\%BUILD_TYPE% console\embeder.exe" "out/console.exe"
        %APPVEYOR_BUILD_FOLDER%\build\embeder2.exe info > %APPVEYOR_BUILD_FOLDER%\build\info.html
        if %errorlevel% neq 0 exit /b 3

		rem Cleanup
		echo Cleanup files....
		DEL /Q %APPVEYOR_BUILD_FOLDER%\build\license.txt
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\news.txt
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php.ini-development
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php.ini-production
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php7embed.lib
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\phpdbg.exe
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\README.md
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\readme-redist-bins.txt
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\snapshot.txt
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\deplister.exe
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\pharcommand.phar
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\phar.phar.bat
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\ext\php_phpdbg_webhelper.dll
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php7apache2_4.dll
        DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php7phpdbg.dll

        echo Cleanup DIRS
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\dev
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\extras
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\lib
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\sasl2

		echo Zipping Assets...
        7z a embedder.zip %APPVEYOR_BUILD_FOLDER%\build\*
		appveyor PushArtifact embedder.zip -FileName embedder.zip
	)
endlocal
