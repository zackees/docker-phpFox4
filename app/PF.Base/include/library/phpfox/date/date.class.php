<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Handles all time/date related logic for phpFox.
 *
 * Example to get a current users timezone they are in:
 * <code>
 * Phpfox::getLib('date')->getTimeZone();
 * </code>
 *
 * Example of our usage of mktime:
 * <code>
 * Phpfox::getLib('date')->mktime(1, 1, 1, 6, 22, 1982);
 * </code>
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: date.class.php 6109 2013-06-21 11:48:16Z
 *                    phpFox LLC $
 */

define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);

class Phpfox_Date
{
    /**
     * Array of all the last days for each of the months starting with January
     *
     * @var int[]
     */
    private $_aMonthTable
        = [
            31,
            28,
            31,
            30,
            31,
            30,
            31,
            31,
            30,
            31,
            30,
            31,
        ];

    private $_aDayTable
        = [
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6
        ];

    /**
     * @return Phpfox_Date
     */
    public static function instance()
    {
        return Phpfox::getLib('date');
    }

    /**
     * Gets the current time zone for a person browsing the site. If
     * they have selected a time zone it will return their value, if not
     * it will return the sites default time zone.
     *
     * @param bool    $bDst Take DST into account. Set to false to disable DST.
     * @param integer $iTime
     *
     * @return float|int|mixed|string|null Returns the time zone offset from GMT
     * @throws Exception
     */
    public static function getTimeZone($bDst = true, $iTime = null)
    {
        static $sUserOffSet = null;

        if (PHPFOX_USE_DATE_TIME) {
            $bDst = false;
        }

        if ($bDst === false) {
            $sUserOffSet = Phpfox::getUserBy('time_zone');

            if (empty($sUserOffSet)) {
                $sUserOffSet = Phpfox::getParam('core.default_time_zone_offset');
            }

            if (substr($sUserOffSet, 0, 1) == 'z' && PHPFOX_USE_DATE_TIME) {
                $aTZ = Phpfox::getService('core')->getTimeZones(true, false);
                $oGmt = new DateTimeZone('GMT');
                if (!empty($iTime)) {
                    $iTime = date("Y-m-d H:i", $iTime);
                } else {
                    $iTime = null;
                }
                $mTimeNow = new DateTime($iTime, $oGmt);
                $oTZ = new DateTimeZone($aTZ[$sUserOffSet]);
                $sUserOffSet = ($oTZ->getOffset($mTimeNow) / HOUR_IN_SECONDS);
            }

            return $sUserOffSet;
        }

        if ($sUserOffSet === null) {
            $sUserOffSet = Phpfox::getUserBy('time_zone');
            if (empty($sUserOffSet)) {
                $sUserOffSet
                    = Phpfox::getParam('core.default_time_zone_offset');
            }

            if (self::_isDst() === true && $bDst === true) {
                $sUserOffSet = ($sUserOffSet + 1);
            }
        }

        return $sUserOffSet;
    }

    /**
     * Gets the last day of month.
     *
     * @param int $iMonth The month to get the last day of
     * @param int $iYear  You can specify a year, if not we find the current year to work with
     *
     * @return int number of day
     */
    public function lastDayOfMonth($iMonth, $iYear = null)
    {
        // build the date for the very first second of next month
        $iYear = ($iYear === null) ? date('Y') : (int)$iYear;
        $iNextMonth = $this->mktime(0, 0, 0, $iMonth + 1, 1, $iYear);
        $iLastDay = date('j', $iNextMonth - 1);
        return $iLastDay;
    }

    /**
     * Calculates how many days there are between 2 specific dates.
     *
     * @param int  $iTimeStart mktime of the starting date. If null we take current time
     * @param int  $iTimeEnd   mktime of the ending date
     * @param bool $bRound     TRUE will round the calculation and FALSE will return its full calculation
     *
     * @return int    Number of days between $iTimeStart and $iTimeEnd
     */
    public function daysToDate($iTimeEnd, $iTimeStart = null, $bRound = true)
    {
        // Starting date values
        if ($iTimeStart !== null) {
            $iMonthStart = substr($iTimeStart, 0, 2);
            $iDayStart = substr($iTimeStart, 2, 2);
            $iYearStart = substr($iTimeStart, 4, 4);
            $iTimeStart = $this->mktime(0, 0, 0, $iMonthStart, $iDayStart,
                $iYearStart);
        } else {
            $iTimeStart = Phpfox::getTime();
            $iMonthStart = date('m', $iTimeStart);
            $iDayStart = date('d', $iTimeStart);
        }
        // Ending date Values
        $iMonthEnd = intval(substr($iTimeEnd, 0, 2));
        $iDayEnd = intval(substr($iTimeEnd, 2, 2));
        $iTimeEnd = intval($iTimeEnd);
        $iYearEnd = (date('Y', Phpfox::getTime()) > date('Y', $iTimeEnd))
            ? date('Y', Phpfox::getTime()) : date('Y', $iTimeEnd);
        if (date('m', Phpfox::getTime()) > $iMonthEnd) {
            $iYearEnd++;
        }

        $iTimeEnd = $this->mktime(24, 0, 0, $iMonthEnd, $iDayEnd, $iYearEnd);

        if ($bRound == true) {
            if ($iMonthStart == $iMonthEnd
                && $iDayStart == $iDayEnd
            ) // compare day to day (iDayStart and iDayEnd) and months iMonthStart and iMonthEnd
            {
                return 0;
            }
            $iDiff = floor(($iTimeEnd - $iTimeStart) / DAY_IN_SECONDS);
        } else {
            $iDiff = ($iTimeEnd - $iTimeStart) / DAY_IN_SECONDS;
        }

        return $iDiff;
    }

