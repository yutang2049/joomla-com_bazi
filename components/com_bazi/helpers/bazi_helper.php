<?php
/**
 * Bazi (八字) Calculation Helper
 *
 * Implements the traditional Chinese Four Pillars of Destiny (四柱推命) algorithm.
 * Algorithm based on open-source references:
 *   - cantian-ai/bazi-mcp (TypeScript)
 *   - CrystalMarch/bazi (Python)
 *   - Elisabethhui/paipan-1 (JavaScript)
 *
 * @package     com_bazi
 * @subpackage  helpers
 */
defined('_JEXEC') or die;

class BaziHelper
{
    // ── Fundamental lookup tables ───────────────────────────────────────────

    /** 天干 Heavenly Stems (index 0-9) */
    const STEMS = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];

    /** 地支 Earthly Branches (index 0-11, starting with 子) */
    const BRANCHES = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];

    /**
     * 地支 starting with 丑 (used for month/hour calculations in paipan-1 style).
     * Index mapping: 0=丑,1=寅,...,10=亥,11=子
     */
    const BRANCHES0 = ['丑','寅','卯','辰','巳','午','未','申','酉','戌','亥','子'];

    /** 五行 Five Elements (paired with stems: 0-1=Wood, 2-3=Fire, 4-5=Earth, 6-7=Metal, 8-9=Water) */
    const STEM_ELEMENT = ['木','木','火','火','土','土','金','金','水','水'];

    /** 五行 for branches */
    const BRANCH_ELEMENT = ['水','土','木','木','土','火','火','土','金','金','土','水'];

    /** 阴阳 Yin/Yang for stems (0=Yang,1=Yin alternating) */
    const STEM_YY = ['阳','阴','阳','阴','阳','阴','阳','阴','阳','阴'];

    /** 阴阳 for branches */
    const BRANCH_YY = ['阳','阴','阳','阴','阳','阴','阳','阴','阳','阴','阳','阴'];

    /** 生肖 Chinese Zodiac (aligned with Branches index 0=子=Rat) */
    const ZODIAC = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];

    /**
     * 纳音 Nayin (Sound names for each of the 60 Jiazi cycles).
     * The array is indexed 0-59, matching the 60-cycle sequence starting from 甲子.
     */
    const NAYIN = [
        '海中金','海中金','炉中火','炉中火','大林木','大林木',
        '路旁土','路旁土','剑锋金','剑锋金','山头火','山头火',
        '涧下水','涧下水','城头土','城头土','白蜡金','白蜡金',
        '杨柳木','杨柳木','泉中水','泉中水','屋上土','屋上土',
        '霹雳火','霹雳火','松柏木','松柏木','长流水','长流水',
        '沙中金','沙中金','山下火','山下火','平地木','平地木',
        '壁上土','壁上土','金箔金','金箔金','覆灯火','覆灯火',
        '天河水','天河水','大驿土','大驿土','钗钏金','钗钏金',
        '桑柘木','桑柘木','大溪水','大溪水','沙中土','沙中土',
        '天上火','天上火','石榴木','石榴木','大海水','大海水',
    ];

    /**
     * 藏干 Hidden Stems for each Earthly Branch.
     * Key = branch character; value = array of [stem, stem, ...] (main first).
     */
    const HIDDEN_STEMS = [
        '子' => ['癸'],
        '丑' => ['己','癸','辛'],
        '寅' => ['甲','丙','戊'],
        '卯' => ['乙'],
        '辰' => ['戊','乙','癸'],
        '巳' => ['丙','庚','戊'],
        '午' => ['丁','己'],
        '未' => ['己','丁','乙'],
        '申' => ['庚','壬','戊'],
        '酉' => ['辛'],
        '戌' => ['戊','辛','丁'],
        '亥' => ['壬','甲'],
    ];

    /**
     * 十二长生 Twelve Life-Stages (星运) for each Heavenly Stem.
     * Indexed parallel to BRANCHES (子丑寅卯辰巳午未申酉戌亥).
     */
    const TERRAIN = [
        '甲' => ['沐浴','冠带','临官','帝旺','衰','病','死','墓','绝','胎','养','长生'],
        '乙' => ['病','衰','帝旺','临官','冠带','沐浴','长生','养','胎','绝','墓','死'],
        '丙' => ['胎','养','长生','沐浴','冠带','临官','帝旺','衰','病','死','墓','绝'],
        '丁' => ['绝','墓','死','病','衰','帝旺','临官','冠带','沐浴','长生','养','胎'],
        '戊' => ['胎','养','长生','沐浴','冠带','临官','帝旺','衰','病','死','墓','绝'],
        '己' => ['绝','墓','死','病','衰','帝旺','临官','冠带','沐浴','长生','养','胎'],
        '庚' => ['死','墓','绝','胎','养','长生','沐浴','冠带','临官','帝旺','衰','病'],
        '辛' => ['长生','养','胎','绝','墓','死','病','衰','帝旺','临官','冠带','沐浴'],
        '壬' => ['帝旺','衰','病','死','墓','绝','胎','养','长生','沐浴','冠带','临官'],
        '癸' => ['临官','冠带','沐浴','长生','养','胎','绝','墓','死','病','衰','帝旺'],
    ];

    /**
     * 十神 Ten Gods (十神) name mapping relative to day-master parity and position offset.
     * Used in shiShen().
     */
    const SHISHEN_YANG = [
        0  => '比肩', 1 => '劫财', 2 => '食神', 3 => '伤官',
        4  => '偏财', 5 => '正财', 6 => '七杀', 7 => '正官',
        8  => '偏印', 9 => '正印',
    ];

    const SHISHEN_YIN = [
        0  => '比肩', 1 => '劫财', 2 => '伤官', 3 => '食神',
        4  => '偏财', 5 => '正财', 6 => '七杀', 7 => '正官',
        8  => '偏印', 9 => '正印',
    ];

    // ── Reference timestamps for solar-term calculation ─────────────────────
    // These values mirror the paipan-1 JavaScript constants (milliseconds / 1000
    // to get PHP-compatible Unix seconds).

    /** Reference base-day timestamp (seconds); corresponds to ~ Dec 31 1983 16:00 UTC */
    const Y_D84 = 441734400.726;

    /** Tropical year length in seconds (≈ 365.2422 days) */
    const YEAR_SEC = 31556926.009;

    /**
     * Solar-term timestamps for 1984 (in seconds from Unix epoch).
     * Order: 小寒, 立春, 惊蛰, 清明, 立夏, 芒种, 小暑, 立秋, 白露, 寒露, 立冬, 大雪
     */
    const JQ84 = [
        442208451.146,  // 0  小寒
        444755924.716,  // 1  立春
        447326679.845,  // 2  惊蛰
        449936540.593,  // 3  清明
        452591457.618,  // 4  立夏
        455285317.308,  // 5  芒种
        458000946.032,  // 6  小暑
        460714673.166,  // 7  立秋
        463403390.187,  // 8  白露
        466051355.952,  // 9  寒露
        468654332.864,  // 10 立冬
        471220083.199,  // 11 大雪
    ];

    /**
     * 三合 Three Harmony groups.
     * Each array groups the three branches that form a harmony.
     */
    const SANHUI = [
        ['子','辰','申'],  // water
        ['寅','午','戌'],  // fire
        ['巳','酉','丑'],  // metal
        ['亥','卯','未'],  // wood
    ];

    /**
     * 六合 Six Harmonies (branch pairs).
     */
    const LIUHE = [
        ['子','丑'], ['寅','亥'], ['卯','戌'],
        ['辰','酉'], ['巳','申'], ['午','未'],
    ];

    /**
     * 六冲 Six Clashes (branch pairs).
     */
    const LIUCHONG = [
        ['子','午'], ['丑','未'], ['寅','申'],
        ['卯','酉'], ['辰','戌'], ['巳','亥'],
    ];

    /**
     * 六害 Six Harms (branch pairs).
     */
    const LIUHAI = [
        ['子','未'], ['丑','午'], ['寅','巳'],
        ['卯','辰'], ['申','亥'], ['酉','戌'],
    ];

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Calculate the complete Bazi chart for a given birth date/time and gender.
     *
     * @param   int  $year    Gregorian year  (e.g. 1985)
     * @param   int  $month   Gregorian month (1-12)
     * @param   int  $day     Gregorian day   (1-31)
     * @param   int  $hour    Hour of birth   (0-23)
     * @param   int  $gender  1 = male (乾造), 0 = female (坤造)
     *
     * @return  array  Associative array with the full chart.
     */
    public static function calculate($year, $month, $day, $hour, $gender = 1)
    {
        $year   = (int)$year;
        $month  = (int)$month;
        $day    = (int)$day;
        $hour   = (int)$hour;
        $gender = (int)$gender;

        // Unix timestamp of the birth moment (treated as CST = UTC+8, adjusted to UTC)
        $ts = gmmktime($hour, 0, 0, $month, $day, $year) - 8 * 3600;

        // ── Four Pillars ────────────────────────────────────────────────────
        $yearPillar  = self::yearPillar($year, $month, $day, $ts);
        $monthPillar = self::monthPillar($year, $month, $ts);
        $dayPillar   = self::dayPillar($ts);
        $hourPillar  = self::hourPillar($dayPillar['stem'], $hour);

        // Day master (日主) = day-pillar heavenly stem
        $dayMaster = $dayPillar['stem'];
        $dayMasterIdx = array_search($dayMaster, self::STEMS);

        // ── Build output objects ────────────────────────────────────────────
        $yearObj  = self::buildPillarObject($yearPillar,  $dayMaster, $dayMasterIdx);
        $monthObj = self::buildPillarObject($monthPillar, $dayMaster, $dayMasterIdx);
        $dayObj   = self::buildPillarObject($dayPillar,   null, null); // day: no ten-god on stem
        $hourObj  = self::buildPillarObject($hourPillar,  $dayMaster, $dayMasterIdx);

        // ── Decade fortunes (大运) ───────────────────────────────────────────
        $daYun = self::decadeFortunes(
            $year, $month, $ts,
            $yearPillar['stem'],
            $monthPillar,
            $gender
        );

        // ── Annual Fortunes (流年) ───────────────────────────────────────────
        $liuNian = self::annualFortunes($year, $daYun['起运年龄']);

        // ── Clashes & Harmonies (刑冲合害) ──────────────────────────────────
        $xingChongHeHai = self::xingChongHeHai([
            $yearPillar['branch'], $monthPillar['branch'],
            $dayPillar['branch'],  $hourPillar['branch'],
        ]);

        // ── Xun Kong (旬空/空亡) ─────────────────────────────────────────────
        $xunKong = self::xunKong($dayPillar['stem'], $dayPillar['branch']);

        return [
            '性别'    => $gender === 1 ? '乾造(男)' : '坤造(女)',
            '阳历'    => sprintf('%04d-%02d-%02d %02d时', $year, $month, $day, $hour),
            '八字'    => $yearPillar['ganzhi'] . ' ' . $monthPillar['ganzhi'] . ' '
                        . $dayPillar['ganzhi'] . ' ' . $hourPillar['ganzhi'],
            '生肖'    => self::ZODIAC[array_search($yearPillar['branch'], self::BRANCHES)],
            '日主'    => $dayMaster,
            '日主五行' => self::STEM_ELEMENT[$dayMasterIdx],
            '年柱'    => $yearObj,
            '月柱'    => $monthObj,
            '日柱'    => $dayObj,
            '时柱'    => $hourObj,
            '旬空'    => $xunKong,
            '大运'    => $daYun,
            '流年'    => $liuNian,
            '刑冲合害' => $xingChongHeHai,
        ];
    }

    // ── Pillar Calculations ─────────────────────────────────────────────────

    /**
     * Calculate the Year Pillar (年柱).
     * The pillar changes at 立春 (Start of Spring, index 1 in JQ84).
     */
    private static function yearPillar($year, $month, $day, $ts)
    {
        // Solar-term timestamp for 立春 of this year
        $lichun = self::solarTermTimestamp($year, 1);

        // If birth is before 立春, use previous year for the pillar
        $adjYear = ($ts < $lichun) ? $year - 1 : $year;

        $stemIdx   = ($adjYear + 6) % 10;
        $branchIdx = (($adjYear - 1984 % 12) + 1200) % 12;
        // Simpler: (year + 8) % 12 maps year 1984 → index 8 (子=Rat year 1984)
        $branchIdx = (($adjYear - 4) % 12 + 12) % 12;

        return self::buildPillar($stemIdx, $branchIdx);
    }

    /**
     * Calculate the Month Pillar (月柱).
     * Changes at each of the 12 principal solar terms.
     */
    private static function monthPillar($year, $month, $ts)
    {
        // Solar term index in JQ84 corresponding to the current solar month (0-based, 0=小寒)
        // Months (solar): Jan→0(小寒), Feb→1(立春), Mar→2(惊蛰), Apr→3(清明),
        //                 May→4(立夏), Jun→5(芒种), Jul→6(小暑), Aug→7(立秋),
        //                 Sep→8(白露), Oct→9(寒露), Nov→10(立冬), Dec→11(大雪)
        $jqIdx = $month - 1; // 0-based

        $jqTs = self::solarTermTimestamp($year, $jqIdx);

        // If before this month's solar term, shift back one month
        if ($ts < $jqTs) {
            $jqIdx = ($jqIdx - 1 + 12) % 12;
            if ($jqIdx === 11) {
                // Rolled back to December → previous year's 大雪 term
                $jqTs = self::solarTermTimestamp($year - 1, 11);
            } else {
                $jqTs = self::solarTermTimestamp($year, $jqIdx);
            }
        }

        // Month Branch: 寅(index 2) corresponds to solar month starting at 立春 (jqIdx=1)
        // jqIdx 0 (小寒) → 丑(index 1), jqIdx 1 (立春) → 寅(index 2), etc.
        $branchIdx = ($jqIdx + 1) % 12; // 0=丑, 1=寅, 2=卯, ...

        // Month Stem depends on the year stem
        // paipan-1 formula: stem_base = (yearStemIdx % 5) * 2 + 1 for 丑月(小寒)
        // e.g. 甲/己 year → base=1(乙), 乙/庚 year → base=3(丁), etc.
        $yearStemForMonth = self::currentYearStemIdx($year, $ts);
        $monthStemBase    = ($yearStemForMonth % 5) * 2 + 1;
        $stemIdx = ($monthStemBase + $jqIdx) % 10;

        return self::buildPillar($stemIdx, $branchIdx);
    }

    /**
     * Calculate the Day Pillar (日柱).
     * Uses the paipan-1 reference: y_d84 ≈ Jan 1 1984 00:00 CST = 甲午日.
     * Formula: y_r = floor((ts - y_d84) / 86400), then
     *   stem   = STEMS[y_r % 10]
     *   branch = BRANCHES0[(5 + y_r%12) % 12]  (BRANCHES0 = 丑寅卯...子)
     */
    private static function dayPillar($ts)
    {
        $y_d84_int = (int)self::Y_D84; // ≈ 441734400
        $y_r       = (int)(($ts - $y_d84_int) / 86400);

        $stemIdx  = (($y_r % 10) + 10) % 10;
        $dz0Idx   = ((5 + (($y_r % 12) + 12) % 12) % 12 + 12) % 12;
        // BRANCHES0 = ['丑','寅','卯','辰','巳','午','未','申','酉','戌','亥','子']
        // BRANCHES0[i] = BRANCHES[(i+1)%12]
        $branchIdx = ($dz0Idx + 1) % 12;

        return self::buildPillar($stemIdx, $branchIdx);
    }

    /**
     * Calculate the Hour Pillar (时柱).
     */
    private static function hourPillar($dayStem, $hour)
    {
        // Determine hour branch (时支)
        // 子时 23:00-01:00 (hour 23 or 0), 丑时 01:00-03:00, etc.
        if ($hour >= 23 || $hour < 1)  { $hBranchIdx = 0; }  // 子
        elseif ($hour < 3)             { $hBranchIdx = 1; }  // 丑
        elseif ($hour < 5)             { $hBranchIdx = 2; }  // 寅
        elseif ($hour < 7)             { $hBranchIdx = 3; }  // 卯
        elseif ($hour < 9)             { $hBranchIdx = 4; }  // 辰
        elseif ($hour < 11)            { $hBranchIdx = 5; }  // 巳
        elseif ($hour < 13)            { $hBranchIdx = 6; }  // 午
        elseif ($hour < 15)            { $hBranchIdx = 7; }  // 未
        elseif ($hour < 17)            { $hBranchIdx = 8; }  // 申
        elseif ($hour < 19)            { $hBranchIdx = 9; }  // 酉
        elseif ($hour < 21)            { $hBranchIdx = 10; } // 戌
        else                           { $hBranchIdx = 11; } // 亥

        // Hour stem depends on day stem
        $dayStemIdx   = array_search($dayStem, self::STEMS);
        $dayGroup     = $dayStemIdx % 5; // 0=甲己, 1=乙庚, 2=丙辛, 3=丁壬, 4=戊癸
        $stemBase     = [0, 2, 4, 6, 8][$dayGroup]; // 甲子时开始的天干索引
        $hStemIdx     = ($stemBase + $hBranchIdx) % 10;

        return self::buildPillar($hStemIdx, $hBranchIdx);
    }

    // ── Decade & Annual Fortunes ────────────────────────────────────────────

    /**
     * Calculate the Decade Fortunes (大运) and their start age.
     */
    private static function decadeFortunes($year, $month, $ts, $yearStem, $monthPillar, $gender)
    {
        $yearStemIdx = array_search($yearStem, self::STEMS);
        $isYangYear  = ($yearStemIdx % 2 === 0); // Yang year stem (甲丙戊庚壬)

        // Fortune direction: Yang male / Yin female → forward; else → backward
        $forward = ($isYangYear && $gender === 1) || (!$isYangYear && $gender === 0);

        // Find the nearest solar term (立春-based) for start-age calculation
        $jqIdx  = $month - 1; // current solar month index
        $jqTs   = self::solarTermTimestamp($year, $jqIdx);

        if ($forward) {
            // Distance to the NEXT solar term
            $nextJqIdx = ($jqIdx + 1) % 12;
            $nextYear  = ($nextJqIdx === 0) ? $year + 1 : $year;
            $nextJqTs  = self::solarTermTimestamp($nextYear, $nextJqIdx);
            $distDays  = ($nextJqTs - $ts) / 86400.0;
        } else {
            // Distance to the PREVIOUS solar term
            if ($ts >= $jqTs) {
                $distDays = ($ts - $jqTs) / 86400.0;
            } else {
                $prevJqIdx = ($jqIdx - 1 + 12) % 12;
                $prevYear  = ($prevJqIdx === 11) ? $year - 1 : $year;
                $prevJqTs  = self::solarTermTimestamp($prevYear, $prevJqIdx);
                $distDays  = ($ts - $prevJqTs) / 86400.0;
            }
        }

        // 3 days ≈ 1 year in the fortune calculation
        $qiYunAge = (int)round($distDays / 3.0);

        // Build decade fortunes
        $mStemIdx   = array_search($monthPillar['stem'],   self::STEMS);
        $mBranchIdx = array_search($monthPillar['branch'], self::BRANCHES);

        $fortunes = [];
        for ($i = 1; $i <= 8; $i++) {
            if ($forward) {
                $si = ($mStemIdx   + $i) % 10;
                $bi = ($mBranchIdx + $i) % 12;
            } else {
                $si = (($mStemIdx   - $i) % 10 + 10) % 10;
                $bi = (($mBranchIdx - $i) % 12 + 12) % 12;
            }
            $startAge  = $qiYunAge + ($i - 1) * 10;
            $startYear = $year + $startAge;
            $fortunes[] = [
                '干支'   => self::STEMS[$si] . self::BRANCHES[$bi],
                '天干'   => self::STEMS[$si],
                '地支'   => self::BRANCHES[$bi],
                '纳音'   => self::nayin60Index($si, $bi),
                '开始年龄' => $startAge,
                '结束年龄' => $startAge + 9,
                '开始年份' => $startYear,
                '结束年份' => $startYear + 9,
            ];
        }

        return [
            '起运年龄' => $qiYunAge,
            '顺逆'   => $forward ? '顺行' : '逆行',
            '大运'   => $fortunes,
        ];
    }

    /**
     * Calculate Annual Fortunes (流年) for 80 years starting from the fortune start age.
     */
    private static function annualFortunes($birthYear, $qiYunAge)
    {
        $startYear = $birthYear + $qiYunAge;
        $result    = [];
        for ($i = 0; $i < 80; $i++) {
            $y         = $startYear + $i;
            $stemIdx   = ($y + 6) % 10;
            $branchIdx = (($y - 4) % 12 + 12) % 12;
            $result[]  = [
                '年份' => $y,
                '干支' => self::STEMS[$stemIdx] . self::BRANCHES[$branchIdx],
            ];
        }
        return $result;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Get Unix timestamp (seconds) of a given solar term in a given year.
     *
     * @param  int  $year   Gregorian year
     * @param  int  $jqIdx  Solar term index 0-11 (0=小寒, 1=立春, …, 11=大雪)
     * @return float
     */
    private static function solarTermTimestamp($year, $jqIdx)
    {
        return self::JQ84[$jqIdx] + ($year - 1984) * self::YEAR_SEC;
    }

    /**
     * Get the current year stem index (accounting for 立春 boundary).
     */
    private static function currentYearStemIdx($year, $ts)
    {
        $lichun = self::solarTermTimestamp($year, 1);
        $adjYear = ($ts < $lichun) ? $year - 1 : $year;
        return ($adjYear + 6) % 10;
    }

    /**
     * Build a raw pillar array { stem, branch, ganzhi, idx60 }.
     */
    private static function buildPillar($stemIdx, $branchIdx)
    {
        $stemIdx   = (($stemIdx   % 10) + 10) % 10;
        $branchIdx = (($branchIdx % 12) + 12) % 12;
        // Position in the 60-cycle (甲子=0 … 癸亥=59)
        // Find the Jiazi index: stem%10 and branch%12 must be congruent mod 2
        // Naive search (fast since max 60 iterations)
        $idx60 = -1;
        for ($k = 0; $k < 60; $k++) {
            if (($k % 10) === $stemIdx && ($k % 12) === $branchIdx) {
                $idx60 = $k;
                break;
            }
        }
        return [
            'stem'     => self::STEMS[$stemIdx],
            'branch'   => self::BRANCHES[$branchIdx],
            'ganzhi'   => self::STEMS[$stemIdx] . self::BRANCHES[$branchIdx],
            'stemIdx'  => $stemIdx,
            'branchIdx'=> $branchIdx,
            'idx60'    => $idx60,
        ];
    }

    /**
     * Build the full pillar display object including five-elements, ten-gods, etc.
     *
     * @param  array       $pillar        From buildPillar()
     * @param  string|null $dayMaster     Day-master stem character
     * @param  int|null    $dayMasterIdx  Day-master stem index
     */
    private static function buildPillarObject($pillar, $dayMaster, $dayMasterIdx)
    {
        $si = $pillar['stemIdx'];
        $bi = $pillar['branchIdx'];

        $tenGodStem   = ($dayMaster !== null)
            ? self::tenGod($dayMasterIdx, $si)
            : null;

        $hiddenStems = self::HIDDEN_STEMS[$pillar['branch']];
        $hiddenObjs  = [];
        foreach ($hiddenStems as $hs) {
            $hsIdx = array_search($hs, self::STEMS);
            $hiddenObjs[] = [
                '天干' => $hs,
                '五行' => self::STEM_ELEMENT[$hsIdx],
                '十神' => ($dayMaster !== null) ? self::tenGod($dayMasterIdx, $hsIdx) : null,
            ];
        }

        // 自坐: terrain of this pillar's own stem in its branch
        $selfTerrain = self::TERRAIN[$pillar['stem']][$bi] ?? null;

        // 星运: terrain of day-master in this pillar's branch
        // For the day pillar itself (dayMaster === null), 星运 equals 自坐
        $terrain     = ($dayMaster !== null)
            ? self::TERRAIN[$dayMaster][$bi]
            : $selfTerrain;

        return [
            '干支' => $pillar['ganzhi'],
            '天干' => [
                '天干' => $pillar['stem'],
                '五行' => self::STEM_ELEMENT[$si],
                '阴阳' => self::STEM_YY[$si],
                '十神' => $tenGodStem,
            ],
            '地支' => [
                '地支' => $pillar['branch'],
                '五行' => self::BRANCH_ELEMENT[$bi],
                '阴阳' => self::BRANCH_YY[$bi],
                '藏干' => $hiddenObjs,
            ],
            '纳音' => self::NAYIN[$pillar['idx60']],
            '星运' => $terrain,
            '自坐' => $selfTerrain,
        ];
    }

    /**
     * Calculate the Ten God (十神) relationship.
     *
     * @param  int  $masterIdx  Day-master stem index (0-9)
     * @param  int  $targetIdx  Target stem index (0-9)
     * @return string
     */
    public static function tenGod($masterIdx, $targetIdx)
    {
        $diff  = (($targetIdx - $masterIdx) % 10 + 10) % 10;
        $isYang = ($masterIdx % 2 === 0);
        return $isYang
            ? self::SHISHEN_YANG[$diff]
            : self::SHISHEN_YIN[$diff];
    }

    /**
     * Get the Nayin name for a 60-cycle position identified by stem+branch indices.
     */
    private static function nayin60Index($si, $bi)
    {
        for ($k = 0; $k < 60; $k++) {
            if (($k % 10) === $si && ($k % 12) === $bi) {
                return self::NAYIN[$k];
            }
        }
        return '';
    }

    /**
     * Calculate Xun Kong (旬空/空亡) for the day pillar.
     * The two empty branches are those "missing" in the current 10-day cycle.
     *
     * @param  string  $dayStem    Day stem character
     * @param  string  $dayBranch  Day branch character
     * @return string  Two-character string of empty branches
     */
    public static function xunKong($dayStem, $dayBranch)
    {
        $si = array_search($dayStem,   self::STEMS);
        $bi = array_search($dayBranch, self::BRANCHES);
        // Start of the current 10-day cycle (旬首)
        $cycleBranchStart = ($bi - $si + 12) % 12;
        // The two empty branches are at positions 10 and 11 of the cycle
        $empty1 = self::BRANCHES[($cycleBranchStart + 10) % 12];
        $empty2 = self::BRANCHES[($cycleBranchStart + 11) % 12];
        return $empty1 . $empty2;
    }

    /**
     * Compute clashes, harmonies, and harms among the four day-branches.
     *
     * @param  array  $branches  Four branch characters [year, month, day, hour]
     * @return array
     */
    private static function xingChongHeHai($branches)
    {
        $labels = ['年','月','日','时'];
        $chong  = [];
        $liuhe  = [];
        $sanhe  = [];
        $hai    = [];

        for ($i = 0; $i < 4; $i++) {
            for ($j = $i + 1; $j < 4; $j++) {
                $pair = [$branches[$i], $branches[$j]];
                sort($pair);

                // 六冲
                foreach (self::LIUCHONG as $c) {
                    $cs = $c; sort($cs);
                    if ($cs === $pair) {
                        $chong[] = $labels[$i] . $branches[$i] . '冲' . $labels[$j] . $branches[$j];
                    }
                }
                // 六合
                foreach (self::LIUHE as $h) {
                    $hs = $h; sort($hs);
                    if ($hs === $pair) {
                        $liuhe[] = $labels[$i] . $branches[$i] . '合' . $labels[$j] . $branches[$j];
                    }
                }
                // 六害
                foreach (self::LIUHAI as $h) {
                    $hs = $h; sort($hs);
                    if ($hs === $pair) {
                        $hai[] = $labels[$i] . $branches[$i] . '害' . $labels[$j] . $branches[$j];
                    }
                }
            }
        }

        // 三合 (partial harmony — at least 2 distinct branches matching the group)
        foreach (self::SANHUI as $group) {
            $matchedBranches = [];
            foreach ($branches as $idx => $b) {
                if (in_array($b, $group) && !in_array($b, $matchedBranches)) {
                    $matchedBranches[] = $b;
                }
            }
            if (count($matchedBranches) >= 2) {
                $sanhe[] = implode('', $matchedBranches) . (count($matchedBranches) === 3 ? '三合' : '半合');
            }
        }

        return [
            '六冲' => $chong,
            '六合' => $liuhe,
            '三合' => $sanhe,
            '六害' => $hai,
        ];
    }
}
