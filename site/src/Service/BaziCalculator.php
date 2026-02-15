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
use Yutang\Component\Bazi\Site\Helper\GanzhiHelper;

/**
 * BaZi Calculator Service
 * Main calculation engine for 八字排盘
 *
 * @since  1.0.0
 */
class BaziCalculator
{
    /**
     * Algorithm version
     *
     * @var    string
     * @since  1.0.0
     */
    const ALGO_VERSION = '1.0.0';

    /**
     * Calculate complete BaZi chart
     *
     * @param   DateTime  $birthDateTime  Birth date/time (normalized with true solar time if applicable)
     * @param   string    $gender         Gender ('male' or 'female')
     * @param   array     $options        Calculation options
     *
     * @return  array  Complete BaZi calculation result
     *
     * @since   1.0.0
     * @throws  Exception
     */
    public function calculate(DateTime $birthDateTime, string $gender, array $options = []): array
    {
        // Calculate Four Pillars (四柱八字)
        $fourPillars = $this->calculateFourPillars($birthDateTime);

        // Calculate Dayun (大运)
        $dayun = $this->calculateDayun($birthDateTime, $gender, $fourPillars);

        // Calculate Liunian (流年)
        $liunianRange = $options['liunian_range'] ?? 5;
        $liunian = $this->calculateLiunian($liunianRange);

        // Calculate Shensha (神煞)
        $shensha = $this->calculateShensha($fourPillars);

        return [
            'algo_version' => self::ALGO_VERSION,
            'birth_datetime' => $birthDateTime->format('c'),
            'gender' => $gender,
            'four_pillars' => $fourPillars,
            'dayun' => $dayun,
            'liunian' => $liunian,
            'shensha' => $shensha,
            'options' => $options,
        ];
    }

    /**
     * Calculate Four Pillars (四柱八字)
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     *
     * @return  array  Four pillars data
     *
     * @since   1.0.0
     * @throws  Exception
     */
    protected function calculateFourPillars(DateTime $birthDateTime): array
    {
        $year = (int) $birthDateTime->format('Y');
        $month = (int) $birthDateTime->format('n');
        $day = (int) $birthDateTime->format('j');
        $hour = (int) $birthDateTime->format('G');

        // Year Pillar: Use 立春 boundary
        $yearPillar = $this->calculateYearPillar($birthDateTime);

        // Month Pillar: Use 节气定月
        $monthPillar = $this->calculateMonthPillar($birthDateTime, $yearPillar);

        // Day Pillar: Use Julian Day Number
        $dayPillar = $this->calculateDayPillar($birthDateTime);

        // Hour Pillar
        $hourPillar = $this->calculateHourPillar($hour, $dayPillar);

        return [
            'year' => $yearPillar,
            'month' => $monthPillar,
            'day' => $dayPillar,
            'hour' => $hourPillar,
        ];
    }

    /**
     * Calculate Year Pillar using 立春 boundary
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     *
     * @return  array  Year pillar data
     *
     * @since   1.0.0
     */
    protected function calculateYearPillar(DateTime $birthDateTime): array
    {
        $year = (int) $birthDateTime->format('Y');
        
        // Get Lichun of current year
        $lichun = CalendarDataRepository::getLichun($year);

        // If Lichun data available and birth is before Lichun, use previous year's ganzhi
        if ($lichun && $birthDateTime < $lichun)
        {
            $year--;
        }
        elseif ($lichun === null)
        {
            // Fallback: approximate Lichun as Feb 4
            $approxLichun = new DateTime($year . '-02-04 00:00:00', new \DateTimeZone('UTC'));
            if ($birthDateTime < $approxLichun)
            {
                $year--;
            }
        }

        $ganzhi = GanzhiHelper::getYearGanzhi($year);

        return [
            'year' => $year,
            'stem' => $ganzhi['stem'],
            'branch' => $ganzhi['branch'],
            'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
        ];
    }

