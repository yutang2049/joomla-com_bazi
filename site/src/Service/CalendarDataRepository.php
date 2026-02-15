<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Site\Service;

defined('_JEXEC') or die;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Calendar Data Repository for loading solar terms and lunar calendar data
 *
 * @since  1.0.0
 */
class CalendarDataRepository
{
    /**
     * Cached solar terms data
     *
     * @var    array|null
     * @since  1.0.0
     */
    protected static $solarTermsData = null;

    /**
     * Path to data directory
     *
     * @var    string
     * @since  1.0.0
     */
    protected static $dataPath = null;

    /**
     * Initialize the repository
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public static function init(): void
    {
        if (self::$dataPath === null)
        {
            self::$dataPath = dirname(__DIR__) . '/Data';
        }
    }

    /**
     * Load solar terms data
     *
     * @return  array  Solar terms data
     *
     * @since   1.0.0
     * @throws  Exception
     */
    protected static function loadSolarTermsData(): array
    {
        if (self::$solarTermsData !== null)
        {
            return self::$solarTermsData;
        }

        self::init();

        $filePath = self::$dataPath . '/solar_terms_sample.json';

        if (!file_exists($filePath))
        {
            throw new Exception('Solar terms data file not found: ' . $filePath);
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!$data)
        {
            throw new Exception('Failed to parse solar terms data');
        }

        self::$solarTermsData = $data;
        return self::$solarTermsData;
    }

