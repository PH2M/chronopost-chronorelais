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
use Chronopost\Chronorelais\Helper\Webservice;
use Chronopost\Chronorelais\Helper\Data;
use Chronopost\Chronorelais\Model\Config\Source\ChronofreshOffers;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ChooseContract
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class ChooseContract extends Column
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Contract
     */
    protected $helper;

    /**
     * @var Webservice
     */
    protected $helperWS;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var ChronofreshOffers
     */
    private $chronofreshOffers;

    /**
     * ChooseContract constructor.
     *
     * @param ContextInterface     $context
     * @param UiComponentFactory   $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Contract             $helper
     * @param Webservice           $helperWS
     * @param Data                 $helperData
     * @param ChronofreshOffers    $chronofreshOffers
     * @param array                $components
     * @param array                $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        Contract $helper,
        Webservice $helperWS,
        Data $helperData,
        ChronofreshOffers $chronofreshOffers,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->helperWS = $helperWS;
        $this->helperData = $helperData;
        $this->chronofreshOffers = $chronofreshOffers;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareDataSource(array $dataSource): array
    {
        $render = '';
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['shipment_id'])) {
                    $entityId = $item['entity_id'];

                    $shippingMethodCode = $this->helperData->getShippingMethodeCode($item['shipping_method']);
                    if ($shippingMethodCode === Data::CHRONO_FRESH_CODE) {
                        $defaultChronofreshOffer = $this->helperData->getDefaultChronofreshOffer();
                        $contracts = $this->helper->getConfigContracts();
                        list($render, $selectedContracts) = $this->renderContractsDropDown(
                            $contracts,
                            $entityId,
                            $shippingMethodCode,
                            $defaultChronofreshOffer
                        );

                        $render .= '<br/><br/>';
                        $render .= $this->renderOffersDropDown(
                            $selectedContracts,
                            $entityId,
                            $shippingMethodCode,
                            $defaultChronofreshOffer
                        );
                    } else {
                        $contract = $this->helper->getContractByOrderId($entityId);
                        if ($contract === null) {
                            $contracts = $this->helper->getConfigContracts();
                            if (count($contracts)) {
                                list($render, $selectedContracts) = $this->renderContractsDropDown(
                                    $contracts,
                                    $entityId,
                                    $shippingMethodCode
                                );
                            }
                        } else {
                            $render = $contract->getData('contract_name');
                        }
                    }

                    $item[$this->getData('name')] = $render;
                }
            }
        }

        return $dataSource;
    }

    /**
     * Render offers dropdown
     *
     * @param int|null    $selectedContracts
     * @param string      $entityId
     * @param string      $shippingMethodCode
     * @param null|string $defaultOffer
     *
     * @return string
     */
    private function renderOffersDropDown(
        $selectedContracts,
        string $entityId,
        string $shippingMethodCode,
        $defaultOffer = null
    ): string {
        $render = '<select style="font-size: 12px;" data-entityid="' . $entityId .
            '" id="offers-' . $entityId . '">';

        $offers = $this->chronofreshOffers->toOptionArray();
        foreach ($offers as $key => $offer) {
            $isEnable = false;
            if ($selectedContracts) {
                $isEnable = $this->helperWS->shippingMethodEnabled(
                    $shippingMethodCode,
                    $selectedContracts,
                    $key
                );
            }

            if ($isEnable) {
                $selected = $defaultOffer === $key ? 'selected' : '';
                $render .= '<option value="' . $key . '" ' . $selected . '>' . $offer . '</option>';
            }
        }

        $render .= '</select>';

        return $render;
    }

    /**
     * Render contracts dropdown
     *
     * @param array       $contracts
     * @param string      $entityId
     * @param string      $shippingMethodCode
     * @param null|string $offer
     *
     * @return array
     */
    private function renderContractsDropDown(
        array $contracts,
        string $entityId,
        string $shippingMethodCode,
        $offer = null
    ): array {
        // Render contract dropdown
        $selectedContracts = null;
        $render = "<select style='font-size: 12px;' data-entityid='" . $entityId .
            "' id='contract-" . $entityId . "'>";
        foreach ($contracts as $key => $contract) {
            if ($this->helperWS->shippingMethodEnabled($shippingMethodCode, (int)$key, $offer)) {
                $defaultContract = $this->helper->getCarrierContract($shippingMethodCode);
                $selected = ($key === $defaultContract['contract_id']) ? 'selected' : '';
                $render .= '<option value="' . $key . '" ' . $selected . ' >' . $contract['name'] .
                    '</option>';

                if ($selected !== '') {
                    $selectedContracts = $key;
                }
            }
        }

        $render .= '<select>';

        return [$render, $selectedContracts];
    }
}
