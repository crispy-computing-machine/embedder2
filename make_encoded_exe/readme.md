- Clone or download the repository from https://github.com/crispy-computing-machine/embedder2.
- Navigate to the directory make_encoded_exe.
- Make sure you have PHP installed on your machine and accessible in the command line.
- Run the PHP script using the command line. The usage is:

``
php2exe [options] file.php
``

where file.php is the PHP file you want to build into an exe and options is the directory containing the PHP source .h files, etc.
- The PHP script will create a C file (the "stub" application) which includes some contents and runs the main application. The contents of your PHP script will be encrypted and embedded into the C file
- A batch file will be created to run cl.exe and build the application using the generated C code
- The generated C file and batch file will be saved in the make_encoded_exe directory
- Navigate to the src directory and open embeder.vcxproj in Visual Studio, or manually build the project by adding the PHP source directories to the include path and then building the exe. The PHP source directories to be included are as follows:

``
    C:\path\to\php\source\main
    C:\path\to\php\source\Zend
    C:\path\to\php\source\TSRM
    C:\path\to\php\source
    C:\path\to\php\source\sapi\embed
    C:\path\to\php\source\ext\standard
``

- Replace C:\path\to\php\source with the actual path to your PHP source directories. Once the paths are set, build the exe file​2​.
- Copy the built exe file to the out directory and rename it to console.exe, or run post.cmd​2​.

- Remember to have Visual Studio and PHP installed on your machine. The PHP script must be able to be run from the command line, and the Visual Studio project requires the PHP source files in order to build the executable.