    /**
     * Get Lichun (立春) timestamp for a given year
     *
     * @param   integer  $year  Year
     *
     * @return  DateTime|null  Lichun datetime or null if not found
     *
     * @since   1.0.0
     */
    public static function getLichun(int $year): ?DateTime
    {
        try
        {
            $data = self::loadSolarTermsData();

            if (!isset($data[(string) $year]['lichun']))
            {
                return null;
            }

            $timestamp = $data[(string) $year]['lichun']['timestamp'];
            return new DateTime($timestamp, new DateTimeZone('UTC'));
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get all solar terms for a given year
     *
     * @param   integer  $year  Year
     *
     * @return  array|null  Array of solar terms with DateTime objects
     *
     * @since   1.0.0
     */
    public static function getSolarTerms(int $year): ?array
    {
        try
        {
            $data = self::loadSolarTermsData();

            if (!isset($data[(string) $year]))
            {
                return null;
            }

            $terms = [];
            foreach ($data[(string) $year] as $key => $termData)
            {
                $terms[$key] = [
                    'name' => $termData['name'],
                    'name_zh' => $termData['name_zh'],
                    'timestamp' => new DateTime($termData['timestamp'], new DateTimeZone('UTC')),
                    'sun_longitude' => $termData['sun_longitude'] ?? null,
                ];
            }

            return $terms;
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Get solar term by name for a given year
     *
     * @param   integer  $year      Year
     * @param   string   $termName  Term name (e.g., 'lichun', 'yushui')
     *
     * @return  DateTime|null  Solar term datetime or null if not found
     *
     * @since   1.0.0
     */
    public static function getSolarTerm(int $year, string $termName): ?DateTime
    {
        try
        {
            $data = self::loadSolarTermsData();

            if (!isset($data[(string) $year][$termName]))
            {
                return null;
            }

            $timestamp = $data[(string) $year][$termName]['timestamp'];
            return new DateTime($timestamp, new DateTimeZone('UTC'));
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    /**
     * Determine which month (1-12) a date belongs to based on solar terms
     * Month starts at each odd-numbered solar term (Jie Qi 节气)
     *
     * @param   DateTime  $datetime  Date/time to check
     *
     * @return  integer  Month number (1-12), where 1 = 立春 to 惊蛰
     *
     * @since   1.0.0
     */
    public static function getSolarTermMonth(DateTime $datetime): int
    {
        $year = (int) $datetime->format('Y');
        $terms = self::getSolarTerms($year);

        if (!$terms)
        {
            // Fallback to Gregorian month approximation
            return (int) $datetime->format('n');
        }

        // Define the Jie Qi (odd-numbered solar terms) that mark month boundaries
        $monthTerms = [
            1  => 'lichun',     // 立春 (Feb 3-5)
            2  => 'jingzhe',    // 惊蛰 (Mar 5-7)
            3  => 'qingming',   // 清明 (Apr 4-6)
            4  => 'lixia',      // 立夏 (May 5-7)
            5  => 'mangzhong',  // 芒种 (Jun 5-7)
            6  => 'xiaoshu',    // 小暑 (Jul 6-8)
            7  => 'liqiu',      // 立秋 (Aug 7-9)
            8  => 'bailu',      // 白露 (Sep 7-9)
            9  => 'hanlu',      // 寒露 (Oct 8-9)
            10 => 'lidong',     // 立冬 (Nov 7-8)
            11 => 'daxue',      // 大雪 (Dec 6-8)
            12 => 'xiaohan',    // 小寒 (Jan 5-7)
        ];

        // Check which month the datetime falls into
        for ($month = 1; $month <= 12; $month++)
        {
            $termKey = $monthTerms[$month];
            $nextMonth = $month + 1;
            
            // Handle year boundary
            if ($nextMonth > 12)
            {
                $nextYear = $year + 1;
                $nextTerms = self::getSolarTerms($nextYear);
                $nextTermKey = $monthTerms[1]; // 立春 of next year
                
                if ($nextTerms && isset($nextTerms[$nextTermKey]))
                {
                    $termStart = $terms[$termKey]['timestamp'];
                    $termEnd = $nextTerms[$nextTermKey]['timestamp'];
                    
                    if ($datetime >= $termStart && $datetime < $termEnd)
                    {
                        return $month;
                    }
                }
            }
            else
            {
                $nextTermKey = $monthTerms[$nextMonth];
                
                if (isset($terms[$termKey]) && isset($terms[$nextTermKey]))
                {
                    $termStart = $terms[$termKey]['timestamp'];
                    $termEnd = $terms[$nextTermKey]['timestamp'];
                    
                    if ($datetime >= $termStart && $datetime < $termEnd)
                    {
                        return $month;
                    }
                }
            }
        }

        // Default to month 12 if all else fails
        return 12;
    }

    /**
     * Get the next solar term (Jie Qi - odd-numbered term) after a given datetime
     *
     * @param   DateTime  $datetime  Reference datetime
     *
     * @return  array|null  ['name' => string, 'timestamp' => DateTime] or null
     *
     * @since   1.0.0
     */
    public static function getNextJieQi(DateTime $datetime): ?array
    {
        $year = (int) $datetime->format('Y');
        $terms = self::getSolarTerms($year);

        if (!$terms)
        {
            return null;
        }

        // Jie Qi (odd-numbered solar terms)
        $jieQiNames = ['lichun', 'jingzhe', 'qingming', 'lixia', 'mangzhong', 'xiaoshu',
                       'liqiu', 'bailu', 'hanlu', 'lidong', 'daxue', 'xiaohan'];

        // Find the next Jie Qi in current year
        foreach ($jieQiNames as $name)
        {
            if (isset($terms[$name]) && $terms[$name]['timestamp'] > $datetime)
            {
                return [
                    'name' => $name,
                    'name_zh' => $terms[$name]['name_zh'],
                    'timestamp' => $terms[$name]['timestamp'],
                ];
            }
        }

        // If not found in current year, try next year
        $nextYear = $year + 1;
        $nextTerms = self::getSolarTerms($nextYear);

        if ($nextTerms && isset($nextTerms['lichun']))
        {
            return [
                'name' => 'lichun',
                'name_zh' => $nextTerms['lichun']['name_zh'],
                'timestamp' => $nextTerms['lichun']['timestamp'],
            ];
        }

        return null;
    }

    /**
     * Get the previous solar term (Jie Qi - odd-numbered term) before a given datetime
     *
     * @param   DateTime  $datetime  Reference datetime
     *
     * @return  array|null  ['name' => string, 'timestamp' => DateTime] or null
     *
     * @since   1.0.0
     */
    public static function getPreviousJieQi(DateTime $datetime): ?array
    {
        $year = (int) $datetime->format('Y');
        $terms = self::getSolarTerms($year);

        if (!$terms)
        {
            return null;
        }

        // Jie Qi (odd-numbered solar terms) in reverse order
        $jieQiNames = array_reverse(['lichun', 'jingzhe', 'qingming', 'lixia', 'mangzhong', 'xiaoshu',
                                      'liqiu', 'bailu', 'hanlu', 'lidong', 'daxue', 'xiaohan']);

        // Find the previous Jie Qi in current year
        foreach ($jieQiNames as $name)
        {
            if (isset($terms[$name]) && $terms[$name]['timestamp'] < $datetime)
            {
                return [
                    'name' => $name,
                    'name_zh' => $terms[$name]['name_zh'],
                    'timestamp' => $terms[$name]['timestamp'],
                ];
            }
        }

        // If not found in current year, try previous year
        $prevYear = $year - 1;
        $prevTerms = self::getSolarTerms($prevYear);

        if ($prevTerms && isset($prevTerms['xiaohan']))
        {
            return [
                'name' => 'xiaohan',
                'name_zh' => $prevTerms['xiaohan']['name_zh'],
                'timestamp' => $prevTerms['xiaohan']['timestamp'],
            ];
        }

        return null;
    }
}
