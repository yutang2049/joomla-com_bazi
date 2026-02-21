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

use DateTime;
use DateInterval;

/**
 * True Solar Time Correction Service
 * Applies longitude correction and Equation of Time (EoT) correction
 *
 * @since  1.0.0
 */
class TrueSolarTime
{
    /**
     * Standard longitude for timezone (东经120° for UTC+8)
     *
     * @var    float
     * @since  1.0.0
     */
    const STANDARD_LONGITUDE = 120.0;

    /**
     * Apply true solar time correction to birth datetime
     *
     * @param   DateTime  $datetime          Original datetime
     * @param   float     $longitude         Location longitude
     * @param   boolean   $applyLongitude    Apply longitude correction
     * @param   boolean   $applyEoT          Apply Equation of Time correction
     *
     * @return  DateTime  Corrected datetime
     *
     * @since   1.0.0
     */
    public static function correct(
        DateTime $datetime,
        float $longitude,
        bool $applyLongitude = true,
        bool $applyEoT = true
    ): DateTime {
        $correctedTime = clone $datetime;

        // Apply longitude correction
        if ($applyLongitude)
        {
            $longitudeCorrection = self::calculateLongitudeCorrection($longitude);
            $correctedTime->modify(sprintf('%+d seconds', $longitudeCorrection));
        }

        // Apply Equation of Time correction
        if ($applyEoT)
        {
            $eotCorrection = self::calculateEquationOfTime($correctedTime);
            $correctedTime->modify(sprintf('%+d seconds', $eotCorrection));
        }

        return $correctedTime;
    }

    /**
     * Calculate longitude correction in seconds
     * 4 minutes per degree of longitude difference from standard meridian
     *
     * @param   float  $longitude  Location longitude
     *
     * @return  integer  Correction in seconds
     *
     * @since   1.0.0
     */
    protected static function calculateLongitudeCorrection(float $longitude): int
    {
        $longitudeDiff = $longitude - self::STANDARD_LONGITUDE;

        // 4 minutes (240 seconds) per degree
        return (int) round($longitudeDiff * 240);
    }

    /**
     * Calculate Equation of Time correction in seconds
     * Simplified formula based on day of year
     *
     * @param   DateTime  $datetime  Date for calculation
     *
     * @return  integer  Correction in seconds
     *
     * @since   1.0.0
     */
    protected static function calculateEquationOfTime(DateTime $datetime): int
    {
        $dayOfYear = (int) $datetime->format('z');

        // Calculate B value in radians
        $B = 2 * pi() * ($dayOfYear - 81) / 365;

        // Equation of Time in minutes (simplified Spencer formula)
        $eot = 9.87 * sin(2 * $B) - 7.53 * cos($B) - 1.5 * sin($B);

        // Convert to seconds
        return (int) round($eot * 60);
    }

    /**
     * Get correction summary for display
     *
     * @param   DateTime  $original   Original datetime
     * @param   DateTime  $corrected  Corrected datetime
     * @param   float     $longitude  Location longitude
     *
     * @return  array  Correction summary
     *
     * @since   1.0.0
     */
    public static function getCorrectionSummary(
        DateTime $original,
        DateTime $corrected,
        float $longitude
    ): array {
        $diff = $original->diff($corrected);
        $totalSeconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        if ($diff->invert)
        {
            $totalSeconds = -$totalSeconds;
        }

        $longitudeCorrection = self::calculateLongitudeCorrection($longitude);
        $eotCorrection = self::calculateEquationOfTime($corrected);

        return [
            'longitude_correction_seconds' => $longitudeCorrection,
            'eot_correction_seconds' => $eotCorrection,
            'total_correction_seconds' => $totalSeconds,
            'total_correction_minutes' => round($totalSeconds / 60, 2),
            'original_time' => $original->format('Y-m-d H:i:s'),
            'corrected_time' => $corrected->format('Y-m-d H:i:s'),
        ];
    }
}
