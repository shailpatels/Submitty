<?php

namespace unitTests\app\libraries;

use app\libraries\IniParser;

class IniParserTester extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \app\exceptions\FileNotFoundException
     * @expectedExceptionMessage Could not find ini file to parse: invalid_file
     */
    public function testNonExistFile() {
        IniParser::readFile("invalid_file");
    }

    /**
     * @expectedException \app\exceptions\IniException
     * @expectedExceptionMessageRegExp  /Error reading ini file 'invalid\.ini': syntax error, unexpected NULL_NULL in (.*?)invalid\.ini/
     */
    public function testNonValidIniFile() {
        IniParser::readFile(__TEST_DATA__.'/ini_files/invalid.ini');
    }

    public function testValidIniFile() {
        $ini_file = IniParser::readFile(__TEST_DATA__.'/ini_files/valid.ini');
        $expected = array(
            'section_1' => array(
                'string' => 'string',
                'string_2' => 'string',
                'integer' => 10,
                'float' => 10.10,
                'bool_true' => true,
                'bool_true_2' => true,
                'bool_true_3' => true,
                'bool_false' => false,
                'bool_false_2' => false,
                'bool_false_3' => false,
                'array' => array(
                    0 => 1,
                    1 => 2,
                    2 => 3
                ),
                'value_null' => null
            ),
            'section_2' => array(
                'array' => array(
                    'yes' => true,
                    'no' => false
                )
            )
        );
        $this->assertEquals($expected, $ini_file);
    }

    public function testWriteIniFile() {
        $file = array(
            'section_1' => array(
                'string' => 'string',
                'string_2' => 'string',
                'integer' => 10,
                'float' => 10.10,
                'bool_true' => true,
                'bool_true_2' => true,
                'bool_true_3' => true,
                'bool_false' => false,
                'bool_false_2' => false,
                'bool_false_3' => false,
                'array' => array(
                    0 => 1,
                    1 => 2,
                    2 => 3
                ),
                'value_null' => null
            ),
            'section_2' => array(
                'array' => array(
                    'yes' => true,
                    'no' => false
                )
            )
        );
        $tmp_file = tempnam(sys_get_temp_dir(), "initest_");
        register_shutdown_function(function() use ($tmp_file) {
            unlink($tmp_file);
        });
        IniParser::writeFile($tmp_file, $file);
        $this->assertFileEquals($tmp_file, __TEST_DATA__.'/ini_files/test.ini');
    }

    /**
     * @expectedException \app\exceptions\IniException
     * @expectedExceptionMessage Cannot have nested arrays inside array elements
     */
    public function testInvalidArray() {
        $array = array(
            'section_1' => array(
                'inner' => array(
                    0 => array(
                        'inner'
                    )
                )
            )
        );

        $tmp_file = tempnam(sys_get_temp_dir(), "initest_");
        register_shutdown_function(function() use ($tmp_file) {
            unlink($tmp_file);
        });
        IniParser::writeFile($tmp_file, $array);
    }

    /**
     * @expectedException \app\exceptions\IniException
     * @expectedExceptionMessage Keys at top level of array are sections and must point to arrays
     */
    public function testCannotHaveNonArraryRoot() {
        $array = array('value');
        $tmp_file = tempnam(sys_get_temp_dir(), "initest_");
        register_shutdown_function(function() use ($tmp_file) {
            unlink($tmp_file);
        });
        IniParser::writeFile($tmp_file, $array);
    }

    /**
     * @expectedException \app\exceptions\IOException
     * @expectedExceptionMessageRegExp /Could not write ini file .*?/
     */
    public function testCannotWriteFile() {
        $file = array(
            'section_1' => array(
                'string' => 'string'
            )
        );
        $tmp_file = tempnam(sys_get_temp_dir(), "initest_");
        chmod($tmp_file, 0400);
        register_shutdown_function(function() use ($tmp_file) {
            unlink($tmp_file);
        });
        IniParser::writeFile($tmp_file, $file);
    }
}
