<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Site\Helper;

defined('_JEXEC') or die;

/**
 * Helper class for Ganzhi (干支) conversions and calculations
 *
 * @since  1.0.0
 */
class GanzhiHelper
{
    /**
     * Ten Heavenly Stems (天干)
     */
    const STEMS = [
        0 => ['pinyin' => 'jia', 'chinese' => '甲', 'element' => 'wood', 'yinyang' => 'yang'],
        1 => ['pinyin' => 'yi', 'chinese' => '乙', 'element' => 'wood', 'yinyang' => 'yin'],
        2 => ['pinyin' => 'bing', 'chinese' => '丙', 'element' => 'fire', 'yinyang' => 'yang'],
        3 => ['pinyin' => 'ding', 'chinese' => '丁', 'element' => 'fire', 'yinyang' => 'yin'],
        4 => ['pinyin' => 'wu', 'chinese' => '戊', 'element' => 'earth', 'yinyang' => 'yang'],
        5 => ['pinyin' => 'ji', 'chinese' => '己', 'element' => 'earth', 'yinyang' => 'yin'],
        6 => ['pinyin' => 'geng', 'chinese' => '庚', 'element' => 'metal', 'yinyang' => 'yang'],
        7 => ['pinyin' => 'xin', 'chinese' => '辛', 'element' => 'metal', 'yinyang' => 'yin'],
        8 => ['pinyin' => 'ren', 'chinese' => '壬', 'element' => 'water', 'yinyang' => 'yang'],
        9 => ['pinyin' => 'gui', 'chinese' => '癸', 'element' => 'water', 'yinyang' => 'yin'],
    ];

    /**
     * Twelve Earthly Branches (地支)
     */
    const BRANCHES = [
        0  => ['pinyin' => 'zi', 'chinese' => '子', 'element' => 'water', 'zodiac' => 'rat'],
        1  => ['pinyin' => 'chou', 'chinese' => '丑', 'element' => 'earth', 'zodiac' => 'ox'],
        2  => ['pinyin' => 'yin', 'chinese' => '寅', 'element' => 'wood', 'zodiac' => 'tiger'],
        3  => ['pinyin' => 'mao', 'chinese' => '卯', 'element' => 'wood', 'zodiac' => 'rabbit'],
        4  => ['pinyin' => 'chen', 'chinese' => '辰', 'element' => 'earth', 'zodiac' => 'dragon'],
        5  => ['pinyin' => 'si', 'chinese' => '巳', 'element' => 'fire', 'zodiac' => 'snake'],
        6  => ['pinyin' => 'wu', 'chinese' => '午', 'element' => 'fire', 'zodiac' => 'horse'],
        7  => ['pinyin' => 'wei', 'chinese' => '未', 'element' => 'earth', 'zodiac' => 'goat'],
        8  => ['pinyin' => 'shen', 'chinese' => '申', 'element' => 'metal', 'zodiac' => 'monkey'],
        9  => ['pinyin' => 'you', 'chinese' => '酉', 'element' => 'metal', 'zodiac' => 'rooster'],
        10 => ['pinyin' => 'xu', 'chinese' => '戌', 'element' => 'earth', 'zodiac' => 'dog'],
        11 => ['pinyin' => 'hai', 'chinese' => '亥', 'element' => 'water', 'zodiac' => 'pig'],
    ];

    /**
     * Get stem for a given year (base year 4 = 甲 jiǎ)
     *
     * @param   integer  $year  Year
     *
     * @return  array  Stem information
     *
     * @since   1.0.0
     */
    public static function getYearStem(int $year): array
    {
        $index = ($year - 4) % 10;
        return self::STEMS[$index];
    }

    /**
     * Get branch for a given year (base year 4 = 子 zǐ)
     *
     * @param   integer  $year  Year
     *
     * @return  array  Branch information
     *
     * @since   1.0.0
     */
    public static function getYearBranch(int $year): array
    {
        $index = ($year - 4) % 12;
        return self::BRANCHES[$index];
    }

    /**
     * Get Ganzhi for a given year
     *
     * @param   integer  $year  Year
     *
     * @return  array  ['stem' => array, 'branch' => array]
     *
     * @since   1.0.0
     */
    public static function getYearGanzhi(int $year): array
    {
        return [
            'stem' => self::getYearStem($year),
            'branch' => self::getYearBranch($year),
        ];
    }