    /**
     * Calculate Month Pillar using 节气定月
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     * @param   array     $yearPillar     Year pillar data
     *
     * @return  array  Month pillar data
     *
     * @since   1.0.0
     */
    protected function calculateMonthPillar(DateTime $birthDateTime, array $yearPillar): array
    {
        // Get solar term month (1-12)
        $monthIndex = CalendarDataRepository::getSolarTermMonth($birthDateTime);

        // Get month stem based on year stem
        $yearStemIndex = array_search($yearPillar['stem']['chinese'], array_column(GanzhiHelper::STEMS, 'chinese'));
        $monthStem = GanzhiHelper::getMonthStem($yearStemIndex, $monthIndex);

        // Get month branch
        $monthBranch = GanzhiHelper::getMonthBranch($monthIndex);

        $ganzhi = [
            'stem' => $monthStem,
            'branch' => $monthBranch,
        ];

        return [
            'month_index' => $monthIndex,
            'stem' => $monthStem,
            'branch' => $monthBranch,
            'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
        ];
    }

    /**
     * Calculate Day Pillar using Julian Day Number
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     *
     * @return  array  Day pillar data
     *
     * @since   1.0.0
     */
    protected function calculateDayPillar(DateTime $birthDateTime): array
    {
        // Convert to Julian Day Number
        $jd = gregoriantojd(
            (int) $birthDateTime->format('n'),
            (int) $birthDateTime->format('j'),
            (int) $birthDateTime->format('Y')
        );

        $ganzhi = GanzhiHelper::getDayGanzhi($jd);

        return [
            'jd' => $jd,
            'stem' => $ganzhi['stem'],
            'branch' => $ganzhi['branch'],
            'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
        ];
    }

    /**
     * Calculate Hour Pillar
     *
     * @param   integer  $hour       Hour (0-23)
     * @param   array    $dayPillar  Day pillar data
     *
     * @return  array  Hour pillar data
     *
     * @since   1.0.0
     */
    protected function calculateHourPillar(int $hour, array $dayPillar): array
    {
        // Get hour branch
        $hourBranch = GanzhiHelper::getHourBranch($hour);

        // Get hour stem based on day stem
        $dayStemIndex = array_search($dayPillar['stem']['chinese'], array_column(GanzhiHelper::STEMS, 'chinese'));
        $hourBranchIndex = array_search($hourBranch['chinese'], array_column(GanzhiHelper::BRANCHES, 'chinese'));
        $hourStem = GanzhiHelper::getHourStem($dayStemIndex, $hourBranchIndex);

        $ganzhi = [
            'stem' => $hourStem,
            'branch' => $hourBranch,
        ];

        return [
            'hour' => $hour,
            'stem' => $hourStem,
            'branch' => $hourBranch,
            'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
        ];
    }

    /**
     * Calculate Dayun (大运) - Major Luck Cycles
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     * @param   string    $gender         Gender ('male' or 'female')
     * @param   array     $fourPillars    Four pillars data
     *
     * @return  array  Dayun data
     *
     * @since   1.0.0
     */
    protected function calculateDayun(DateTime $birthDateTime, string $gender, array $fourPillars): array
    {
        $yearStem = $fourPillars['year']['stem'];
        
        // Determine direction: 阳男阴女顺; 阴男阳女逆
        $isYangStem = ($yearStem['yinyang'] === 'yang');
        $isMale = ($gender === 'male');
        
        $isForward = ($isYangStem && $isMale) || (!$isYangStem && !$isMale);
        
        // Get reference Jie Qi for starting age calculation
        if ($isForward)
        {
            $referenceJieQi = CalendarDataRepository::getNextJieQi($birthDateTime);
        }
        else
        {
            $referenceJieQi = CalendarDataRepository::getPreviousJieQi($birthDateTime);
        }
        
        // If no Jie Qi data available, use approximate fallback
        if ($referenceJieQi === null)
        {
            $referenceJieQi = $this->getApproximateJieQi($birthDateTime, $isForward);
        }
        
        // Calculate starting age (3 days = 1 year)
        $startAge = $this->calculateDayunStartAge($birthDateTime, $referenceJieQi);
        
        // Generate 8 Dayun cycles (10 years each)
        $cycles = $this->generateDayunCycles($fourPillars['month'], $isForward, $startAge);
        
        return [
            'direction' => $isForward ? 'forward' : 'backward',
            'start_age' => $startAge,
            'reference_jieqi' => $referenceJieQi,
            'cycles' => $cycles,
        ];
    }

