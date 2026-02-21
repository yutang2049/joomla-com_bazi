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
                if (isset($termData['timestamp']))
                {
                    $terms[$key] = [
                        'name' => $termData['name'],
                        'name_zh' => $termData['name_zh'],
                        'timestamp' => new DateTime($termData['timestamp'], new DateTimeZone('UTC')),
                        'sun_longitude' => $termData['sun_longitude'] ?? null,
                    ];
                }
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
     *
     * @param   DateTime  $datetime  Date/time to check
     *
     * @return  integer  Month number (1-12)
     *
     * @since   1.0.0
     */
    public static function getSolarTermMonth(DateTime $datetime): int
    {
        $year = (int) $datetime->format('Y');
        $terms = self::getSolarTerms($year);

        if (!$terms)
        {
            return (int) $datetime->format('n');
        }

        // Define the Jie Qi that mark month boundaries
        $monthTerms = [
            1  => 'lichun',
            2  => 'jingzhe',
            3  => 'qingming',
            4  => 'lixia',
            5  => 'mangzhong',
            6  => 'xiaoshu',
            7  => 'liqiu',
            8  => 'bailu',
            9  => 'hanlu',
            10 => 'lidong',
            11 => 'daxue',
            12 => 'xiaohan',
        ];

        for ($month = 1; $month <= 12; $month++)
        {
            $termKey = $monthTerms[$month];
            $nextMonth = $month + 1;

            if ($nextMonth > 12)
            {
                $nextYear = $year + 1;
                $nextTerms = self::getSolarTerms($nextYear);
                $nextTermKey = $monthTerms[1];

                if ($nextTerms && isset($nextTerms[$nextTermKey]) && isset($terms[$termKey]))
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

        return 12;
    }

    /**
     * Get the next solar term (Jie Qi) after a given datetime
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

        $jieQiNames = ['lichun', 'jingzhe', 'qingming', 'lixia', 'mangzhong', 'xiaoshu',
                       'liqiu', 'bailu', 'hanlu', 'lidong', 'daxue', 'xiaohan'];

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
     * Get the previous solar term (Jie Qi) before a given datetime
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

        $jieQiNames = array_reverse(['lichun', 'jingzhe', 'qingming', 'lixia', 'mangzhong', 'xiaoshu',
                                      'liqiu', 'bailu', 'hanlu', 'lidong', 'daxue', 'xiaohan']);

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
