<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var $this \Yutang\Component\Bazi\Administrator\View\Readings\HtmlView */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_bazi&view=readings'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="readingsList">
                        <thead>
                            <tr>
                                <th scope="col" style="width:1%" class="text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BAZI_READING_FIELD_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BAZI_READING_FIELD_CHART', 'a.chart_id', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BAZI_READING_FIELD_STATUS', 'a.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BAZI_READING_FIELD_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_BAZI_READING_FIELD_CREATED', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo $item->id; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->chart_name ?: 'Chart #' . $item->chart_id); ?>
                                    </td>
                                    <td>
                                        <?php echo Text::_('COM_BAZI_READING_STATUS_' . strtoupper($item->status)); ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->user_name ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
