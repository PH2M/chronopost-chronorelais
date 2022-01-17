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
use Magento\Framework\Module\ModuleListInterface;
use Chronopost\Chronorelais\Helper\Data as HelperData;

/**
 * Class ModuleVersion
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class ModuleVersion extends Field
{

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * ModuleVersion constructor.
     *
     * @param Context             $context
     * @param ModuleListInterface $moduleList
     * @param array               $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->_moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
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
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return '<strong>' . $this->_getVersion() . '</strong>';
    }

    /**
     * Return current version of Module
     *
     * @return mixed|string
     */
    protected function _getVersion()
    {
        $infos = $this->_moduleList->getOne(HelperData::MODULE_NAME);

        return is_array($infos) ? $infos['setup_version'] : '';
    }
}
