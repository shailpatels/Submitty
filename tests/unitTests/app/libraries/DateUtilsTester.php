<?php

namespace tests\unitTests\app\libraries;

use \app\libraries\DateUtils;

class DateUtilsTester extends \PHPUnit_Framework_TestCase {
    public function dayDiffsData() {
        return array(
            array(1, "Now", "Tomorrow"),
            array(0, "2017-01-12 19:10:53.000000", "2017-01-12 19:10:53.000000"),
            array(1, "2016-07-19 00:00:00", "2016-07-19 00:00:30"),
            array(0, "2016-07-19 00:00:30", "2016-07-19 00:00:00"),
            array(1, "2016-07-19 00:00:00", "2016-07-19 00:01:00"),
            array(0, "2016-07-19 00:01:00", "2016-07-19 00:00:00"),
            array(1, "2016-07-19 00:00:00", "2016-07-19 01:00:00"),
            array(0, "2016-07-19 01:00:00", "2016-07-19 00:00:00"),
            array(10, "2016-07-19 00:00:00", "2016-07-28 12:00:00"),
            array(0, "2016-07-19 00:00:00", "2016-07-18 23:55:00"),
            array(-1, "2016-07-19 00:00:00", "2016-07-17 23:00:00"),
            array(-6, "2016-07-19 00:00:00", "2016-07-12 12:00:00")
        );
    }
    
    /**
     * @param string $expected
     * @param string $date1
     * @param string $date2
     *
     * @dataProvider dayDiffsData
     */
    public function testCalculateDayDiff($expected, $date1, $date2) {
        $this->assertEquals($expected, DateUtils::calculateDayDiff($date1, $date2));
    }

    public function timestampData() {
        return array(
          array('08-05-1991', true),
          array('02-29-2016', true),
          array('06-31-2016', false),
          array('08/05/1991', true),
          array('02/29/2016', true),
          array('06/31/2016', false),
          array('08-05-91', true),
          array('02-29-16', true),
          array('06-31-16', false),
          array('08/05/91', true),
          array('02/29/16', true),
          array('06/31/16', false),
          array('string', false)
        );
    }

    /**
     * @param $timestamp
     * @param $expected
     *
     * @dataProvider timestampData
     */
    public function testValidateTimestamp($timestamp, $expected) {
        $this->assertEquals($expected, DateUtils::validateTimestamp($timestamp));
    }
}
