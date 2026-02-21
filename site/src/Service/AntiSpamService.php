<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Anti-spam Service for rate limiting and bot protection
 *
 * @since  1.0.0
 */
class AntiSpamService
{
    /**
     * Database instance
     *
     * @var    DatabaseInterface
     * @since  1.0.0
     */
    protected $db;

    /**
     * Component configuration
     *
     * @var    \Joomla\Registry\Registry
     * @since  1.0.0
     */
    protected $params;

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   mixed              $params  Component parameters
     *
     * @since   1.0.0
     */
    public function __construct(DatabaseInterface $db = null, $params = null)
    {
        $this->db = $db ?? Factory::getContainer()->get(DatabaseInterface::class);
        $this->params = $params ?? Factory::getApplication()->getParams('com_bazi');
    }

    /**
     * Check if request passes honeypot validation
     *
     * @param   array  $data  Form data
     *
     * @return  boolean  True if passes (honeypot empty), false if fails (honeypot filled)
     *
     * @since   1.0.0
     */
    public function checkHoneypot(array $data): bool
    {
        if (!$this->params->get('honeypot_enabled', 1))
        {
            return true;
        }

        $honeypotField = $this->params->get('honeypot_field_name', 'website');

        // Honeypot should be empty
        return empty($data[$honeypotField]);
    }

    /**
     * Check rate limit for user or IP
     *
     * @param   integer|null  $userId  User ID (null for guest)
     * @param   string|null   $ip      IP address
     *
     * @return  boolean  True if within limit, false if exceeded
     *
     * @since   1.0.0
     */
    public function checkRateLimit(?int $userId, ?string $ip): bool
    {
        $window = $this->params->get('rate_limit_window', 3600);
        $maxRequests = $userId ?
            $this->params->get('rate_limit_user_max', 20) :
            $this->params->get('rate_limit_guest_max', 5);

        // Determine identifier
        $identifier = $userId ? (string) $userId : $ip;
        $identifierType = $userId ? 'user' : 'ip';

        if (!$identifier)
        {
            return false;
        }

        // Clean up old records
        $this->cleanupRateLimits($window);

        // Get current window start
        $windowStart = Factory::getDate('now', 'UTC')
            ->sub(new \DateInterval('PT' . $window . 'S'))
            ->toSql();

        // Count requests in current window
        $query = $this->db->getQuery(true)
            ->select('SUM(' . $this->db->quoteName('request_count') . ')')
            ->from($this->db->quoteName('#__bazi_rate_limits'))
            ->where($this->db->quoteName('identifier') . ' = :identifier')
            ->where($this->db->quoteName('identifier_type') . ' = :type')
            ->where($this->db->quoteName('window_start') . ' >= :windowStart')
            ->bind(':identifier', $identifier)
            ->bind(':type', $identifierType)
            ->bind(':windowStart', $windowStart);

        $this->db->setQuery($query);
        $count = (int) $this->db->loadResult();

        return $count < $maxRequests;
    }

    /**
     * Record a request for rate limiting
     *
     * @param   integer|null  $userId  User ID (null for guest)
     * @param   string|null   $ip      IP address
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function recordRequest(?int $userId, ?string $ip): void
    {
        $identifier = $userId ? (string) $userId : $ip;
        $identifierType = $userId ? 'user' : 'ip';

        if (!$identifier)
        {
            return;
        }

        $now = Factory::getDate('now', 'UTC');
        $windowStart = $now->toSql();

        // Try to update existing record for current window
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__bazi_rate_limits'))
            ->set($this->db->quoteName('request_count') . ' = ' . $this->db->quoteName('request_count') . ' + 1')
            ->set($this->db->quoteName('last_request') . ' = :now')
            ->where($this->db->quoteName('identifier') . ' = :identifier')
            ->where($this->db->quoteName('identifier_type') . ' = :type')
            ->where($this->db->quoteName('window_start') . ' = :windowStart')
            ->bind(':now', $windowStart)
            ->bind(':identifier', $identifier)
            ->bind(':type', $identifierType)
            ->bind(':windowStart', $windowStart);

        $this->db->setQuery($query);
        $this->db->execute();

        // If no rows updated, insert new record
        if ($this->db->getAffectedRows() === 0)
        {
            $query = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__bazi_rate_limits'))
                ->columns([
                    $this->db->quoteName('identifier'),
                    $this->db->quoteName('identifier_type'),
                    $this->db->quoteName('window_start'),
                    $this->db->quoteName('request_count'),
                    $this->db->quoteName('last_request'),
                ])
                ->values(':identifier, :type, :windowStart, 1, :now')
                ->bind(':identifier', $identifier)
                ->bind(':type', $identifierType)
                ->bind(':windowStart', $windowStart)
                ->bind(':now', $windowStart);

            $this->db->setQuery($query);
            $this->db->execute();
        }
    }

    /**
     * Clean up old rate limit records
     *
     * @param   integer  $window  Window size in seconds
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function cleanupRateLimits(int $window): void
    {
        $cutoffTime = Factory::getDate('now', 'UTC')
            ->sub(new \DateInterval('PT' . ($window * 2) . 'S'))
            ->toSql();

        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__bazi_rate_limits'))
            ->where($this->db->quoteName('window_start') . ' < :cutoff')
            ->bind(':cutoff', $cutoffTime);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    /**
     * Get honeypot field name for form rendering
     *
     * @return  string  Honeypot field name
     *
     * @since   1.0.0
     */
    public function getHoneypotFieldName(): string
    {
        return $this->params->get('honeypot_field_name', 'website');
    }

    /**
     * Check if honeypot is enabled
     *
     * @return  boolean  True if enabled
     *
     * @since   1.0.0
     */
    public function isHoneypotEnabled(): bool
    {
        return (bool) $this->params->get('honeypot_enabled', 1);
    }

    /**
     * Check if CAPTCHA is enabled
     *
     * @return  boolean  True if enabled
     *
     * @since   1.0.0
     */
    public function isCaptchaEnabled(): bool
    {
        return (bool) $this->params->get('captcha_enabled', 0);
    }
}
