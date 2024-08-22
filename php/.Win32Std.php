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
/** @noinspection ALL */
const RT_CURSOR = "#1";
const RT_BITMAP = "#2";
const RT_ICON = "#3";
const RT_MENU = "#4";
const RT_DIALOG = "#5";
const RT_STRING = "#6";
const RT_FONTDIR = "#7";
const RT_FONT = "#8";
const RT_ACCELERATOR = "#9";
const RT_RCDATA = "#10";
const RT_MESSAGETABLE = "#11";
const RT_GROUP_CURSOR = "#12";
const RT_GROUP_ICON = "#14";
const RT_VERSION = "#16";
const RT_DLGINCLUDE = "#17";
const RT_PLUGPLAY = "#19";
const RT_VXD = "#20";
const RT_ANICURSOR = "#21";
const RT_ANIICON = "#22";
const RT_HTML = "#23";
const RT_NOT_DOCUMENTED = "#241";
const MB_IDABORT = 3;
const MB_IDCANCEL = 2;
const MB_IDNO = 7;
const MB_IDOK = 1;
const MB_IDYES = 6;
const MB_IDIGNORE = 5;
const MB_IDRETRY = 4;
function res_get($res_rc, string $type, string $name, ?string $lang = '') : string|false{}
function res_list($res_rc, string $type) : array|false{}
function res_list_type($res_rc, ?bool $as_string) : array|false{}
function res_open(string $module) : false{}
function res_close($res_rc) : ?int{}
function res_set($module, string $type, string $name, int $data) : ?int{}
function res_exists($res_rc, string $type, string $name, ?string $lang) : ?int{}
function win_play_wav(string $file, bool $loop) : ?int{}
function win_beep(string $str) : ?int{}
function win_message_box(string $text, ?int $type, ?string $caption) : false{}
function win_create_link(string $file, string $link, ?string $args, ?string $descr, ?string $workingdir) : ?int{}
function win_browse_folder(?string $dir, ?string $caption) : ?false{}
function win_browse_file(?int $open, ?string $path, ?string $file, ?string $ext, null $zfilter) : ?string{}