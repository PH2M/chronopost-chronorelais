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

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Chronopost\Chronorelais\Helper\Webservice;
use Chronopost\Chronorelais\Helper\Data;

/**
 * Class Enabled
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class Enabled extends Field
{

    /**
     * @var Webservice
     */
    protected $helperWS;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * Enabled constructor.
     *
     * @param Context    $context
     * @param Webservice $helperWS
     * @param Data       $helperData
     * @param array      $data
     */
    public function __construct(
        Context $context,
        Webservice $helperWS,
        Data $helperData,
        array $data = []
    ) {
        $this->helperWS = $helperWS;
        $this->helperData = $helperData;
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
        $id = $element->getId();
        $carrier = explode('_', $id);

        if (isset($carrier[1])) {
            $offer = null;
            if ($carrier[1] === Data::CHRONO_FRESH_CODE) {
                $offer = $this->helperData->getDefaultChronofreshOffer();
            }

            if (!$this->helperWS->shippingMethodEnabled($carrier[1], 1000, $offer) ||
                    !$this->helperData->shippingMethodIsEnabled($carrier[1])) {
                $element->setDisabled('disabled');
                $element->setValue(0);
            }
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }
}
