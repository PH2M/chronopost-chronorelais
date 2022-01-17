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

namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Import\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * Class Form
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\Sales\Import\Edit
 */
class Form extends Generic
{

    /**
     * @var CollectionFactory
     */
    protected $_configCollectionFactory;

    /**
     * Form constructor.
     *
     * @param Context           $context
     * @param Registry          $registry
     * @param FormFactory       $formFactory
     * @param CollectionFactory $collectionFactory
     * @param array             $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_configCollectionFactory = $collectionFactory;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $_configFactory = $this->_configCollectionFactory->create();
        $columnParcel = $_configFactory->addFieldToFilter('path', ['eq' => 'chronopost/import/number_column_parcel'])
            ->getFirstItem()
            ->getValue();

        $_configFactory = $this->_configCollectionFactory->create();
        $columnOrder = $_configFactory->addFieldToFilter('path', ['eq' => 'chronopost/import/number_column_order'])
            ->getFirstItem()
            ->getValue();

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __(''), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'import_chronorelais_tracking_title',
            'text',
            [
                'name'     => 'import_chronorelais_tracking_title',
                'label'    => __('Tracking title'),
                'title'    => __('Tracking title'),
                'required' => true,
                'value'    => __('Chronopost - Chronopost express home deliveries')
            ]
        );

        $fieldset->addField(
            'import_chronorelais_column_parcel',
            'text',
            [
                'name'     => 'import_chronorelais_column_parcel',
                'label'    => __('Colonne contenant le numéro du colis'),
                'title'    => __('Colonne contenant le numéro du colis'),
                'required' => true,
                'value'    => $columnParcel
            ]
        );

        $fieldset->addField(
            'import_chronorelais_column_order',
            'text',
            [
                'name'     => 'import_chronorelais_column_order',
                'label'    => __('Colonne contenant le numéro de commande'),
                'title'    => __('Colonne contenant le numéro de commande'),
                'required' => true,
                'value'    => $columnOrder
            ]
        );

        $fieldset->addField(
            'import_chronorelais_file',
            'file',
            [
                'name'     => 'import_chronorelais_file',
                'label'    => __('Import file'),
                'title'    => __('Import file'),
                'required' => true,
                'note'     => __('Line format: Order increment Id, tracking number')
            ]
        );

        $form->setAction($this->getUrl('*/*/import_save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
