<?php
/**
 * com_bazi – Administrator entry point (minimal stub)
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;

$controller = JControllerLegacy::getInstance('Bazi');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
