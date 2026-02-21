<?php
/**
 * com_bazi – Bazi Model
 *
 * Validates user input and delegates calculation to BaziHelper.
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;

class BaziModelBazi extends JModelLegacy
{
    /** @var array|null  Computed chart (null before first calculate()). */
    protected $chart = null;

    /** @var array  Input values echoed back to the view. */
    protected $input = [];

    /** @var string  Error message (empty when no error). */
    protected $error = '';

    /**
     * Calculate the Bazi chart from the current request.
     *
     * @return  bool  True on success.
     */
    public function calculate()
    {
        $app    = JFactory::getApplication();
        $jinput = $app->input;

        $year   = (int)$jinput->getInt('year',   0);
        $month  = (int)$jinput->getInt('month',  0);
        $day    = (int)$jinput->getInt('day',    0);
        $hour   = (int)$jinput->getInt('hour',   0);
        $gender = (int)$jinput->getInt('gender', 1);

        $this->input = compact('year', 'month', 'day', 'hour', 'gender');

        // Basic validation
        if ($year < 1900 || $year > 2100) {
            $this->error = JText::_('COM_BAZI_ERROR_YEAR_RANGE');
            return false;
        }
        if ($month < 1 || $month > 12) {
            $this->error = JText::_('COM_BAZI_ERROR_MONTH_RANGE');
            return false;
        }
        if ($day < 1 || $day > 31) {
            $this->error = JText::_('COM_BAZI_ERROR_DAY_RANGE');
            return false;
        }
        if ($hour < 0 || $hour > 23) {
            $this->error = JText::_('COM_BAZI_ERROR_HOUR_RANGE');
            return false;
        }

        // Verify date is real
        if (!checkdate($month, $day, $year)) {
            $this->error = JText::_('COM_BAZI_ERROR_DATE_INVALID');
            return false;
        }

        $this->chart = BaziHelper::calculate($year, $month, $day, $hour, $gender);
        return true;
    }

    /** @return array|null */
    public function getChart()   { return $this->chart; }
    /** @return array */
    public function getInput()   { return $this->input; }
    /** @return string */
    public function getError()   { return $this->error; }
    /** @return bool */
    public function hasResult()  { return $this->chart !== null; }
}
