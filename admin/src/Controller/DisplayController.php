<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * BaZi Component Display Controller
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'charts';
}
