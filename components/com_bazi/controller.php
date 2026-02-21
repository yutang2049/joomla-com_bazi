<?php
/**
 * com_bazi – Default controller
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;

class BaziController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->getView('bazi', 'html');
        $view->setModel($this->getModel('bazi'), true);
        $view->display();
    }
}
