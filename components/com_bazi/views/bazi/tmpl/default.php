<?php
/**
 * com_bazi – Default template (排盘结果页)
 *
 * Renders the input form and, if a chart is available, the full 八字排盘.
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;
?>
<div class="com-bazi">

<?php /* ─────────────────────────  Inline styles  ───────────────────────── */ ?>
<style>
.com-bazi{font-family:"Noto Serif SC","STSong","SimSun",serif;color:#2c1a0e;max-width:900px;margin:0 auto;padding:16px}
.com-bazi h1{text-align:center;font-size:1.6em;letter-spacing:.15em;color:#7b3f00;margin-bottom:16px}
.bazi-form{background:#fdf8f0;border:1px solid #d4a96a;border-radius:8px;padding:20px;margin-bottom:24px}
.bazi-form h2{font-size:1.1em;color:#7b3f00;margin:0 0 14px}
.bazi-form .field-row{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:12px}
.bazi-form label{font-size:.9em;color:#5a3010;display:block;margin-bottom:4px}
.bazi-form select,.bazi-form input[type=number]{border:1px solid #c9934a;border-radius:4px;padding:6px 8px;font-size:1em;background:#fffdf8;width:90px}
.bazi-form .gender-field{display:flex;gap:14px;align-items:center;margin-top:4px}
.bazi-form .btn-submit{background:#7b3f00;color:#fff;border:none;border-radius:4px;padding:8px 22px;font-size:1em;cursor:pointer;letter-spacing:.05em}
.bazi-form .btn-submit:hover{background:#9c5200}
.bazi-error{color:#c0392b;background:#fdecea;border:1px solid #f5c6cb;border-radius:4px;padding:10px 14px;margin-bottom:16px}

/* Chart grid */
.bazi-chart{background:#fdf8f0;border:1px solid #d4a96a;border-radius:8px;padding:20px;margin-bottom:24px}
.bazi-chart h2{font-size:1.1em;color:#7b3f00;margin:0 0 14px;text-align:center}
.pillars-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.pillar-card{border:1px solid #d4a96a;border-radius:6px;background:#fff9ee;padding:10px;text-align:center}
.pillar-card .pillar-title{font-size:.75em;color:#9c6020;margin-bottom:6px;letter-spacing:.1em}
.pillar-card .ganzhi{font-size:2em;letter-spacing:.05em;color:#3d1a00;line-height:1.2;margin-bottom:4px}
.pillar-card .nayin{font-size:.75em;color:#7a4f1e;margin-bottom:4px}
.pillar-card .detail-table{width:100%;font-size:.75em;border-collapse:collapse;text-align:left}
.pillar-card .detail-table td{padding:1px 3px;vertical-align:top}
.pillar-card .detail-table .label{color:#9c6020;white-space:nowrap}
.pillar-card .detail-table .val{color:#2c1a0e}
.pillar-card .hidden-stems{font-size:.72em;color:#5a3010;margin-top:4px;line-height:1.5}
.pillar-card .hs-main{font-weight:bold}

/* Summary row */
.summary-row{display:flex;flex-wrap:wrap;gap:8px;font-size:.88em;margin-bottom:14px;background:#fff5e0;border-radius:6px;padding:10px 14px}
.summary-row .s-item{display:flex;gap:4px}
.summary-row .s-label{color:#9c6020;font-size:.9em}
.summary-row .s-val{color:#2c1a0e;font-weight:bold}

/* Decade fortunes */
.section-title{font-size:1em;color:#7b3f00;margin:16px 0 8px;font-weight:bold;border-bottom:1px solid #d4a96a;padding-bottom:4px}
.dayun-table{width:100%;border-collapse:collapse;font-size:.84em}
.dayun-table th,.dayun-table td{border:1px solid #d4a96a;padding:5px 8px;text-align:center}
.dayun-table th{background:#f5e6cc;color:#7b3f00}
.dayun-table tr:nth-child(even){background:#fffdf5}
.dayun-table .gz{font-size:1.15em;font-weight:bold;letter-spacing:.05em}

/* Clashes */
.xing-section{background:#fff9ee;border:1px solid #d4a96a;border-radius:6px;padding:12px 16px;margin-top:12px}
.xing-section h3{font-size:.95em;color:#7b3f00;margin:0 0 8px}
.xing-tags{display:flex;flex-wrap:wrap;gap:6px}
.xing-tag{background:#fde8c8;border:1px solid #c9934a;border-radius:12px;padding:3px 10px;font-size:.82em;color:#5a2e00}
.xing-none{color:#aaa;font-size:.82em}
</style>

<h1>⚡ 八字排盘</h1>

<?php /* ─────────────────────────  Input form  ─────────────────────────── */ ?>
<div class="bazi-form">
    <h2>输入出生信息</h2>
    <form method="get" action="<?php echo JRoute::_('index.php?option=com_bazi'); ?>">
        <input type="hidden" name="option" value="com_bazi">
        <input type="hidden" name="task"   value="calculate">
        <div class="field-row">
            <div>
                <label for="bazi-year">出生年 (公历)</label>
                <input type="number" id="bazi-year" name="year" min="1900" max="2100"
                    value="<?php echo (int)($this->formData['year'] ?? date('Y')); ?>" required>
            </div>
            <div>
                <label for="bazi-month">月</label>
                <select id="bazi-month" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>"
                            <?php echo (isset($this->formData['month']) && (int)$this->formData['month'] === $m) ? 'selected' : ''; ?>>
                            <?php echo $m; ?>月
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="bazi-day">日</label>
                <select id="bazi-day" name="day">
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                        <option value="<?php echo $d; ?>"
                            <?php echo (isset($this->formData['day']) && (int)$this->formData['day'] === $d) ? 'selected' : ''; ?>>
                            <?php echo $d; ?>日
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="bazi-hour">时辰 (小时)</label>
                <select id="bazi-hour" name="hour">
                    <?php
                    $shichen = [
                        '子时(23-01)'=>23,'丑时(01-03)'=>1,'寅时(03-05)'=>3,'卯时(05-07)'=>5,
                        '辰时(07-09)'=>7,'巳时(09-11)'=>9,'午时(11-13)'=>11,'未时(13-15)'=>13,
                        '申时(15-17)'=>15,'酉时(17-19)'=>17,'戌时(19-21)'=>19,'亥时(21-23)'=>21,
                    ];
                    foreach ($shichen as $label => $val):
                    ?>
                        <option value="<?php echo $val; ?>"
                            <?php echo (isset($this->formData['hour']) && (int)$this->formData['hour'] === $val) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>性别</label>
                <div class="gender-field">
                    <label><input type="radio" name="gender" value="1"
                        <?php echo (!isset($this->formData['gender']) || (int)$this->formData['gender'] === 1) ? 'checked' : ''; ?>>
                        乾造(男)</label>
                    <label><input type="radio" name="gender" value="0"
                        <?php echo (isset($this->formData['gender']) && (int)$this->formData['gender'] === 0) ? 'checked' : ''; ?>>
                        坤造(女)</label>
                </div>
            </div>
            <div>
                <button type="submit" class="btn-submit">排　盘</button>
            </div>
        </div>
    </form>
</div>

<?php /* ─────────────────────────  Error  ─────────────────────────────── */ ?>
<?php if ($this->error): ?>
    <div class="bazi-error"><?php echo htmlspecialchars($this->error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php /* ─────────────────────────  Chart output  ─────────────────────── */ ?>
<?php if ($this->chart): $c = $this->chart; ?>

<div class="bazi-chart">
    <h2>四柱八字排盘</h2>

    <?php /* Summary row */ ?>
    <div class="summary-row">
        <div class="s-item"><span class="s-label">性别：</span><span class="s-val"><?php echo htmlspecialchars($c['性别']); ?></span></div>
        <div class="s-item"><span class="s-label">阳历：</span><span class="s-val"><?php echo htmlspecialchars($c['阳历']); ?></span></div>
        <div class="s-item"><span class="s-label">八字：</span><span class="s-val"><?php echo htmlspecialchars($c['八字']); ?></span></div>
        <div class="s-item"><span class="s-label">生肖：</span><span class="s-val"><?php echo htmlspecialchars($c['生肖']); ?></span></div>
        <div class="s-item"><span class="s-label">日主：</span><span class="s-val"><?php echo htmlspecialchars($c['日主']); ?>（<?php echo htmlspecialchars($c['日主五行']); ?>）</span></div>
        <div class="s-item"><span class="s-label">旬空：</span><span class="s-val"><?php echo htmlspecialchars($c['旬空']); ?></span></div>
    </div>

    <?php /* Four Pillars Grid */ ?>
    <div class="pillars-grid">
        <?php
        $pillars = [
            ['年柱', $c['年柱']],
            ['月柱', $c['月柱']],
            ['日柱', $c['日柱']],
            ['时柱', $c['时柱']],
        ];
        foreach ($pillars as [$title, $p]):
        ?>
        <div class="pillar-card">
            <div class="pillar-title"><?php echo $title; ?></div>
            <div class="ganzhi"><?php echo htmlspecialchars($p['干支']); ?></div>
            <div class="nayin">「<?php echo htmlspecialchars($p['纳音']); ?>」</div>
            <table class="detail-table">
                <tr>
                    <td class="label">天干</td>
                    <td class="val">
                        <?php echo htmlspecialchars($p['天干']['天干']); ?>
                        <?php echo htmlspecialchars($p['天干']['阴阳']); ?>
                        <?php echo htmlspecialchars($p['天干']['五行']); ?>
                        <?php if ($p['天干']['十神']): ?>
                            <br><em><?php echo htmlspecialchars($p['天干']['十神']); ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">地支</td>
                    <td class="val">
                        <?php echo htmlspecialchars($p['地支']['地支']); ?>
                        <?php echo htmlspecialchars($p['地支']['阴阳']); ?>
                        <?php echo htmlspecialchars($p['地支']['五行']); ?>
                    </td>
                </tr>
                <?php if ($p['星运']): ?>
                <tr>
                    <td class="label">星运</td>
                    <td class="val"><?php echo htmlspecialchars($p['星运']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($p['自坐']): ?>
                <tr>
                    <td class="label">自坐</td>
                    <td class="val"><?php echo htmlspecialchars($p['自坐']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <div class="hidden-stems">
                <?php foreach ($p['地支']['藏干'] as $idx => $hs): ?>
                    <span class="<?php echo $idx === 0 ? 'hs-main' : ''; ?>">
                        <?php echo htmlspecialchars($hs['天干']); ?>
                        (<?php echo htmlspecialchars($hs['五行']); ?>
                        <?php echo $hs['十神'] ? '·' . htmlspecialchars($hs['十神']) : ''; ?>)
                    </span>
                    <?php echo $idx < count($p['地支']['藏干']) - 1 ? ' &nbsp;' : ''; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php /* Clashes & Harmonies */ ?>
    <?php $xch = $c['刑冲合害']; ?>
    <div class="xing-section">
        <h3>刑冲合害</h3>
        <?php
        $groups = ['六冲' => $xch['六冲'], '六合' => $xch['六合'], '三合' => $xch['三合'], '六害' => $xch['六害']];
        foreach ($groups as $gname => $items):
        ?>
        <strong><?php echo $gname; ?>：</strong>
        <span class="xing-tags">
            <?php if ($items): ?>
                <?php foreach ($items as $it): ?>
                    <span class="xing-tag"><?php echo htmlspecialchars($it); ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="xing-none">无</span>
            <?php endif; ?>
        </span>　
        <?php endforeach; ?>
    </div>
</div>

<?php /* ─────────────────────────  Decade Fortunes  ──────────────────── */ ?>
<div class="bazi-chart">
    <?php $dy = $c['大运']; ?>
    <h2>大运（<?php echo htmlspecialchars($dy['起运年龄']); ?>岁起运，<?php echo htmlspecialchars($dy['顺逆']); ?>）</h2>
    <div class="section-title">大运表</div>
    <table class="dayun-table">
        <thead>
            <tr>
                <th>干支</th><th>起运年龄</th><th>结束年龄</th>
                <th>开始年份</th><th>结束年份</th><th>纳音</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dy['大运'] as $yun): ?>
            <tr>
                <td class="gz"><?php echo htmlspecialchars($yun['干支']); ?></td>
                <td><?php echo $yun['开始年龄']; ?></td>
                <td><?php echo $yun['结束年龄']; ?></td>
                <td><?php echo $yun['开始年份']; ?></td>
                <td><?php echo $yun['结束年份']; ?></td>
                <td><?php echo htmlspecialchars($yun['纳音']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php /* ─────────────────────────  Annual Fortunes  ───────────────────── */ ?>
<div class="bazi-chart">
    <h2>流年（从起运年份起80年）</h2>
    <div style="display:flex;flex-wrap:wrap;gap:6px;font-size:.82em;">
        <?php foreach (array_slice($c['流年'], 0, 80) as $ln): ?>
            <span style="background:#fff5e0;border:1px solid #d4a96a;border-radius:4px;padding:2px 7px;">
                <?php echo $ln['年份']; ?> <?php echo htmlspecialchars($ln['干支']); ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; // chart ?>

<p style="text-align:center;font-size:.75em;color:#aaa;margin-top:16px">
    八字排盘 · com_bazi for Joomla
</p>
</div>
