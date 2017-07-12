<?php

namespace app\libraries;

use app\exceptions\FileNotFoundException;
use app\exceptions\IniException;
use app\exceptions\IOException;

/**
 * Class IniParser
 *
 * Helper to interact with ini files, both reading them in as well as writing out an array
 * to the ini file. Reading a file is loosely based on austinhyde's IniParser
 * (@link https://github.com/austinhyde/IniParser) though stripped of some of the more
 * advanced features of it and changes to the type handling to fit our needs, however
 * it's still a general parser. The writer is setup specifically for our needs and expected
 * ini files (ones that have sections, and then stuff under that).
 */
class IniParser {
    /**
     * Reads in an ini file giving an associate array which is indexed by
     * section names. Additionally, we further decode the array that PHP
     * gives us in its builtin function to other primitive types than just
     * string. "true", "on", "yes" evaluate to bool true while "false", "off",
     * "no" evaluate to bool false, and "null" evaluates to null. Additionally,
     * if the string is a numeric, we will parse it to either int or float as
     * appropriate
     *
     * @param string $filename
     * @throws IniException | FileNotFoundException
     * @return array
     */
    public static function readFile($filename) {
        // @codeCoverageIgnoreStart
        if (!function_exists('parse_ini_file')) {
            throw new IniException("parse_ini_file needs to be enabled");
        }
        // @codeCoverageIgnoreEnd

        if (!file_exists($filename)) {
            throw new FileNotFoundException("Could not find ini file to parse: {$filename}");
        }

        $parsed = @parse_ini_file($filename, true, INI_SCANNER_RAW);
        if ($parsed === false) {
            $e = error_get_last();
            $basename = basename($filename);
            throw new IniException("Error reading ini file '{$basename}': {$e['message']}");
        }
        return static::decode($parsed);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function decode($value) {
        if (is_array($value)) {
            foreach ($value as $i => &$subvalue) {
                $subvalue = static::decode($subvalue);
            }
        }

        if (is_string($value)) {
            // Do we have a boolean?
            $test_value = strtolower($value);
            if ($test_value == "true" || $test_value == "yes" || $test_value == "on") {
                $value = true;
            }
            else if ($test_value == "false" || $test_value == "no" || $test_value == "off") {
                $value = false;
            }
            // Or do we have a null?
            else if ($test_value == "null") {
                $value = null;
            }
            // or is it a number?
            else if (is_numeric($value)) {
                if (intval($value) == floatval($value)) {
                    $value = intval($value);
                }
                else {
                    $value = floatval($value);
                }
            }
        }
        return $value;


    }

    /**
     * Writer function for INI files. This function expects an associative array where the first level
     * of the array are keys (sections) and that point to arrays which are then the settings for that
     * section.
     *
     * @param string $filename
     * @param array  $array
     *
     */
    public static function writeFile($filename, $array) {
        $to_write = "";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($to_write !== "") {
                    $to_write .= "\n";
                }
                $to_write .= "[{$key}]\n";
                foreach ($value as $kkey => $vvalue) {
                    if (is_array($vvalue)) {
                        foreach ($vvalue as $kkkey => $vvvalue) {
                            if (is_array($vvvalue)) {
                                throw new IniException("Cannot have nested arrays inside array elements");
                            }
                            $inner = (is_int($kkkey)) ? "" : $kkkey;
                            static::addElement($to_write, $kkey . "[{$inner}]", $vvvalue);
                        }
                    }
                    else {
                        static::addElement($to_write, $kkey, $vvalue);
                    }
                }
            }
            else {
                throw new IniException("Keys at top level of array are sections and must point to arrays");
            }
        }

        if (@file_put_contents($filename, $to_write) === false) {
            throw new IOException("Could not write ini file {$filename}");
        }
    }

    private static function addElement(&$to_write, $key, $value) {
        $to_write .= "{$key}=";
        if (is_string($value)) {
            $to_write .= "\"{$value}\"\n";
        }
        else {
            if (is_bool($value)) {
                $to_write .= (($value === true) ? "true" : "false")."\n";
            }
            else if ($value === null) {
                $to_write .= "null\n";
            }
            else {
                $to_write .= "{$value}\n";
            }
        }
    }
}
