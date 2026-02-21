<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var $this \Yutang\Component\Bazi\Site\View\Calculate\HtmlView */

$honeypotField = $this->antiSpam->getHoneypotFieldName();
$isHoneypotEnabled = $this->antiSpam->isHoneypotEnabled();
$isCaptchaEnabled = $this->antiSpam->isCaptchaEnabled();
?>

<div class="com-bazi-calculate">
    <h1><?php echo Text::_('COM_BAZI_PAGE_TITLE'); ?></h1>

    <?php if ($this->result): ?>
        <!-- Display Results -->
        <div class="bazi-results">
            <h2><?php echo Text::_('COM_BAZI_RESULTS_TITLE'); ?></h2>

            <?php if ($this->chartId): ?>
                <div class="alert alert-success">
                    <?php echo Text::sprintf('COM_BAZI_MSG_CHART_SAVED', $this->chartId); ?>
                </div>
            <?php endif; ?>

            <!-- Input Summary -->
            <?php if ($this->inputData): ?>
                <div class="bazi-input-summary card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_INPUT_TIME'); ?></h5>
                        <?php if (!empty($this->inputData['name'])): ?>
                            <p><strong><?php echo Text::_('COM_BAZI_FIELD_NAME'); ?>:</strong> <?php echo $this->escape($this->inputData['name']); ?></p>
                        <?php endif; ?>
                        <p><strong><?php echo Text::_('COM_BAZI_FIELD_BIRTH_DATE'); ?>:</strong> <?php echo $this->escape($this->inputData['birth_date']); ?></p>
                        <p><strong><?php echo Text::_('COM_BAZI_FIELD_BIRTH_TIME'); ?>:</strong> <?php echo $this->escape($this->inputData['birth_time']); ?></p>
                        <p><strong><?php echo Text::_('COM_BAZI_FIELD_GENDER'); ?>:</strong> <?php echo Text::_('COM_BAZI_FIELD_GENDER_' . strtoupper($this->inputData['gender'])); ?></p>
                        <?php if (!empty($this->inputData['location_name'])): ?>
                            <p><strong><?php echo Text::_('COM_BAZI_FIELD_LOCATION'); ?>:</strong> <?php echo $this->escape($this->inputData['location_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- True Solar Time Correction Info -->
            <?php if (!empty($this->result['options']['correction_summary'])): ?>
                <div class="bazi-correction card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_NORMALIZED_TIME'); ?></h5>
                        <?php $correction = $this->result['options']['correction_summary']; ?>
                        <p><strong><?php echo Text::_('COM_BAZI_RESULTS_INPUT_TIME'); ?>:</strong> <?php echo $correction['original_time']; ?></p>
                        <p><strong><?php echo Text::_('COM_BAZI_RESULTS_NORMALIZED_TIME'); ?>:</strong> <?php echo $correction['corrected_time']; ?></p>
                        <p><strong>Total Correction:</strong> <?php echo $correction['total_correction_minutes']; ?> minutes</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Four Pillars (四柱八字) -->
            <div class="bazi-four-pillars card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_BASIC_CHART'); ?></h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_BAZI_RESULTS_YEAR_PILLAR'); ?></th>
                                    <th><?php echo Text::_('COM_BAZI_RESULTS_MONTH_PILLAR'); ?></th>
                                    <th><?php echo Text::_('COM_BAZI_RESULTS_DAY_PILLAR'); ?></th>
                                    <th><?php echo Text::_('COM_BAZI_RESULTS_HOUR_PILLAR'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="pillar">
                                            <div class="stem"><?php echo $this->result['four_pillars']['year']['stem']['chinese']; ?></div>
                                            <div class="branch"><?php echo $this->result['four_pillars']['year']['branch']['chinese']; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="pillar">
                                            <div class="stem"><?php echo $this->result['four_pillars']['month']['stem']['chinese']; ?></div>
                                            <div class="branch"><?php echo $this->result['four_pillars']['month']['branch']['chinese']; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="pillar">
                                            <div class="stem"><?php echo $this->result['four_pillars']['day']['stem']['chinese']; ?></div>
                                            <div class="branch"><?php echo $this->result['four_pillars']['day']['branch']['chinese']; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="pillar">
                                            <div class="stem"><?php echo $this->result['four_pillars']['hour']['stem']['chinese']; ?></div>
                                            <div class="branch"><?php echo $this->result['four_pillars']['hour']['branch']['chinese']; ?></div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Dayun (大运) -->
            <div class="bazi-dayun card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_DAYUN'); ?></h3>
                    <p>
                        <strong><?php echo Text::_('COM_BAZI_RESULTS_DAYUN_DIRECTION'); ?>:</strong>
                        <?php echo $this->result['dayun']['direction'] === 'forward' ? Text::_('COM_BAZI_RESULTS_DAYUN_FORWARD') : Text::_('COM_BAZI_RESULTS_DAYUN_BACKWARD'); ?>
                    </p>
                    <p>
                        <strong><?php echo Text::_('COM_BAZI_RESULTS_DAYUN_START'); ?>:</strong>
                        <?php echo $this->result['dayun']['start_age']['years']; ?> <?php echo Text::_('COM_BAZI_RESULTS_AGE_YEARS'); ?>
                        <?php echo $this->result['dayun']['start_age']['months']; ?> <?php echo Text::_('COM_BAZI_RESULTS_AGE_MONTHS'); ?>
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Age Range</th>
                                    <th>Ganzhi (干支)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->result['dayun']['cycles'] as $cycle): ?>
                                    <tr>
                                        <td><?php echo $cycle['age_start']; ?> - <?php echo $cycle['age_end']; ?></td>
                                        <td>
                                            <span class="ganzhi">
                                                <?php echo $cycle['stem']['chinese'] . $cycle['branch']['chinese']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Liunian (流年) -->
            <div class="bazi-liunian card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_LIUNIAN'); ?></h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_BAZI_RESULTS_YEAR'); ?></th>
                                    <th>Ganzhi (干支)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->result['liunian'] as $year): ?>
                                    <tr>
                                        <td><?php echo $year['year']; ?></td>
                                        <td>
                                            <span class="ganzhi">
                                                <?php echo $year['stem']['chinese'] . $year['branch']['chinese']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shensha (神煞) -->
            <div class="bazi-shensha card mb-3">
                <div class="card-body">
                    <h3 class="card-title"><?php echo Text::_('COM_BAZI_RESULTS_SHENSHA'); ?></h3>
                    <dl class="row">
                        <?php if (!empty($this->result['shensha']['tianyi_nobleman'])): ?>
                            <dt class="col-sm-4">天乙贵人 (Tianyi Nobleman)</dt>
                            <dd class="col-sm-8"><?php echo implode(', ', $this->result['shensha']['tianyi_nobleman']); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->result['shensha']['taiji_nobleman'])): ?>
                            <dt class="col-sm-4">太极贵人 (Taiji Nobleman)</dt>
                            <dd class="col-sm-8"><?php echo implode(', ', $this->result['shensha']['taiji_nobleman']); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->result['shensha']['wenchang_nobleman'])): ?>
                            <dt class="col-sm-4">文昌贵人 (Wenchang Nobleman)</dt>
                            <dd class="col-sm-8"><?php echo $this->result['shensha']['wenchang_nobleman']; ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->result['shensha']['peach_blossom'])): ?>
                            <dt class="col-sm-4">桃花 (Peach Blossom)</dt>
                            <dd class="col-sm-8"><?php echo $this->result['shensha']['peach_blossom']; ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($this->result['shensha']['post_horse'])): ?>
                            <dt class="col-sm-4">驿马 (Post Horse)</dt>
                            <dd class="col-sm-8"><?php echo $this->result['shensha']['post_horse']; ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <hr class="my-4">
        </div>
    <?php endif; ?>

    <!-- Calculation Form -->
    <div class="bazi-form card">
        <div class="card-body">
            <h2 class="card-title"><?php echo Text::_('COM_BAZI_PAGE_TITLE'); ?></h2>

            <form action="<?php echo Route::_('index.php?option=com_bazi&task=calculate.submit'); ?>" method="post" class="form-validate">

                <div class="mb-3">
                    <label for="name" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_NAME'); ?></label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?php echo $this->escape($this->inputData['name'] ?? ''); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="birth_date" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_BIRTH_DATE'); ?> *</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" required
                               value="<?php echo $this->escape($this->inputData['birth_date'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="birth_time" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_BIRTH_TIME'); ?> *</label>
                        <input type="time" class="form-control" id="birth_time" name="birth_time" required
                               value="<?php echo $this->escape($this->inputData['birth_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php echo Text::_('COM_BAZI_FIELD_GENDER'); ?> *</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" required
                               <?php echo (($this->inputData['gender'] ?? '') === 'male') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gender_male">
                            <?php echo Text::_('COM_BAZI_FIELD_GENDER_MALE'); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female" required
                               <?php echo (($this->inputData['gender'] ?? '') === 'female') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gender_female">
                            <?php echo Text::_('COM_BAZI_FIELD_GENDER_FEMALE'); ?>
                        </label>
                    </div>
                </div>

                <?php if ($this->params->get('true_solar_time', 1)): ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_BAZI_HELP_TRUE_SOLAR_TIME'); ?>
                    </div>

                    <div class="mb-3">
                        <label for="location_name" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_LOCATION'); ?></label>
                        <input type="text" class="form-control" id="location_name" name="location_name"
                               value="<?php echo $this->escape($this->inputData['location_name'] ?? ''); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="longitude" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_LONGITUDE'); ?></label>
                            <input type="number" step="0.000001" class="form-control" id="longitude" name="longitude"
                                   value="<?php echo $this->escape((string) ($this->inputData['longitude'] ?? '')); ?>"
                                   placeholder="e.g., 116.4074 for Beijing">
                            <small class="form-text text-muted"><?php echo Text::_('COM_BAZI_HELP_LONGITUDE'); ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="latitude" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_LATITUDE'); ?></label>
                            <input type="number" step="0.000001" class="form-control" id="latitude" name="latitude"
                                   value="<?php echo $this->escape((string) ($this->inputData['latitude'] ?? '')); ?>"
                                   placeholder="e.g., 39.9042 for Beijing">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="timezone_offset" class="form-label"><?php echo Text::_('COM_BAZI_FIELD_TIMEZONE'); ?></label>
                        <input type="number" step="0.5" class="form-control" id="timezone_offset" name="timezone_offset"
                               value="<?php echo $this->escape((string) ($this->inputData['timezone_offset'] ?? '')); ?>"
                               placeholder="e.g., 8 for UTC+8">
                        <small class="form-text text-muted"><?php echo Text::_('COM_BAZI_HELP_TIMEZONE'); ?></small>
                    </div>
                <?php endif; ?>

                <?php if ($this->user->id): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="save_chart" name="save_chart" value="1"
                               <?php echo ($this->inputData['save_chart'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="save_chart">
                            <?php echo Text::_('COM_BAZI_FIELD_SAVE'); ?>
                        </label>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <?php echo Text::_('COM_BAZI_MSG_GUEST_NO_SAVE'); ?>
                    </div>
                <?php endif; ?>

                <!-- Honeypot field (hidden) -->
                <?php if ($isHoneypotEnabled): ?>
                    <div style="position: absolute; left: -9999px;">
                        <label for="<?php echo $honeypotField; ?>">Leave this field empty</label>
                        <input type="text" id="<?php echo $honeypotField; ?>" name="<?php echo $honeypotField; ?>" value="" tabindex="-1" autocomplete="off">
                    </div>
                <?php endif; ?>

                <?php if ($isCaptchaEnabled && !$this->user->id): ?>
                    <div class="mb-3">
                        <?php echo HTMLHelper::_('captcha.display', 'captcha'); ?>
                    </div>
                <?php endif; ?>

                <?php echo HTMLHelper::_('form.token'); ?>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-secondary">
                        <?php echo Text::_('COM_BAZI_BUTTON_RESET'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('COM_BAZI_BUTTON_CALCULATE'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bazi-results .pillar {
    text-align: center;
    font-size: 1.5rem;
    line-height: 1.2;
}
.bazi-results .pillar .stem {
    color: #c00;
    font-weight: bold;
}
.bazi-results .pillar .branch {
    color: #00c;
    font-weight: bold;
}
.bazi-results .ganzhi {
    font-size: 1.2rem;
    font-weight: bold;
}
</style>
