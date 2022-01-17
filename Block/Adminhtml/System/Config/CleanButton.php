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
use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\Phrase;

/**
 * Class CleanButton
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class CleanButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Chronopost_Chronorelais::system/config/cleanbutton.phtml';

    /**
     * @var Data
     */
    public $helper;

    /**
     * CleanButton constructor.
     *
     * @param Context $context
     * @param Data    $helper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
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
        $this->addData([
            'html_id' => $element->getHtmlId()
        ]);

        return $this->_toHtml();
    }

    /**
     * Get label button
     *
     * @return Phrase
     */
    public function getLabelButtonCleanInformations()
    {
        return __('Clean Informations');
    }
}
