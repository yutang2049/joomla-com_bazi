<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Administrator\View\Readings;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of readings.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var    array
     * @since  1.0.0
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var    \Joomla\CMS\Pagination\Pagination
     * @since  1.0.0
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var    \Joomla\CMS\Object\CMSObject
     * @since  1.0.0
     */
    protected $state;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_BAZI_READINGS_TITLE'), 'list');

        $user = $this->getCurrentUser();

        if ($user->authorise('core.admin', 'com_bazi'))
        {
            ToolbarHelper::preferences('com_bazi');
        }
    }
}