    /**
     * Calculate Dayun starting age
     *
     * @param   DateTime  $birthDateTime    Birth date/time
     * @param   array     $referenceJieQi   Reference Jie Qi data
     *
     * @return  array  Starting age breakdown
     *
     * @since   1.0.0
     */
    protected function calculateDayunStartAge(DateTime $birthDateTime, array $referenceJieQi): array
    {
        $jieqiTime = $referenceJieQi['timestamp'];
        $diff = $birthDateTime->diff($jieqiTime);
        
        $totalDays = abs($diff->days);
        
        // 3 days = 1 year
        $years = intval($totalDays / 3);
        $remainingDays = $totalDays % 3;
        
        // 1 day = 4 months
        $months = intval($remainingDays * 4);
        
        return [
            'years' => $years,
            'months' => $months,
            'total_days' => $totalDays,
        ];
    }

    /**
     * Generate Dayun cycles
     *
     * @param   array    $monthPillar  Month pillar data
     * @param   boolean  $isForward    Direction (true = forward, false = backward)
     * @param   array    $startAge     Starting age data
     *
     * @return  array  Array of Dayun cycles
     *
     * @since   1.0.0
     */
    protected function generateDayunCycles(array $monthPillar, bool $isForward, array $startAge): array
    {
        $cycles = [];
        $stemIndex = array_search($monthPillar['stem']['chinese'], array_column(GanzhiHelper::STEMS, 'chinese'));
        $branchIndex = array_search($monthPillar['branch']['chinese'], array_column(GanzhiHelper::BRANCHES, 'chinese'));
        
        $baseAge = $startAge['years'];
        
        for ($i = 0; $i < 8; $i++)
        {
            if ($isForward)
            {
                $ganzhi = GanzhiHelper::getNextGanzhi($stemIndex, $branchIndex, $i + 1);
            }
            else
            {
                $ganzhi = GanzhiHelper::getPreviousGanzhi($stemIndex, $branchIndex, $i + 1);
            }
            
            $startAge = $baseAge + ($i * 10);
            $endAge = $startAge + 9;
            
            $cycles[] = [
                'cycle' => $i + 1,
                'age_start' => $startAge,
                'age_end' => $endAge,
                'stem' => $ganzhi['stem'],
                'branch' => $ganzhi['branch'],
                'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
            ];
        }
        
        return $cycles;
    }

    /**
     * Calculate Liunian (流年) - Annual Fortunes
     *
     * @param   integer  $range  Range of years (±) around current year
     *
     * @return  array  Liunian data
     *
     * @since   1.0.0
     */
    protected function calculateLiunian(int $range = 5): array
    {
        $currentYear = (int) date('Y');
        $liunian = [];
        
        for ($offset = -$range; $offset <= $range; $offset++)
        {
            $year = $currentYear + $offset;
            $ganzhi = GanzhiHelper::getYearGanzhi($year);
            
            $liunian[] = [
                'year' => $year,
                'stem' => $ganzhi['stem'],
                'branch' => $ganzhi['branch'],
                'ganzhi' => GanzhiHelper::formatGanzhi($ganzhi),
            ];
        }
        
        return $liunian;
    }

    /**
     * Calculate Shensha (神煞) - Spiritual Influences
     *
     * @param   array  $fourPillars  Four pillars data
     *
     * @return  array  Shensha data
     *
     * @since   1.0.0
     */
    protected function calculateShensha(array $fourPillars): array
    {
        $shensha = [];
        
        // 天乙贵人 (Tianyi Nobleman)
        $shensha['tianyi_nobleman'] = $this->calculateTianyiNobleman($fourPillars['day']['stem']);
        
        // 太极贵人 (Taiji Nobleman)
        $shensha['taiji_nobleman'] = $this->calculateTaijiNobleman($fourPillars);
        
        // Bonus: 文昌贵人 (Wenchang Nobleman)
        $shensha['wenchang_nobleman'] = $this->calculateWenchangNobleman($fourPillars['year']['stem']);
        
        // Bonus: 桃花 (Peach Blossom)
        $shensha['peach_blossom'] = $this->calculatePeachBlossom($fourPillars['year']['branch']);
        
        // Bonus: 驿马 (Post Horse)
        $shensha['post_horse'] = $this->calculatePostHorse($fourPillars['year']['branch']);
        
        return $shensha;
    }

