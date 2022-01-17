<?php
/**
 * Chronopost
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Chronopost
 * @package   Chronopost_Chronorelais
 * @copyright Copyright (c) 2021 Chronopost
 */
declare(strict_types=1);

namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Chronopost\Chronorelais\Model\Config\Source\Day;
use Chronopost\Chronorelais\Model\Config\Source\Hour;
use Chronopost\Chronorelais\Model\Config\Source\Minute;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Date
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class Date extends Field
{
    /**
     * @var Day
     */
    protected $_sourceDay;

    /**
     * @var Hour
     */
    protected $_sourceHour;

    /**
     * @var Minute
     */
    protected $_sourceMinute;


    /**
     * Date constructor.
     *
     * @param Context $context
     * @param Day     $day
     * @param Hour    $hour
     * @param Minute  $minute
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Day $day,
        Hour $hour,
        Minute $minute,
        array $data = []
    ) {
        $this->_sourceDay = $day;
        $this->_sourceHour = $hour;
        $this->_sourceMinute = $minute;
        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setStyle('width:70px;')
            ->setName($element->getName() . '[]');

        if ($element->getValue()) {
            $values = explode(':', $element->getValue());
        } else {
            $values = [];
        }

        $date = $element->setValues($this->_sourceDay->toOptionArray())
            ->setValue(isset($values[0]) ? $values[0] : null)->getElementHtml();
        $heure = $element->setValues($this->_sourceHour->toOptionArray())
            ->setValue(isset($values[1]) ? $values[1] : null)->getElementHtml();
        $minutes = $element->setValues($this->_sourceMinute->toOptionArray())
            ->setValue(isset($values[2]) ? $values[2] : null)->getElementHtml();

        return __('Date') . ' : ' . $date
            . ' '
            . __('Time') . ' : ' . $heure . ' ' . $minutes;
    }
}
