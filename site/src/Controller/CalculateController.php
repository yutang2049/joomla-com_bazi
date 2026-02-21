<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_bazi
 *
 * @copyright   Copyright (C) 2026 Yutang2049
 * @license     GNU General Public License version 2 or later
 */

namespace Yutang\Component\Bazi\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Yutang\Component\Bazi\Site\Service\AntiSpamService;
use Yutang\Component\Bazi\Site\Service\BaziCalculator;
use Yutang\Component\Bazi\Site\Service\TrueSolarTime;

/**
 * Calculate Controller
 *
 * @since  1.0.0
 */
class CalculateController extends BaseController
{
    /**
     * Calculate BaZi chart
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function submit()
    {
        // Check for token
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $input = $app->input;
        $user = $app->getIdentity();
        $params = $app->getParams('com_bazi');

        // Get form data
        $data = [
            'name' => $input->getString('name', ''),
            'birth_date' => $input->getString('birth_date', ''),
            'birth_time' => $input->getString('birth_time', ''),
            'gender' => $input->getString('gender', ''),
            'location_name' => $input->getString('location_name', ''),
            'longitude' => $input->getFloat('longitude', null),
            'latitude' => $input->getFloat('latitude', null),
            'timezone_offset' => $input->getFloat('timezone_offset', null),
            'save_chart' => $input->getInt('save_chart', 0),
        ];

        // Get honeypot field
        $antiSpam = new AntiSpamService();
        $honeypotField = $antiSpam->getHoneypotFieldName();
        $data[$honeypotField] = $input->getString($honeypotField, '');

        try
        {
            // Anti-spam checks
            if (!$antiSpam->checkHoneypot($data))
            {
                $app->enqueueMessage(Text::_('COM_BAZI_ERROR_HONEYPOT'), 'error');
                $app->redirect(Route::_('index.php?option=com_bazi&view=calculate', false));
                return;
            }

            // Rate limiting
            $userId = $user->id ?: null;
            $ip = $app->input->server->getString('REMOTE_ADDR', '');

            if (!$antiSpam->checkRateLimit($userId, $ip))
            {
                $app->setHeader('Status', '429 Too Many Requests', true);
                $app->enqueueMessage(Text::_('COM_BAZI_ERROR_RATE_LIMIT'), 'error');
                $app->redirect(Route::_('index.php?option=com_bazi&view=calculate', false));
                return;
            }

            // Record request
            $antiSpam->recordRequest($userId, $ip);

            // Validate required fields
            if (empty($data['birth_date']) || empty($data['birth_time']) || empty($data['gender']))
            {
                throw new \Exception(Text::_('COM_BAZI_ERROR_INVALID_INPUT'));
            }

            // Validate gender
            if (!in_array($data['gender'], ['male', 'female']))
            {
                throw new \Exception(Text::_('COM_BAZI_ERROR_INVALID_INPUT'));
            }

            // Parse birth datetime
            $birthDateTime = new \DateTime($data['birth_date'] . ' ' . $data['birth_time'], new \DateTimeZone('UTC'));

            // Validate year range
            $year = (int) $birthDateTime->format('Y');
            $yearMin = $params->get('year_min', 1900);
            $yearMax = $params->get('year_max', 2050);

            if ($year < $yearMin || $year > $yearMax)
            {
                throw new \Exception(sprintf(Text::_('COM_BAZI_ERROR_YEAR_OUT_OF_RANGE'), $yearMin, $yearMax));
            }

            // Apply true solar time correction if enabled and longitude provided
            $normalizedDateTime = $birthDateTime;
            $trueSolarTimeApplied = false;
            $correctionSummary = null;

            if ($params->get('true_solar_time', 1) && $data['longitude'] !== null)
            {
                $applyLongitude = $params->get('longitude_correction', 1);
                $applyEoT = $params->get('eot_correction', 1);

                $normalizedDateTime = TrueSolarTime::correct(
                    $birthDateTime,
                    $data['longitude'],
                    (bool) $applyLongitude,
                    (bool) $applyEoT
                );

                $trueSolarTimeApplied = true;
                $correctionSummary = TrueSolarTime::getCorrectionSummary(
                    $birthDateTime,
                    $normalizedDateTime,
                    $data['longitude']
                );
            }

            // Calculate BaZi
            $calculator = new BaziCalculator();
            $options = [
                'liunian_range' => $params->get('liunian_range', 5),
                'true_solar_time' => $trueSolarTimeApplied,
                'correction_summary' => $correctionSummary,
            ];

            $baziResult = $calculator->calculate($normalizedDateTime, $data['gender'], $options);

            // Save chart if user is logged in and wants to save
            $chartId = null;
            if ($user->id && $data['save_chart'])
            {
                // Check permission
                if ($user->authorise('bazi.chart.create', 'com_bazi'))
                {
                    $chartId = $this->saveChart($user->id, $data, $normalizedDateTime, $baziResult);
                    $app->enqueueMessage(Text::_('COM_BAZI_MSG_CHART_SAVED'), 'success');
                }
                else
                {
                    $app->enqueueMessage(Text::_('COM_BAZI_ERROR_PERMISSION_DENIED'), 'warning');
                }
            }

            // Store result in session for display
            $app->setUserState('com_bazi.calculate.result', $baziResult);
            $app->setUserState('com_bazi.calculate.input', $data);
            $app->setUserState('com_bazi.calculate.chart_id', $chartId);

            $app->enqueueMessage(Text::_('COM_BAZI_MSG_CALCULATION_COMPLETE'), 'success');
        }
        catch (\Exception $e)
        {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        // Redirect back to calculate view
        $app->redirect(Route::_('index.php?option=com_bazi&view=calculate', false));
    }

    /**
     * Save chart to database
     *
     * @param   integer   $userId             User ID
     * @param   array     $inputData          Input data
     * @param   \DateTime $normalizedDateTime Normalized datetime
     * @param   array     $baziResult         BaZi calculation result
     *
     * @return  integer  Chart ID
     *
     * @since   1.0.0
     * @throws  \Exception
     */
    protected function saveChart(int $userId, array $inputData, \DateTime $normalizedDateTime, array $baziResult): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $now = Factory::getDate('now', 'UTC')->toSql();