    /**
     * Calculate Tianyi Nobleman based on day stem
     *
     * @param   array  $dayStem  Day stem data
     *
     * @return  array  Tianyi nobleman branches
     *
     * @since   1.0.0
     */
    protected function calculateTianyiNobleman(array $dayStem): array
    {
        $tianyiTable = [
            '甲' => ['丑', '未'],
            '乙' => ['子', '申'],
            '丙' => ['亥', '酉'],
            '丁' => ['亥', '酉'],
            '戊' => ['丑', '未'],
            '己' => ['子', '申'],
            '庚' => ['丑', '未'],
            '辛' => ['寅', '午'],
            '壬' => ['卯', '巳'],
            '癸' => ['卯', '巳'],
        ];
        
        return $tianyiTable[$dayStem['chinese']] ?? [];
    }

    /**
     * Calculate Taiji Nobleman
     *
     * @param   array  $fourPillars  Four pillars data
     *
     * @return  array  Taiji nobleman branches
     *
     * @since   1.0.0
     */
    protected function calculateTaijiNobleman(array $fourPillars): array
    {
        // Simplified: 甲乙见子午, 丙丁见卯酉, etc.
        $dayStem = $fourPillars['day']['stem']['chinese'];
        
        $taijiTable = [
            '甲' => ['子', '午'],
            '乙' => ['子', '午'],
            '丙' => ['卯', '酉'],
            '丁' => ['卯', '酉'],
            '戊' => ['辰', '戌', '丑', '未'],
            '己' => ['辰', '戌', '丑', '未'],
            '庚' => ['寅', '亥'],
            '辛' => ['寅', '亥'],
            '壬' => ['巳', '申'],
            '癸' => ['巳', '申'],
        ];
        
        return $taijiTable[$dayStem] ?? [];
    }

    /**
     * Calculate Wenchang Nobleman based on year stem
     *
     * @param   array  $yearStem  Year stem data
     *
     * @return  string|null  Wenchang branch
     *
     * @since   1.0.0
     */
    protected function calculateWenchangNobleman(array $yearStem): ?string
    {
        $wenchangTable = [
            '甲' => '巳',
            '乙' => '午',
            '丙' => '申',
            '丁' => '酉',
            '戊' => '申',
            '己' => '酉',
            '庚' => '亥',
            '辛' => '子',
            '壬' => '寅',
            '癸' => '卯',
        ];
        
        return $wenchangTable[$yearStem['chinese']] ?? null;
    }

    /**
     * Calculate Peach Blossom based on year branch
     *
     * @param   array  $yearBranch  Year branch data
     *
     * @return  string|null  Peach blossom branch
     *
     * @since   1.0.0
     */
    protected function calculatePeachBlossom(array $yearBranch): ?string
    {
        $peachTable = [
            '寅' => '卯', '午' => '卯', '戌' => '卯',  // 寅午戌见卯
            '申' => '酉', '子' => '酉', '辰' => '酉',  // 申子辰见酉
            '巳' => '午', '酉' => '午', '丑' => '午',  // 巳酉丑见午
            '亥' => '子', '卯' => '子', '未' => '子',  // 亥卯未见子
        ];
        
        return $peachTable[$yearBranch['chinese']] ?? null;
    }

    /**
     * Calculate Post Horse based on year branch
     *
     * @param   array  $yearBranch  Year branch data
     *
     * @return  string|null  Post horse branch
     *
     * @since   1.0.0
     */
    protected function calculatePostHorse(array $yearBranch): ?string
    {
        $postHorseTable = [
            '寅' => '申', '午' => '申', '戌' => '申',  // 寅午戌见申
            '申' => '寅', '子' => '寅', '辰' => '寅',  // 申子辰见寅
            '巳' => '亥', '酉' => '亥', '丑' => '亥',  // 巳酉丑见亥
            '亥' => '巳', '卯' => '巳', '未' => '巳',  // 亥卯未见巳
        ];
        
        return $postHorseTable[$yearBranch['chinese']] ?? null;
    }

