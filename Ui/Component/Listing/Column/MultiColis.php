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

namespace Chronopost\Chronorelais\Ui\Component\Listing\Column;

use Chronopost\Chronorelais\Helper\Contract;
use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class MultiColis
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class MultiColis extends Column
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Contract
     */
    private $helperContract;

    /**
     * MultiColis constructor.
     *
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param UrlInterface          $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param FormKey               $formKey
     * @param ScopeConfigInterface  $scopeConfig
     * @param Data                  $helper
     * @param Contract              $helperContract
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        FormKey $formKey,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Contract $helperContract,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->formKey = $formKey;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->helperContract = $helperContract;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['shipment_id'])) {
                    $item[$this->getData('name')] = '';

                    $indexFieldValues = explode(',', $item['shipment_id']);
                    if ($item['shipment_id'] === '') {
                        $indexFieldValues = [$item['entity_id']];
                    }

                    $url = $this->urlBuilder->getUrl($this->getData('config/viewUrlPathGenerate') ?: '#');
                    $render = '<form class="form_' . $item['entity_id'] . '" id="form_' . $item['entity_id'] .
                        '" action="' . $url . '" method="post">';
                    $render .= '<input name="form_key" type="hidden" value="' . $this->formKey->getFormKey() . '" />';

                    $totalWeight = $this->helper->getWeightOfOrder($item['entity_id'], true);
                    $dimensions = '{"0":{"weight":"' . $totalWeight . '","width":"1","height":"1","length":"1"}}';

                    $render .= '<input type="hidden" id="order_dimensions" class="dimensions container"' .
                        'name="dimensions" value=' . $dimensions . ' />';
                    $render .= '<input type="hidden" name="order_id" value="' . $item['entity_id'] . '" />';

                    $contractId = null;
                    $shippingMethodCode = $this->helper->getShippingMethodeCode($item['shipping_method']);
                    if ($shippingMethodCode === Data::CHRONO_FRESH_CODE) {
                        $contract = $this->helperContract->getCarrierContract($shippingMethodCode);
                        $contractId = $contract['contract_id'];
                    } else {
                        $contract = $this->helperContract->getContractByOrderId($item['entity_id']);
                        if ($contract === null) {
                            $contract = $this->helperContract->getCarrierContract($shippingMethodCode);
                            $contractId = $contract['contract_id'];
                        } else {
                            $accountNumber = $contract->getData('contract_account_number');
                            $contract = $this->helperContract->getContractByNumber($accountNumber);
                            if ($contract) {
                                $contractId = $contract['contract_id'];
                            }
                        }
                    }

                    $render .= "<input type='hidden' value='" . $contractId . "' name='contract'/>";

                    if ($shippingMethodCode === Data::CHRONO_FRESH_CODE) {
                        $offer = $this->helper->getDefaultChronofreshOffer();
                        $render .= "<input type='hidden' value='" . $offer . "' name='offer'/>";

                        $expirationDate = $this->helper->getDefaultExpirationDate();
                        $render .= "<input type='hidden' value='" . $expirationDate . "' name='expiration_date'/>";
                    }

                    $render .= "<input class='input-text' data-orderid='" .
                        $item['entity_id'] . "' type='text' name='nb_colis' value='1'/><br>";

                    if (count($indexFieldValues) === 1) {
                        $render .= '<input name="shipment_id" type="hidden" value="' . $item['shipment_id'] . '" />';
                    } else {
                        $render .= '<select style="margin-bottom:5px;text-align:center;" name="shipment_id" required >';
                        $render .= '<option value="">' . __('Select a shipment') . '</option>';
                        foreach ($indexFieldValues as $indexFieldValue) {
                            $render .= '<option value="' . trim($indexFieldValue) . '">' . $indexFieldValue . '</option>';
                        }
                        $render .= '</select>';
                    }

                    $render .= '<button id="generate-label" type="submit">' . __('Generate') . '</button>';
                    $render .= '</form>';
                    $item[$this->getData('name')] = $render;
                }
            }
        }

        return $dataSource;
    }
}
