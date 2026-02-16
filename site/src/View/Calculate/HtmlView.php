<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Site\View\Calculate;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Yutang\Component\Bazi\Site\Service\AntiSpamService;

/**
 * HTML Calculate View class for the BaZi component
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The page parameters
     *
     * @var    \Joomla\Registry\Registry|null
     * @since  1.0.0
     */
    protected $params = null;

    /**
     * The user object
     *
     * @var    \Joomla\CMS\User\User
     * @since  1.0.0
     */
    protected $user = null;

    /**
     * Calculation result from session
     *
     * @var    array|null
     * @since  1.0.0
     */
    protected $result = null;

    /**
     * Input data from session
     *
     * @var    array|null
     * @since  1.0.0
     */
    protected $inputData = null;

    /**
     * Chart ID if saved
     *
     * @var    integer|null
     * @since  1.0.0
     */
    protected $chartId = null;

    /**
     * Anti-spam service
     *
     * @var    AntiSpamService
     * @since  1.0.0
     */
    protected $antiSpam = null;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        
        $this->params = $app->getParams();
        $this->user = $app->getIdentity();
        $this->antiSpam = new AntiSpamService();

        // Get calculation result from session if available
        $this->result = $app->getUserState('com_bazi.calculate.result');
        $this->inputData = $app->getUserState('com_bazi.calculate.input');
        $this->chartId = $app->getUserState('com_bazi.calculate.chart_id');

        // Clear session data after retrieving
        if ($this->result)
        {
            $app->setUserState('com_bazi.calculate.result', null);
            $app->setUserState('com_bazi.calculate.input', null);
            $app->setUserState('com_bazi.calculate.chart_id', null);
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function prepareDocument()
    {
        $app = Factory::getApplication();
        $title = Text::_('COM_BAZI_PAGE_TITLE');

        if ($app->get('sitename_pagetitles', 0) == 1)
        {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        }
        elseif ($app->get('sitename_pagetitles', 0) == 2)
        {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);
    }
}