        // Prepare options JSON
        $optionsJson = json_encode([
            'true_solar_time' => $baziResult['options']['true_solar_time'] ?? false,
            'correction_summary' => $baziResult['options']['correction_summary'] ?? null,
        ]);

        // Prepare BaZi JSON
        $baziJson = json_encode($baziResult);

        // Insert chart
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__bazi_charts'))
            ->columns([
                $db->quoteName('user_id'),
                $db->quoteName('created'),
                $db->quoteName('modified'),
                $db->quoteName('birth_date'),
                $db->quoteName('birth_time'),
                $db->quoteName('gender'),
                $db->quoteName('name'),
                $db->quoteName('location_name'),
                $db->quoteName('longitude'),
                $db->quoteName('latitude'),
                $db->quoteName('timezone_offset'),
                $db->quoteName('normalized_datetime'),
                $db->quoteName('options_json'),
                $db->quoteName('bazi_json'),
                $db->quoteName('algo_version'),
            ])
            ->values(':userId, :created, :modified, :birthDate, :birthTime, :gender, :name, :location, :longitude, :latitude, :timezone, :normalized, :options, :bazi, :version')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':created', $now)
            ->bind(':modified', $now)
            ->bind(':birthDate', $inputData['birth_date'])
            ->bind(':birthTime', $inputData['birth_time'])
            ->bind(':gender', $inputData['gender'])
            ->bind(':name', $inputData['name'])
            ->bind(':location', $inputData['location_name'])
            ->bind(':longitude', $inputData['longitude'])
            ->bind(':latitude', $inputData['latitude'])
            ->bind(':timezone', $inputData['timezone_offset'])
            ->bind(':normalized', $normalizedDateTime->format('Y-m-d H:i:s'))
            ->bind(':options', $optionsJson)
            ->bind(':bazi', $baziJson)
            ->bind(':version', BaziCalculator::ALGO_VERSION);

        $db->setQuery($query);
        $db->execute();

        return $db->insertid();
    }
}
