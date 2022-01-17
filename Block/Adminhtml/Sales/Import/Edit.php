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

namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Import;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Button;
use Chronopost\Chronorelais\Block\Adminhtml\Sales\Import\Edit\Form;

/**
 * Class Edit
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\Sales\Import
 */
class Edit extends Template
{
    protected $_template = "sales/import/edit.phtml";
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->getToolbar()->addChild(
            'reset_button',
            Button::class,
            [
                'label' => __('Reset'),
                'onclick' => 'window.location.href = window.location.href',
                'class' => 'reset'
            ]
        );

        $this->getToolbar()->addChild(
            'save_button',
            Button::class,
            [
                'label' => __('Import'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#sales_import_edit_form']],
                ]

            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Return edit flag for block
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEditMode()
    {
        return false;
    }

    /**
     * Return header text for form
     *
     * @return Phrase
     */
    public function getHeaderText()
    {
        return __('Import a list of parcel numbers');
    }

    /**
     * Return form block HTML
     *
     * @return string
     * @throws LocalizedException
     */
    public function getForm()
    {
        return $this->getLayout()->createBlock(Form::class)->toHtml();
    }

    /**
     * Return action url for form
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/import_save');
    }

    /**
     * Retrieve Save As Flag
     *
     * @return string
     */
    public function getSaveAsFlag()
    {
        return $this->getRequest()->getParam('_save_as_flag') ? '1' : '';
    }

    /**
     * Getter for single store mode check
     *
     * @return bool
     */
    protected function isSingleStoreMode()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    /**
     * Getter for id of current store (the only one in single-store mode and current in multi-stores mode)
     *
     * @return int
     * @throws NoSuchEntityException
     */
    protected function getStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }
}
