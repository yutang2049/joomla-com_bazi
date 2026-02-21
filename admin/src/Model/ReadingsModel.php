<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of bazi readings records.
 *
 * @since  1.0.0
 */
class ReadingsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = [
                'id', 'a.id',
                'chart_id', 'a.chart_id',
                'status', 'a.status',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\QueryInterface
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('a.id, a.chart_id, a.status, a.created, a.created_by');
        $query->from($db->quoteName('#__bazi_readings', 'a'));

        // Join with charts table
        $query->select($db->quoteName('c.name', 'chart_name'))
            ->join('LEFT', $db->quoteName('#__bazi_charts', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.chart_id'));

        // Join with users table
        $query->select($db->quoteName('u.name', 'user_name'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));

        // Filter by status
        $status = $this->getState('filter.status');
        if (!empty($status))
        {
            $query->where($db->quoteName('a.status') . ' = ' . $db->quote($status));
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'a.created');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = 'a.created', $direction = 'DESC')
    {
        $status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status');
        $this->setState('filter.status', $status);

        parent::populateState($ordering, $direction);
    }
}
