<?php

namespace unitTests\app\libraries;

use app\libraries\GradeableType;

class GradeableTypeTester extends \PHPUnit_Framework_TestCase {
    public function testConstants() {
        $this->assertEquals(0, GradeableType::ELECTRONIC_FILE);
        $this->assertEquals(1, GradeableType::CHECKPOINTS);
        $this->assertEquals(2, GradeableType::NUMERIC_TEXT);
    }

    public function typeData() {
        return array(
            array(GradeableType::ELECTRONIC_FILE, "Electronic File"),
            array(GradeableType::CHECKPOINTS, "Checkpoints"),
            array(GradeableType::NUMERIC_TEXT, "Numeric/Text")
        );
    }

    /**
     * @param GradeableType $type
     * @param string        $expected
     * @dataProvider typeData
     */
    public function testTypeToString($type, $expected) {
        $this->assertEquals($expected, GradeableType::typeToString($type));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidType() {
        GradeableType::typeToString(4);
    }

    /**
     * @param GradeableType $expected
     * @param string        $type
     * @dataProvider typeData
     */
    public function testStringToType($expected, $type) {
        $this->assertEquals(GradeableType::stringToType($type), $expected);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidString() {
        GradeableType::stringToType("Invalid");
    }
}
