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

namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment;

use Chronopost\Chronorelais\Helper\Contract;
use Chronopost\Chronorelais\Helper\Data;
use Chronopost\Chronorelais\Model\Config\Source\ChronofreshOffers;
use Magento\Backend\Block\Template\Context;
use Chronopost\Chronorelais\Helper\Webservice as HelperWS;
use Magento\Framework\View\Element\Template;

/**
 * Class Dimensions
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment
 */
class Dimensions extends Template
{

    /**
     * @var HelperWS
     */
    private $helperWs;

    /**
     * @var Contract
     */
    private $helperContract;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var ChronofreshOffers
     */
    private $chronofreshOffers;

    /**
     * Dimensions constructor.
     *
     * @param Context           $context
     * @param HelperWS          $helperWs
     * @param ChronofreshOffers $chronofreshOffers
     * @param Data              $helperData
     * @param Contract          $helperContract
     * @param array             $data
     */
    public function __construct(
        Context $context,
        HelperWS $helperWs,
        ChronofreshOffers $chronofreshOffers,
        Data $helperData,
        Contract $helperContract,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperContract = $helperContract;
        $this->helperWs = $helperWs;
        $this->helperData = $helperData;
        $this->chronofreshOffers = $chronofreshOffers;
    }

    /**
     * Get contracts in html
     *
     * @param string $orderId
     *
     * @return string
     */
    public function getContractsHtml(string $orderId): string
    {
        $order = $this->helperData->getOrder($orderId);
        $shippingMethodCode = $this->helperData->getShippingMethodeCode($order->getShippingMethod());

        $contract = $this->helperContract->getContractByOrderId($orderId);

        // It is not possible to change contract for other delivery method than Chronofresh
        if ($shippingMethodCode !== Data::CHRONO_FRESH_CODE && $contract) {
            $contract = $this->helperContract->getContractByNumber($contract->getData('contract_account_number'));
            $html = '<span>' . $contract['name'] . '</span>';
            $html .= '<select id="chrono-contract" name="contract" style="display:none">';
            $html .= '<option value="' . $contract['contract_id'] . '" selected="selected">' . $contract['name'] .
                '</option>';
            $html .= '</select>';

            return $html;
        }

        $selectedContracts = null;
        $html = '<select id="chrono-contract" name="contract">';
        $contractShippingMethod = $this->helperContract->getCarrierContract($shippingMethodCode);
        $contracts = $this->helperContract->getConfigContracts();
        foreach ($contracts as $id => $contract) {
            $offer = null;
            if ($shippingMethodCode === Data::CHRONO_FRESH_CODE) {
                $offer = $this->helperData->getDefaultChronofreshOffer();
            }

            if (!$this->helperWs->shippingMethodEnabled($shippingMethodCode, (int)$id, $offer)) {
                continue;
            }

            if ($contract['number'] === $contractShippingMethod['number']) {
                $selectedContracts = (int)$id;
                $html .= '<option value="' . $id . '" selected="selected">' . $contract['name'] . '</option>';
            } else {
                $html .= '<option value="' . $id . '">' . $contract['name'] . '</option>';
            }
        }

        $html .= '</select>';

        if ($shippingMethodCode === Data::CHRONO_FRESH_CODE) {
            $html .= $this->renderOffersDropDown($selectedContracts, $shippingMethodCode);
        }

        return $html;
    }

    /**
     * Render offers dropdown
     *
     * @param int    $selectedContracts
     * @param string $shippingMethodCode
     *
     * @return string
     */
    private function renderOffersDropDown(int $selectedContracts, string $shippingMethodCode): string
    {
        $html = '<br/><br/>';
        $html .= '<select id="chrono-offers" name="offers">';

        $defaultOffer = $this->helperData->getDefaultChronofreshOffer();
        $offers = $this->chronofreshOffers->toOptionArray();
        foreach ($offers as $key => $offer) {
            $isEnable = false;
            if ($selectedContracts) {
                $isEnable = $this->helperWs->shippingMethodEnabled(
                    $shippingMethodCode,
                    $selectedContracts,
                    $key
                );
            }

            if ($isEnable) {
                $selected = $defaultOffer === $key ? 'selected' : '';
                $html .= '<option value="' . $key . '" ' . $selected . '>' . $offer . '</option>';
            }
        }

        $html .= '</select>';

        return $html;
    }
}