    /**
     * Get month stem based on year stem and month index (1-12)
     * Formula: Month stem = (Year stem index * 2 + Month) % 10
     *
     * @param   integer  $yearStemIndex  Year stem index (0-9)
     * @param   integer  $monthIndex     Month index (1-12, 1=寅 tiger month starting from 立春)
     *
     * @return  array  Stem information
     *
     * @since   1.0.0
     */
    public static function getMonthStem(int $yearStemIndex, int $monthIndex): array
    {
        $index = ($yearStemIndex * 2 + $monthIndex) % 10;
        return self::STEMS[$index];
    }

    /**
     * Get month branch based on month index
     * Month 1 (立春) = 寅 (index 2)
     *
     * @param   integer  $monthIndex  Month index (1-12)
     *
     * @return  array  Branch information
     *
     * @since   1.0.0
     */
    public static function getMonthBranch(int $monthIndex): array
    {
        $index = ($monthIndex + 1) % 12;
        return self::BRANCHES[$index];
    }

    /**
     * Get day Ganzhi using Julian Day Number
     * Day 0 (0000-01-01) = 甲子 (stem 0, branch 0)
     *
     * @param   integer  $jd  Julian Day Number
     *
     * @return  array  ['stem' => array, 'branch' => array]
     *
     * @since   1.0.0
     */
    public static function getDayGanzhi(int $jd): array
    {
        $offset = $jd + 10; // Adjust for cycle start
        $stemIndex = $offset % 10;
        $branchIndex = $offset % 12;

        return [
            'stem' => self::STEMS[$stemIndex],
            'branch' => self::BRANCHES[$branchIndex],
        ];
    }

    /**
     * Get hour branch based on hour (0-23)
     * 子时 23:00-01:00 = 0
     * 丑时 01:00-03:00 = 1
     * etc.
     *
     * @param   integer  $hour  Hour (0-23)
     *
     * @return  array  Branch information
     *
     * @since   1.0.0
     */
    public static function getHourBranch(int $hour): array
    {
        // Adjust hour to Chinese double-hour system
        // 23:00-01:00 = 子, 01:00-03:00 = 丑, etc.
        $index = (int) floor(($hour + 1) / 2) % 12;
        return self::BRANCHES[$index];
    }

    /**
     * Get hour stem based on day stem and hour branch
     * Formula: Hour stem = (Day stem index * 2 + Hour branch index) % 10
     *
     * @param   integer  $dayStemIndex    Day stem index (0-9)
     * @param   integer  $hourBranchIndex Hour branch index (0-11)
     *
     * @return  array  Stem information
     *
     * @since   1.0.0
     */
    public static function getHourStem(int $dayStemIndex, int $hourBranchIndex): array
    {
        $index = ($dayStemIndex * 2 + $hourBranchIndex) % 10;
        return self::STEMS[$index];
    }

    /**
     * Get the next Ganzhi in 60-cycle
     *
     * @param   integer  $stemIndex   Current stem index (0-9)
     * @param   integer  $branchIndex Current branch index (0-11)
     * @param   integer  $steps       Number of steps forward (default 1)
     *
     * @return  array  ['stem' => array, 'branch' => array]
     *
     * @since   1.0.0
     */
    public static function getNextGanzhi(int $stemIndex, int $branchIndex, int $steps = 1): array
    {
        $newStemIndex = ($stemIndex + $steps) % 10;
        $newBranchIndex = ($branchIndex + $steps) % 12;

        return [
            'stem' => self::STEMS[$newStemIndex],
            'branch' => self::BRANCHES[$newBranchIndex],
        ];
    }

    /**
     * Get the previous Ganzhi in 60-cycle
     *
     * @param   integer  $stemIndex   Current stem index (0-9)
     * @param   integer  $branchIndex Current branch index (0-11)
     * @param   integer  $steps       Number of steps backward (default 1)
     *
     * @return  array  ['stem' => array, 'branch' => array]
     *
     * @since   1.0.0
     */
    public static function getPreviousGanzhi(int $stemIndex, int $branchIndex, int $steps = 1): array
    {
        $newStemIndex = ($stemIndex - $steps + 100) % 10; // +100 to handle negative
        $newBranchIndex = ($branchIndex - $steps + 120) % 12; // +120 to handle negative

        return [
            'stem' => self::STEMS[$newStemIndex],
            'branch' => self::BRANCHES[$newBranchIndex],
        ];
    }

    /**
     * Format Ganzhi as Chinese string
     *
     * @param   array  $ganzhi  Ganzhi array with 'stem' and 'branch'
     *
     * @return  string  Chinese Ganzhi string
     *
     * @since   1.0.0
     */
    public static function formatGanzhi(array $ganzhi): string
    {
        return $ganzhi['stem']['chinese'] . $ganzhi['branch']['chinese'];
    }
}
