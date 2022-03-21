<?php /** @noinspection ALL */

/**
 * Stub file - DO NOT INCLUDE! = For PHPStorm to analyse.
 */

// Reference: http://wildphp.free.fr/wiki/doku.php?id=win32std:index

// win32std constants
define('RT_CURSOR', '#1');
define('RT_BITMAP', '#2');
define('RT_ICON', '#3');
define('RT_MENU', '#4');
define('RT_DIALOG', '#5');
define('RT_STRING', '#6');
define('RT_FONTDIR', '#7');
define('RT_FONT', '#8');
define('RT_ACCELERATOR', '#9');
define('RT_RCDATA', '#10');
define('RT_MESSAGETABLE', '#11');
define('RT_GROUP_CURSOR', '#12');
define('RT_GROUP_ICON', '#14');
define('RT_VERSION', '#16');
define('RT_DLGINCLUDE', '#17');
define('RT_PLUGPLAY', '#19');
define('RT_VXD', '#20');
define('RT_ANICURSOR', '#21');
define('RT_ANIICON', '#22');
define('RT_HTML', '#23');
define('RT_NOT_DOCUMENTED', '#241');
define('MB_OK', '0');
define('MB_OKCANCEL', 1);
define('MB_RETRYCANCEL', 5);
define('MB_YESNO', 4);
define('MB_YESNOCANCEL', 3);
define('MB_ICONEXCLAMATION', 48);
define('MB_ICONWARNING', 48);
define('MB_ICONINFORMATION', 64);
define('MB_ICONASTERISK', 64);
define('MB_ICONQUESTION', 32);
define('MB_ICONSTOP', 16);
define('MB_ICONERROR', 16);
define('MB_ICONHAND', 16);
define('MB_DEFBUTTON1', '0');
define('MB_DEFBUTTON2', 256);
define('MB_DEFBUTTON3', 512);
define('MB_DEFBUTTON4', 768);
define('MB_IDABORT', 3);
define('MB_IDCANCEL', 2);
define('MB_IDNO', 7);
define('MB_IDOK', 1);
define('MB_IDYES', 6);
define('MB_IDIGNORE', 5);
define('MB_IDRETRY', 4);
define('HKEY_CLASSES_ROOT', '0');
define('HKEY_CURRENT_CONFIG', 1);
define('HKEY_CURRENT_USER', 2);
define('HKEY_LOCAL_MACHINE', 3);
define('HKEY_USERS', 4);
define('KEY_ALL_ACCESS', 983103);
define('KEY_WRITE', 131078);
define('KEY_READ', 131097);
define('REG_BINARY', 3);
define('REG_DWORD', 4);
define('REG_EXPAND_SZ', 2);
define('REG_MULTI_SZ', 7);
define('REG_NONE', '0');
define('REG_SZ', 1);

// Common Win32 dialogs

/**
 * Prompt a typical Win32 message box. Use the Messages Box Constants to modify the appearance of the message box.
 *
 * @param $text
 * @param $type
 * @param $caption
 *
 * @return int
 */
function win_message_box($text = '', $type = MB_YESNOCANCEL, $caption)
{
    return 1;
}

/**
 * Prompt a â€browse for folder" message box.
 *
 * @param string $dir
 * @param string $caption
 *
 * @return int
 */
function win_browse_folder($dir = '', $caption = '')
{
    return 1;
}

/**
 * Pop an open or save dialog box, You can specify a starting path, a default filename, a default extension, and a filter.
 *
 * @param $open
 * @param $path
 * @param $filename
 * @param $ext
 * @param $filter
 *
 * @return string
 */
function win_browse_file($open, $path, $filename, $ext, $filter)
{
    return '';
}

// Windows utility functions

/**
 * Execute a shell action on a file or directory.
 * Common actions: open, edit, explore, find, print, properties.
 *
 * The shell act the same way when you double click on an icon (action=NULL) or when you choose a menu item on the right click button.
 * This way you can also execute programs that are totaly detached from the current one (useful with DirectX games for exemple).
 *
 * @param $absolute_path
 * @param $action
 * @param $args
 * @param $dir
 *
 * @return bool
 */
function win_shell_execute($absolute_path, $action, $args = '', $dir = '')
{
    return true | false;
}