    /**
     * Get approximate Jie Qi when calendar data is not available
     * Uses simple month-based approximation
     *
     * @param   DateTime  $birthDateTime  Birth date/time
     * @param   boolean   $isForward      Direction (true = forward, false = backward)
     *
     * @return  array  Approximate Jie Qi data
     *
     * @since   1.0.0
     */
    protected function getApproximateJieQi(DateTime $birthDateTime, bool $isForward): array
    {
        $year = (int) $birthDateTime->format('Y');
        $month = (int) $birthDateTime->format('n');
        $day = (int) $birthDateTime->format('j');
        
        // Approximate Jie Qi dates (day of month)
        $jieqiDates = [
            1  => ['name' => 'lichun', 'name_zh' => '立春', 'month' => 2, 'day' => 4],
            2  => ['name' => 'jingzhe', 'name_zh' => '惊蛰', 'month' => 3, 'day' => 6],
            3  => ['name' => 'qingming', 'name_zh' => '清明', 'month' => 4, 'day' => 5],
            4  => ['name' => 'lixia', 'name_zh' => '立夏', 'month' => 5, 'day' => 6],
            5  => ['name' => 'mangzhong', 'name_zh' => '芒种', 'month' => 6, 'day' => 6],
            6  => ['name' => 'xiaoshu', 'name_zh' => '小暑', 'month' => 7, 'day' => 7],
            7  => ['name' => 'liqiu', 'name_zh' => '立秋', 'month' => 8, 'day' => 8],
            8  => ['name' => 'bailu', 'name_zh' => '白露', 'month' => 9, 'day' => 8],
            9  => ['name' => 'hanlu', 'name_zh' => '寒露', 'month' => 10, 'day' => 8],
            10 => ['name' => 'lidong', 'name_zh' => '立冬', 'month' => 11, 'day' => 7],
            11 => ['name' => 'daxue', 'name_zh' => '大雪', 'month' => 12, 'day' => 7],
            12 => ['name' => 'xiaohan', 'name_zh' => '小寒', 'month' => 1, 'day' => 6],
        ];
        
        if ($isForward)
        {
            // Find next Jie Qi
            foreach ($jieqiDates as $idx => $jieqi)
            {
                $jieqiDate = new DateTime(
                    sprintf('%d-%02d-%02d 00:00:00', $year, $jieqi['month'], $jieqi['day']),
                    new \DateTimeZone('UTC')
                );
                
                if ($jieqiDate > $birthDateTime)
                {
                    return [
                        'name' => $jieqi['name'],
                        'name_zh' => $jieqi['name_zh'],
                        'timestamp' => $jieqiDate,
                    ];
                }
            }
            
            // If no future Jie Qi in this year, return next year's first
            $nextYear = $year + 1;
            return [
                'name' => 'lichun',
                'name_zh' => '立春',
                'timestamp' => new DateTime($nextYear . '-02-04 00:00:00', new \DateTimeZone('UTC')),
            ];
        }
        else
        {
            // Find previous Jie Qi
            foreach (array_reverse($jieqiDates, true) as $idx => $jieqi)
            {
                $jieqiDate = new DateTime(
                    sprintf('%d-%02d-%02d 00:00:00', $year, $jieqi['month'], $jieqi['day']),
                    new \DateTimeZone('UTC')
                );
                
                if ($jieqiDate < $birthDateTime)
                {
                    return [
                        'name' => $jieqi['name'],
                        'name_zh' => $jieqi['name_zh'],
                        'timestamp' => $jieqiDate,
                    ];
                }
            }
            
            // If no previous Jie Qi in this year, return previous year's last
            $prevYear = $year - 1;
            return [
                'name' => 'dahan',
                'name_zh' => '大寒',
                'timestamp' => new DateTime($prevYear . '-01-20 00:00:00', new \DateTimeZone('UTC')),
            ];
        }
    }
}
