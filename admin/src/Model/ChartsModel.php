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
 * Methods supporting a list of bazi charts records.
 *
 * @since  1.0.0
 */
class ChartsModel extends ListModel
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
                'name', 'a.name',
                'birth_date', 'a.birth_date',
                'gender', 'a.gender',
                'user_id', 'a.user_id',
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

        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.user_id, a.created, a.birth_date, a.birth_time, a.gender, a.name, a.location_name'
            )
        );

        $query->from($db->quoteName('#__bazi_charts', 'a'));

        // Join with users table
        $query->select($db->quoteName('u.name', 'user_name'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'));

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search))
        {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(a.name LIKE ' . $search . ' OR a.location_name LIKE ' . $search . ')');
        }

        // Filter by user
        $userId = $this->getState('filter.user_id');
        if (is_numeric($userId))
        {
            $query->where($db->quoteName('a.user_id') . ' = ' . (int) $userId);
        }

        // Filter by gender
        $gender = $this->getState('filter.gender');
        if (!empty($gender))
        {
            $query->where($db->quoteName('a.gender') . ' = ' . $db->quote($gender));
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
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $userId = $this->getUserStateFromRequest($this->context . '.filter.user_id', 'filter_user_id');
        $this->setState('filter.user_id', $userId);

        $gender = $this->getUserStateFromRequest($this->context . '.filter.gender', 'filter_gender');
        $this->setState('filter.gender', $gender);

        parent::populateState($ordering, $direction);
    }
}