/**
 * file may be either NULL to stop playback or
 * a file name to start it loop can be set to loop playback (default to false)
 * module may be opened by res_open a file must represent the resource id (NOT IMPL).
 *
 * @param $file
 * @param $loop
 */
function win_play_wav($file = '', $loop = true)
{
}

/**
 * plays the system sound used by default for pre-defined events:
 * '*': System Asterisk
 * '!': System Exclamation
 * 'H': System Hand
 * '?': System Question
 * '1': System Default
 * '0': Standard beep using the computer speaker.
 *
 * @param string $type
 */
function win_beep($type = '')
{
}

/**
 * Create a MS link file (.lnk) Donâ€™t forget the .lnk at the end of link_file or the link will not work.
 *
 * @param string $file
 * @param string $link_file
 * @param string $args
 * @param string $descr
 * @param string $workingdir
 *
 * @return int
 */
function win_create_link($file = '', $link_file = '', $args = '', $descr = '', $workingdir = '')
{
    return 1;
}

// Registry access

/**
 * Open a registry key.
 *
 * @param $hKey
 * @param string $subkey
 * @param int    $samDesired
 * @return resource
 */
function reg_open_key($hKey, $subkey = '', $samDesired = KEY_ALL_ACCESS)
{
    // return resource
}

/**
 * Create a sub key.
 *
 * @param $hKey
 * @param string $subkey
 * @param int    $samDesired
 */
function reg_create_key($hKey, $subkey = '', $samDesired = KEY_ALL_ACCESS)
{
    // return resource
}

/**
 * Close a registry key.
 *
 * @param resource $hKey
 */
function reg_close_key(resource $hKey)
{
}

/**
 * Return the â€˜indexâ€™ based sub key. Return false when done.
 *
 * @param $hKey
 * @param int $index
 *
 * @return bool
 */
function reg_enum_key($hKey, $index = 1)
{
    /* @noinspection SuspiciousBinaryOperationInspection */
    return '' || 0 || true;
}

/**
 * Return the â€˜indexâ€™ based value. Return false when done.
 *
 * @param $hKey
 * @param int $index
 * @return int
 */
function reg_enum_value($hKey, $index = -1)
{
}

/**
 * @param mixed  $hKey
 * @param string $value_name
 * @param string $type
 * @param mixed  $value
 *
 * @return bool
 */
function reg_set_value($hKey, $value_name = '', $type = '', $value = '')
{
    return true || false;
}

/**
 * Get a value.
 *
 * @param mixed  $hKey
 * @param string $value_name
 */
function reg_get_value($hKey, $value_name)
{
}

// Windows resources

/**
 * Return a PHP resource that identify the Windows resource module handle. A module is either a dll file or an exe file.
 *
 * @param $module_name
 *
 * @return resource
 */
function res_open($module_name)
{
}

/**
 * Close a module handle.
 *
 * @param resource $module
 *
 * @return bool
 */
function res_close(resource $module)
{
    return true || false;
}

/**
 * Get a resource data. lang is not fully supported but 0 means neutral, 1 is user default, 2 is system default (see winnt.h LANG_* & SUBLANG_*).
 *
 * @param resource $module
 * @param string   $type
 * @param string   $name
 * @param int      $lang
 *
 * @return string
 */
function res_get($module, $type, $name, $lang = 0)
{
}

/**
 * Add or modify a resource in â€˜fileâ€™ (dll or exe) lang is not fully supported: 0 means neutral, 1 is user default, 2 is system default (see winnt.h LANG_* & SUBLANG_*).
 *
 * Fail if the file is in use (if the executable is in use for exemple).
 *
 * @param string $file
 * @param string $type
 * @param string $name
 * @param string $data
 * @param $lang
 *
 * @return bool
 */
function res_set($file, $type, $name, $data, $lang)
{
    return true || false;
}

/**
 * return the resource list for a given type.
 *
 * @param resource $module
 * @param string   $type
 *
 * @return array
 */
function res_list($module, $type)
{
    return [];
}

/**
 * return the resource type list for a given module.
 * as_string specify if known type should be translated to string (but such string cannot be used in res_get).
 *
 * @param resource $module
 * @param bool     $as_string
 *
 * @return array
 */
function res_list_type($module, $as_string = true)
{
}