    /**
     * Gets a specific month and returns the phrase based on the language package being used
     *
     * @param int $iMonth The int value of the month to return, where 1 is January
     *
     * @return string Returns the month as a phrase.
     */
    public function getMonth($iMonth)
    {
        switch ((int)$iMonth) {
            case 1:
                return _p('january');
                break;
            case 2:
                return _p('february');
                break;
            case 3:
                return _p('march');
                break;
            case 4:
                return _p('april');
                break;
            case 5:
                return _p('may');
                break;
            case 6:
                return _p('june');
                break;
            case 7:
                return _p('july');
                break;
            case 8:
                return _p('august');
                break;
            case 9:
                return _p('september');
                break;
            case 10:
                return _p('october');
                break;
            case 11:
                return _p('november');
                break;
            case 12:
                return _p('december');
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * PHP has their own mktime() function, however there are issues related to dates
     * before the year 1901 and the year 2038. This function extends the PHP mktime()
     * function to help resolve those issues. The logic and outcome is still the same as mktime()
     *
     * @param int $iHour   The number of the hour.
     * @param int $iMinute The number of the minute.
     * @param int $iSecond The number of seconds past the minute.
     * @param int $iMonth  The number of the month.
     * @param int $iDay    The number of the month.
     * @param int $iYear   The number of the year.
     *
     * @return int Returns the Unix timestamp of the arguments given
     * @see mktime()
     */
    public function mktime($iHour, $iMinute, $iSecond, $iMonth, $iDay, $iYear)
    {
        $iDay = intval($iDay);
        $iMonth = intval($iMonth);
        $iYear = intval($iYear);

        if ($iDay === 0) {
            $iDay = 1;
        }

        if ($iMonth === 0) {
            $iMonth = 1;
        }

        if ($iYear === 0) {
            $iYear = 1982;
        }

        if ((1901 < $iYear) and ($iYear < 2038)) {
            return mktime($iHour, $iMinute, $iSecond, $iMonth, $iDay, $iYear);
        }

        if ($iMonth > 12) {
            $iOverlap = floor($iMonth / 12);
            $iYear += $iOverlap;
            $iMonth -= $iOverlap * 12;
        } else {
            $iOverlap = ceil((1 - $iMonth) / 12);
            $iYear -= $iOverlap;
            $iMonth += $iOverlap * 12;
        }

        $iDate = 0;
        // safety check
        if ($iYear > 2070) {
            $iYear = 2070;
        }

        if ($iYear >= 1970) {
            for ($iCount = 1970; $iCount <= $iYear; $iCount++) {
                $bLeapYear = $this->isLeapYear($iCount);
                if ($iCount < $iYear) {
                    $iDate += 365;
                    if ($bLeapYear === true) {
                        $iDate++;
                    }
                } else {
                    for ($iCount = 0; $iCount < ($iMonth - 1); $iCount++) {
                        $iDate += $this->_aMonthTable[$iCount];
                        if (($bLeapYear === true) and ($iCount == 1)) {
                            $iDate++;
                        }
                    }
                }
            }

            $iDate += $iDay - 1;
            $iDate = (($iDate * DAY_IN_SECONDS) + ($iHour * HOUR_IN_SECONDS) + ($iMinute * MINUTE_IN_SECONDS)
                + $iSecond);
        } else {
            for ($iCount = 1969; $iCount >= $iYear; $iCount--) {
                $bLeapYear = $this->isLeapYear($iCount);
                if ($iCount > $iYear) {
                    $iDate += 365;
                    if ($bLeapYear === true) {
                        $iDate++;
                    }
                } else {
                    for ($iCount = 11; $iCount > ($iMonth - 1); $iCount--) {
                        $iDate += $this->_aMonthTable[$iCount];
                        if (($bLeapYear === true) and ($iCount == 1)) {
                            $iDate++;
                        }
                    }
                }
            }

            $iDate += ($this->_aMonthTable[$iMonth - 1] - $iDay);
            $iDate = -(($iDate * DAY_IN_SECONDS) + (DAY_IN_SECONDS - (($iHour * HOUR_IN_SECONDS) + ($iMinute
                            * MINUTE_IN_SECONDS) + $iSecond)));

            if ($iDate < -12220185600) {
                $iDate += DAY_IN_SECONDS * 10;
            } else if ($iDate < -12219321600) {
                $iDate = -12219321600;
            }
        }

        return $iDate;
    }

    /**
     * Checks if the current year passed is a leap year.
     *
     * @param int $iYear The year to check.
     *
     * @return bool Returns TRUE if it is a leap year and FALSE if it is not.
     */
    public function isLeapYear($iYear)
    {
        if (($iYear % 4) != 0) {
            return false;
        }

        if ($iYear % 400 == 0) {
            return true;
        } else if (($iYear > 1582) and ($iYear % 100 == 0)) {
            return false;
        }

        return true;
    }

    /**
     * Converts PM time stamp into military time.
     *
     * @param int $iTime PM time
     *
     * @return int Returns military time
     */
    public function amToPm($iTime)
    {
        $iTime = (int)$iTime;

        return ($iTime <= 24 and $iTime > 12) ? $iTime : null;
    }

    /**
     * Gets the first saturday of the coming week
     *
     * @return int Unix time stamp
     */
    public function getSaturday()
    {
        return $this->mktime(0, 0, 0, Phpfox::getTime('m'),
            (date('d', $this->getWeekEnd()) + 1), Phpfox::getTime('Y'));
    }

    /**
     * Gets the first sunday of the coming week
     *
     * @return int Unix time stamp
     */
    public function getSunday()
    {
        return $this->mktime(23, 59, 59, Phpfox::getTime('m'),
            (date('d', $this->getWeekEnd()) + 2), Phpfox::getTime('Y'));
    }

    /**
     * Gets the time stamp of the start of week.
     *
     * @param int $time By default we use the current time, however you can pass a unix time stamp to check when the next start week is from that date.
     *
     * @return int Unix time stamp
     */
    public function getWeekStart($time = null)
    {
        $weekStartEnd = $this->getWeekStartEnd($time);
        return $weekStartEnd['start'];
    }

    /**
     * Gets the time stamp of the month we are int.
     *
     * @return int Unix time stamp
     */
    public function getThisMonth()
    {
        return ($this->mktime(0, 0, 0, Phpfox::getTime('m'), 1, Phpfox::getTime('Y')));
    }

    /**
     * Get the week start and end of week.
     *
     * @param int $time By default we use the current time, however you can pass a unix time stamp to check when the next week is from that date.
     *
     * @return array Keys are 'start' and 'end'.
     */
    public function getWeekStartEnd($time = null)
    {
        $startOfWeek = Phpfox::getParam('core.start_of_week', 'mon'); // default is mon
        $startOfWeek = $this->_aDayTable[$startOfWeek]; // get number of start of week

        if ($time == null) {
            $time = PHPFOX_TIME;
        }

        // The timestamp of day.
        $day = Phpfox::getLib('date')->mktime(0, 0, 0, Phpfox::getTime('m', $time), Phpfox::getTime('d', $time), Phpfox::getTime('Y', $time));

        $weekday = date('w', $day);
        if ($weekday < $startOfWeek)
            $weekday += 7;

        // The most recent week start day on or before $day.
        $start = $day - DAY_IN_SECONDS * ($weekday - $startOfWeek);

        // $start + 1 week - 1 second.
        $end = $start + WEEK_IN_SECONDS - 1;
        return compact('start', 'end');
    }

    /**
     * Finds the upcoming weekend
     *
     * @param int $time By default we use the current time, however you can pass a unix time stamp to check when the next weekend is from that date.
     *
     * @return int Returns How many days until the next weekend
     */
    public function getWeekEnd($time = null)
    {
        $weekStartEnd = $this->getWeekStartEnd($time);
        return $weekStartEnd['end'];
    }

    /**
     * Converts the time passed based on the offset passed from GMT
     *
     * @param int    $iTime   Current time stamp to convert from GMT
     * @param string $sOffset Offset of the time zone from GMT
     *
     * @return int Returns the time stamp converted from GMT
     * @see Provided by http://www.konsort.org/
     */
    public function convertFromGmt($iTime, $sOffset = '')
    {
        if (empty($sOffset)) {
            $sOffset = $this->getGmtOffset($iTime);
        }
        return (int)(!empty($sOffset) ? (substr($sOffset, 0, 1) == '-' ? ($iTime
            - (substr($sOffset, 1) * HOUR_IN_SECONDS)) : $iTime + ($sOffset * HOUR_IN_SECONDS))
            : $iTime);
    }

    /**
     * Converts the time passed based on the offset passed into GMT
     *
     * @param int    $iTime   Current time stamp to convert into GMT
     * @param string $sOffset Offset of the time zone to GMT
     *
     * @return int Returns the time stamp as GMT
     * @see Provided by http://www.konsort.org/
     */
    public function convertToGmt($iTime)
    {
        $sUserOffSet = $this->getGmtOffset($iTime);
        return (int)(!empty($sUserOffSet) ? (substr($sUserOffSet, 0, 1) == '-'
            ? ($iTime + (int)(substr($sUserOffSet, 1) * HOUR_IN_SECONDS))
            : $iTime - ($sUserOffSet * HOUR_IN_SECONDS)) : $iTime);
    }

    /**
     * Converts a time stamp into a human readable phrase.
     *
     * @param int    $iTime        Time stamp to convert
     * @param string $sDefault     Default phrase to use with the display of the
     *                             time stamp
     *
     * @param bool   $bIsShortType Short type of date
     *
     * @return string Returns the readable phrase with the time stamp included.
     */
    public function convertTime($iTime, $sDefault = null, $bIsShortType = false)
    {
        $iSeconds = (int)round(abs(PHPFOX_TIME - $iTime));
        $iMinutes = (int)round($iSeconds / MINUTE_IN_SECONDS);

        if ($iMinutes < 1) {
            if ($iSeconds === 0 || $iSeconds === 1) {
                return _p('1_second_ago');
            }
            return _p('total_seconds_ago', ['total' => $iSeconds]);
        }

        if ($iMinutes < MINUTE_IN_SECONDS) {
            if ($iMinutes === 0 || $iMinutes === 1) {
                return _p('1_minute_ago');
            }
            return _p('total_minutes_ago', ['total' => $iMinutes]);
        }

        $iHours = (int)round(floatval($iMinutes) / MINUTE_IN_SECONDS);

        if ($iHours < 24) {
            if ($iHours === 0 || $iHours === 1) {
                return _p('1_hour_ago');
            }

            return _p('total_hours_ago', ['total' => $iHours]);
        }

        if ($iHours < 48
            && ((int)date('d', PHPFOX_TIME) - 1) == date('d', $iTime)
        ) {
            return _p('yesterday') . ', '
                . Phpfox::getTime(Phpfox::getParam('core.conver_time_to_string'),
                    $iTime);
        }

        return Phpfox::getTime(Phpfox::getParam(($sDefault === null
            ? 'core.global_update_time' : $sDefault)), $iTime, true, $bIsShortType);
    }

    /**
     * Gets the offset from the current timezone being used to GMT.
     *
     * @param $iTime
     *
     * @return float|int|mixed|string|null
     * @throws Exception
     */
    public function getGmtOffset($iTime)
    {
        return Phpfox::getTimeZone(true, $iTime);
    }

    /**
     * Modify the current hour of a time stamp based on DST settings
     *
     * @param string $sTime Time stamp
     * @param bool   $bAmPm Not being used at the moment
     *
     * @return int Converted time stamp is returned
     */
    public function modifyHours($sTime, $bAmPm = false)
    {
        $iCurrentHour = (int)Phpfox::getTime('H');
        $iTime = 0;
        if (substr($sTime, 0, 1) == '+') {
            $iTime = (int)substr_replace($sTime, '', 0, 1);
        }

        $iNewTime = ($iTime + $iCurrentHour);

        if ($iNewTime >= 24) {
            $iNewTime = ($iNewTime - 24);
        }

        return $iNewTime;
    }

    /**
     * Check to see if we are to use DST support.
     *
     * @return bool TRUE if we use DST, FALSE if we don't
     */
    private static function _isDst()
    {
        if (!Phpfox::getParam('core.identify_dst')) {
            return false;
        }

        return (Phpfox::getUserBy('dst_check') ? true : false);
    }

    /**
     * @param int    $iTime
     * @param string $sTime
     *
     * @return bool
     */
    public function isToday($iTime, $sTime = 'today')
    {
        $date = date('Y-m-d', $iTime);
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $bReturn = false;
        switch ($sTime) {
            case 'today':
                $bReturn = ($date == $today) ? true : false;
                break;
            case 'tomorrow':
                $bReturn = ($date == $tomorrow) ? true : false;
                break;
            case 'yesterday':
                $bReturn = ($date == $yesterday) ? true : false;
                break;
        }
        return $bReturn;
    }
}