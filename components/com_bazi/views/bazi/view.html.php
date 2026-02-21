<?php
/**
 * com_bazi – HTML View
 *
 * @package  com_bazi
 */
defined('_JEXEC') or die;

class BaziViewBazi extends JViewLegacy
{
    /** @var array|null */
    protected $chart;
    /** @var array */
    protected $formData;
    /** @var string */
    protected $error;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        // Process form submission
        $app    = JFactory::getApplication();
        $submit = $app->input->getCmd('task', '');

        if ($submit === 'calculate' || $app->input->getInt('year', 0) > 0) {
            $model->calculate();
        }

        $this->chart    = $model->getChart();
        $this->formData = $model->getInput();
        $this->error    = $model->getError();

        // Add page title
        $doc = JFactory::getDocument();
        $doc->setTitle(JText::_('COM_BAZI_PAGE_TITLE'));

        parent::display($tpl);
    }
}
