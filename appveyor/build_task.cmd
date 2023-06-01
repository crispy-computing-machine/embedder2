@echo off
setlocal enableextensions enabledelayedexpansion

	cd /D %APPVEYOR_BUILD_FOLDER%
	if %errorlevel% neq 0 exit /b 3

	set STABILITY=stable
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
		--enable-embed=static ^
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
		--with-xsl=static ^
		--enable-zip=static ^
		--enable-shmop=static ^
		--with-snmp=no ^
		--enable-soap=static ^
		--enable-sysvshm=static ^
		--enable-zend-test=no ^
		--enable-phpdbg=no ^
		--enable-phpdbgs=no ^
		--enable-apache2-4handler=no


		if %errorlevel% neq 0 exit /b 3

        rem Suppress logo output of nmake
		nmake /NOLOGO
		if %errorlevel% neq 0 exit /b 3

        nmake snap
		rem nmake install
		if %errorlevel% neq 0 exit /b 3

		cd /d %APPVEYOR_BUILD_FOLDER%

        MSBuild.exe %APPVEYOR_BUILD_FOLDER%\src\embeder.sln /p:Configuration="%BUILD_TYPE% console" /p:Platform="x64"
        if %errorlevel% neq 0 exit /b 3

        echo Copying built files into build/asset dir C:\obj\Release_TS\php-8.*.*-dev-Win32-vs16-x64.zip
        rem xcopy "%PHP_BUILD_OBJ_DIR%\Release_TS\php-8.*.*-dev-Win32-vs16-x64.zip" "%APPVEYOR_BUILD_FOLDER%\build\" /s /i /Y
        powershell -NoP -NonI -Command "Expand-Archive -Force -Path '%PHP_BUILD_OBJ_DIR%\Release_TS\php-8.*.*-dev-Win32-vs16-x64.zip' -DestinationPath '%APPVEYOR_BUILD_FOLDER%\build\'"
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\php-win.exe" echo Error, PHP not found. && exit /b 1

        rem win32std
        mkdir "%APPVEYOR_BUILD_FOLDER%\build\ext\"
        echo Downloading https://github.com/crispy-computing-machine/win32std/releases/download/latest/php_win32std.dll
        wget -q --show-progress -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" https://github.com/crispy-computing-machine/win32std/releases/download/latest/php_win32std.dll
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32std.dll" echo Error, php_win32std not found. && exit /b 1

        rem Winbinder
        echo Downloading https://github.com/crispy-computing-machine/Winbinder/releases/download/latest/php_winbinder.dll
        wget  -q --show-progress -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_winbinder.dll" https://github.com/crispy-computing-machine/Winbinder/releases/download/latest/php_winbinder.dll
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\php_winbinder.dll" echo Error, php_winbinder not found. && exit /b 1

        rem win32ps
        echo Downloading https://github.com/crispy-computing-machine/php_win32ps/releases/download/latest/php_win32ps.dll
        wget -q --show-progress -O "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32ps.dll" https://github.com/crispy-computing-machine/php_win32ps/releases/download/latest/php_win32ps.dll
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\php_win32ps.dll" echo Error, php_win32ps not found. && exit /b 1

        rem freeimage
        echo Downloading https://github.com/crispy-computing-machine/freeimage/releases/download/latest/FreeImage.dll
        wget -q --show-progress -O "%APPVEYOR_BUILD_FOLDER%\build\ext\freeimage.dll" https://github.com/crispy-computing-machine/freeimage/releases/download/latest/FreeImage.dll
        IF NOT EXIST "%APPVEYOR_BUILD_FOLDER%\build\ext\freeimage.dll" echo Error, freeimage not found. && exit /b 1

        echo Make ini reference to extension .DLL's
        copy %APPVEYOR_BUILD_FOLDER%\php\php.ini "%APPVEYOR_BUILD_FOLDER%\build\php.ini"
        type %APPVEYOR_BUILD_FOLDER%\build\php.ini

		echo Copy MSBuild exe to build folder and update manifest
        copy "%APPVEYOR_BUILD_FOLDER%\src\x64\%BUILD_TYPE% console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe"
		copy "%APPVEYOR_BUILD_FOLDER%\src\x64\%BUILD_TYPE% console\embeder.exe" "%APPVEYOR_BUILD_FOLDER%\build\debug.exe"
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open "%APPVEYOR_BUILD_FOLDER%\src\x64\%BUILD_TYPE% console\embeder.exe" -resource %php_dir%\php.exe.manifest -action addoverwrite -mask 24, 1,1033, -save "%APPVEYOR_BUILD_FOLDER%\src\x64\%BUILD_TYPE% console\embeder.exe"

		if %errorlevel% neq 0 exit /b 3

		echo Use built PHP to make Embeder2Command into an exe.
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" main "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php"
        %APPVEYOR_BUILD_FOLDER%\build\php.exe -c "%APPVEYOR_BUILD_FOLDER%\build\php.ini" "%APPVEYOR_BUILD_FOLDER%\php\Embeder2Command.php" add "%APPVEYOR_BUILD_FOLDER%\build\embeder2.exe" "%APPVEYOR_BUILD_FOLDER%\src\x64\%BUILD_TYPE% console\embeder.exe" "out/console.exe"
        %APPVEYOR_BUILD_FOLDER%\build\embeder2.exe info > %APPVEYOR_BUILD_FOLDER%\build\embeder2-info.html
        if %errorlevel% neq 0 exit /b 3
		
		echo Add new look manifest + elphant logo (Res hacker tweaks)
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php-win.exe -resource %php_dir%\php.exe.manifest -action addoverwrite -mask 24, 1,1033, -save %php_dir%\php-win.exe
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php-win.exe -resource %APPVEYOR_BUILD_FOLDER%\src\res\php.ico -action addoverwrite -mask ICONGROUP,0, -save %php_dir%\php-win.exe
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php-win.exe -resource %APPVEYOR_BUILD_FOLDER%\src\res\php.ico -action addoverwrite -mask ICONGROUP,MAINICON, -save %php_dir%\php-win.exe
		
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php.exe -resource %php_dir%\php.exe.manifest -action addoverwrite -mask 24, 1,1033, -save %php_dir%\php.exe
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php.exe -resource %APPVEYOR_BUILD_FOLDER%\src\res\php.ico -action addoverwrite -mask ICONGROUP,0, -save %php_dir%\php.exe
		"%APPVEYOR_BUILD_FOLDER%\php\ResourceHacker.exe" -open %php_dir%\php.exe -resource %APPVEYOR_BUILD_FOLDER%\src\res\php.ico -action addoverwrite -mask ICONGROUP,MAINICON, -save %php_dir%\php.exe

		rem Cleanup
		echo Cleanup files....
		DEL /Q %APPVEYOR_BUILD_FOLDER%\build\license.txt >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\news.txt >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php.ini-development >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php.ini-production >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php8embed.lib >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\phpdbg.exe >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\README.md >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\readme-redist-bins.txt >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\snapshot.txt >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\deplister.exe >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\pharcommand.phar >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\phar.phar.bat >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\ext\php_phpdbg_webhelper.dll >NUL 2>NUL
		DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php8apache2_4.dll >NUL 2>NUL
        DEL /Q  %APPVEYOR_BUILD_FOLDER%\build\php8phpdbg.dll >NUL 2>NUL

        echo Cleanup DIRS
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\dev >NUL 2>NUL
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\extras >NUL 2>NUL
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\lib >NUL 2>NUL
		rmdir /s /q %APPVEYOR_BUILD_FOLDER%\build\sasl2 >NUL 2>NUL


		rem echo Zipping Debug packages...
        rem 7z a embedder.zip %APPVEYOR_BUILD_FOLDER%\build\*
		rem appveyor PushArtifact embedder.zip -FileName embedder.zip

		echo Zipping Assets...
        7z a embedder.zip %APPVEYOR_BUILD_FOLDER%\build\*
		appveyor PushArtifact embedder.zip -FileName embedder.zip

	)
endlocal
