<?php
/**
 * com_bazi – Frontend entry point
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;

// Include main helper early so models/views can use it
JLoader::register('BaziHelper', __DIR__ . '/helpers/bazi_helper.php');

$controller = JControllerLegacy::getInstance('Bazi');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
