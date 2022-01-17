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
use Chronopost\Chronorelais\Helper\Contract;
use Magento\Framework\Phrase;

/**
 * Class Contracts
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class Contracts extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Chronopost_Chronorelais::system/config/contracts.phtml';

    /**
     * @var Data
     */
    public $helper;

    /**
     * Contracts constructor.
     *
     * @param Context  $context
     * @param Contract $helper
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Contract $helper,
        array $data = []
    ) {
        $this->helper = $helper;
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
        return parent::_getElementHtml($element) . $this->_toHtml();
    }

    /**
     * Return element html
     *
     * @return string
     */
    public function getConfigContracts()
    {
        return $this->helper->getConfigContracts();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('chronorelais/system_config/checklogin');
    }

    /**
     * @return Phrase
     */
    public function getLabelButtonDelete()
    {
        return __('Delete contract');
    }

    /**
     * @return Phrase
     */
    public function getLabelButtonCheck()
    {
        return __('Check contract');
    }

    /**
     * @return Phrase
     */
    public function getLabelButtonCreate(): Phrase
    {
        return __('Add contract');
    }
}
