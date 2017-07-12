<?php

namespace app\libraries;

use \DateTime;
use \DateInterval;

/**
 * Class DateUtils
 *
 * Utility functions for interacting with dates and times
 */
class DateUtils {
    /**
     * Given two dates, give the interval of time in days between these two times. Any partial "days" are rounded
     * up to the nearest day in the positive direction. Thus if there's a difference of 2 days and 3 hours, then
     * the function would return 3 days. Likewise, if the difference was -3 hours, then 0 days would be returned.
     *
     * @param string|DateTime $date1
     * @param string|DateTime $date2
     *
     * @return int
     */
    public static function calculateDayDiff($date1, $date2="Now") {
        if (!($date1 instanceof DateTime)) {
            $date1 = new DateTime($date1);
        }
        if (!($date2 instanceof DateTime)) {
            $date2 = new DateTime($date2);
        }
        // Set the period as "1 day" for the interval
        if ($date1 == $date2) {
            return 0;
        }
        $diff = $date1->diff($date2);
        $days_late = intval($diff->format('%r%a'));
        if ($date1 < $date2) {
            if ($diff->h > 0 || $diff->i > 0 || $diff->s > 0) {
                $days_late += 1;
            }
        }

        return $days_late;
    }

    public static function validateTimestamp($timestamp) {
        /**
         * Given a timestamp string, make sure that it is in a valid timestamp that
         * conforms to some specific date patterns. These patterns are:
         *      1) 'm-d-Y' -> mm-dd-yyyy
         *      3) 'm/d/Y' -> mm/dd/yyyy
         *      2) 'm-d-y' -> mm-dd-yy
         *      4) 'm/d/y' -> mm/dd/yy
         * We check that the inputted string is a valid pattern and is a date
         * that actually exists, returning TRUE if this is the case, else FALSE.
         *
         * ex: 02-29-2016 is valid, while "06-31-2016" is not as 2016 was a leap
         * year so February had 29 days, while June only ever has 30 days.
         *
         * @param string
         * @return bool
         */

        $formats = array('m-d-Y', 'm-d-y', 'm/d/Y', 'm/d/y');

        foreach ($formats as $format) {
            $datetime = date_create_from_format($format, $timestamp);
            if ($datetime !== false && $datetime->format($format) === $timestamp) {
                return true;
            }
        }
        return false;
    }
}
