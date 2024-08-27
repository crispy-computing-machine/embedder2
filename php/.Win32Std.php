<?php

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
const MB_OK = 0;
const MB_OKCANCEL = 1;
const MB_ABORTRETRYIGNORE = 2;
const MB_YESNOCANCEL = 3;
const MB_YESNO = 4;
const MB_RETRYCANCEL = 5;
const MB_CANCELTRYCONTINUE = 6;
const MB_ICONHAND = 16;
const MB_ICONQUESTION = 32;
const MB_ICONEXCLAMATION = 48;
const MB_ICONASTERISK = 64;
const MB_USERICON = 128;
const MB_ICONWARNING = 48;
const MB_ICONERROR = 16;
const MB_ICONINFORMATION = 64;
const MB_ICONSTOP = 16;

/**
 * Get a resource.
 * lang is experimental: 0 is neutral, 1 is user default, 2 is system default (see winnt.h LANG_* & SUBLANG_*).
 *
 * @param $res_rc
 * @param string $type
 * @param string $name
 * @param int|null $lang
 * @return string|false
 */
function res_get($res_rc, string $type, string $name, ?int $lang = 0) : string|false{}

/**
 * return the resource list for a given type
 * @param $res_rc
 * @param string $type
 * @return array|false
 */
function res_list($res_rc, string $type) : array|false{}

/**
 * return the resource type list for a given module
 * as_string specify if known type should be translated to string (but such string can't be used in res_get)
 *
 * Constants for resource types
 * RT_CURSOR="#1"
 * RT_BITMAP="#2"
 * RT_ICON="#3"
 * RT_MENU="#4"
 * RT_DIALOG="#5"
 * RT_STRING="#6"
 * RT_FONTDIR="#7"
 * RT_FONT="#8"
 * RT_ACCELERATOR="#9"
 * RT_RCDATA="#10"
 * RT_MESSAGETABLE="#11"
 * RT_GROUP_CURSOR="#12"
 * RT_GROUP_ICON="#14"
 * RT_VERSION="#16"
 * RT_DLGINCLUDE="#17"
 * RT_PLUGPLAY="#19"
 * RT_VXD="#20"
 * RT_ANICURSOR="#21"
 * RT_ANIICON="#22"
 * RT_HTML="#23"
 *
 * @param $res_rc
 * @param bool|null $as_string
 * @return array|false
 */
function res_list_type($res_rc, ?bool $as_string) : array|false{}

/**
 * Return a (PHP)resource that identify the (WIN)resource module handle.
 * A module is either a dll file or an exe file.
 * @param string $module
 * @return false
 */
function res_open(string $module) : false{}

/**
 * Close a module handle
 *
 * @param $res_rc
 * @return bool
 */
function res_close($res_rc) : bool{}

/**
 * Add or modify a resource in 'file' (dll or exe)
 * lang is experimental: 0 is neutral, 1 is user default, 2 is system default (see winnt.h LANG_* & SUBLANG_*).
 * Fail if the file is in use.
 *
 * @param $module
 * @param string $type
 * @param string $name
 * @param int $data
 * @param int|null $lang
 * @return bool
 */
function res_set($module, string $type, string $name, int $data, ?int $lang = 0) : bool{}

/**
 * Check if resource exists in the actual module
 * lang is experimental: 0 is neutral, 1 is user default, 2 is system default (see winnt.h LANG_* & SUBLANG_*).
 *
 * @param $res_rc
 * @param string $type
 * @param string $name
 * @param int|null $lang
 * @return bool
 */
function res_exists($res_rc, string $type, string $name, ?int $lang = 0) : bool{}

/**
 * file may be either NULL to stop playback or a file name to start it
 * loop can be set to loop playback (default to false)
 * module may be opened by res_open a file must represent the resource id (NOT IMPL)
 *
 * @param string $file
 * @param bool $loop
 * @return bool
 */
function win_play_wav(string $file, bool $loop) : bool{}

/**
 * plays the system sound used by default for pre-defined events:
 * '*': System Asterisk
 * '!': System Exclamation
 * 'H': System Hand
 * '?': System Question
 * '1': System Default
 * '0': Standard beep using the computer speaker
 *
 * @param string $str
 * @return bool
 */
function win_beep(string $str) : bool{}

/**
 * Prompt a typical Win32 message box.
 * Use the Messages Box Constants to modify the appearance of the message box
 *
 * Message box type constants:
 * MB_OK
 * MB_OKCANCEL
 * MB_RETRYCANCEL
 * MB_YESNO
 * MB_YESNOCANCEL
 *
 * Message box type constants:
 * MB_OK
 * MB_OKCANCEL
 * MB_RETRYCANCEL
 * MB_YESNO
 * MB_YESNOCANCEL
 *
 *
 * Message box icon constants
 * MB_ICONEXCLAMATION
 * MB_ICONWARNING
 * MB_ICONINFORMATION
 * MB_ICONASTERISK
 * MB_ICONQUESTION
 * MB_ICONSTOP
 * MB_ICONERROR
 * MB_ICONHAND
 *
 *
 * Message box default button constants
 * MB_DEFBUTTON1
 * MB_DEFBUTTON2
 * MB_DEFBUTTON3
 * MB_DEFBUTTON4
 *
 *
 * Message box return constants
 * MB_IDABORT
 * MB_IDCANCEL
 * MB_IDNO
 * MB_IDOK
 * MB_IDYES
 * MB_IDIGNORE
 * MB_IDRETRY
 *
 * @param string $text
 * @param int|null $type
 * @param string|null $caption
 * @return false
 */
function win_message_box(string $text, ?int $type, ?string $caption) : false{}

/**
 * Create a MS link file (.lnk)
 * Don't forget the .lnk at the end of link_file !
 *
 * @param string $file
 * @param string $link
 * @param string|null $args
 * @param string|null $descr
 * @param string|null $workingdir
 * @return bool
 */
function win_create_link(string $file, string $link, ?string $args, ?string $descr, ?string $workingdir) : bool{}

/**
 * Prompt a "browse for folder" message box
 *
 * @param string|null $dir
 * @param string|null $caption
 * @return false|null
 */
function win_browse_folder(?string $dir, ?string $caption) : ?false{}

/**
 * open or save dialog box, starting path, default filename, default extension, filter in MS format
 * Filter example: "HTML File\0*.htm;*.html\0INI file\0*.ini\0All files\0*.*\0\0"
 *
 * @param int|null $open
 * @param string|null $path
 * @param string|null $file
 * @param string|null $ext
 * @param null $zfilter
 * @return string|null
 */
function win_browse_file(?int $open, ?string $path, ?string $file, ?string $ext, null $zfilter) : ?string{}