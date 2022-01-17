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

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment;
use Chronopost\Chronorelais\Model\Config\Source\Retour;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ReturnAction
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class ReturnAction extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Retour
     */
    protected $_retourSource;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Shipment
     */
    private $helperShipment;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * ReturnAction constructor.
     *
     * @param ContextInterface     $context
     * @param UiComponentFactory   $uiComponentFactory
     * @param UrlInterface         $urlBuilder
     * @param OrderFactory         $orderFactory
     * @param Retour               $retour
     * @param ScopeConfigInterface $scope
     * @param Shipment             $helperShipment
     * @param HelperData           $helperData
     * @param array                $components
     * @param array                $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        OrderFactory $orderFactory,
        Retour $retour,
        ScopeConfigInterface $scope,
        Shipment $helperShipment,
        HelperData $helperData,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_orderFactory = $orderFactory;
        $this->_retourSource = $retour;
        $this->_scopeConfig = $scope;
        $this->helperShipment = $helperShipment;
        $this->helperData = $helperData;
        $data['config']['label'] = $this->getLabelWithDropdown($data['config']['label']);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Get label with dropdown
     *
     * @param string $label
     *
     * @return string
     */
    protected function getLabelWithDropdown(string $label)
    {
        $defaultAddress = $this->_scopeConfig->getValue('chronorelais/retour/defaultadress');
        $select = "<br/><select id='label_return_address' style='font-size: 12px;' name='label_return_address'>";

        $options = $this->_retourSource->toOptionArray();
        foreach ($options as $value => $option) {
            $selected = ($defaultAddress && $value == $defaultAddress) ? ' selected="selected"' : '';
            $select .= "<option value='" . $value . "'" . $selected . ">" . $option . "</option>";
        }

        $select .= "</select><input type='hidden' id='label_return_address_value' " .
            "name='label_return_address_value' value='" . $defaultAddress . "'/>";

        return $label . $select;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = '';

                // Check if return is allowed for current shipping method
                $shippingMethod = $this->helperData->getShippingMethodeCode($item['shipping_method']);
                $shippingMethodsAllowed = HelperData::SHIPPING_METHODS_RETURN_ALLOWED;
                if (!in_array($shippingMethod, $shippingMethodsAllowed)) {
                    $item[$this->getData('name')] = __('Returns are not available for this shipping method');
                    continue;
                }

                // Check if return is authorized
                $order = $this->helperData->getOrder($item['entity_id']);
                $shippingCountryId = $order->getShippingAddress()->getCountryId();
                if (!$this->helperData->returnAuthorized($shippingCountryId, $shippingMethod)) {
                    $item[$this->getData('name')] = __(
                        'Return labels are not available for this country: %1',
                        $shippingCountryId
                    );
                    continue;
                }

                // If no shipment and tracking, no return possible
                if (!isset($item['shipment_id'], $item['track_number']) ||
                    empty($item['shipment_id']) || $item['shipment_id'] === '' ||
                    empty($item['track_number']) || $item['track_number'] === '') {
                    continue;
                }

                $viewUrlPath = $this->getData('config/viewUrlPath') ?: 'javascript:void(0);';
                $deleteUrlPath = $this->getData('config/deleteUrlPath') ?: '#';
                $viewGeneratedUrlPath = $this->getData('config/viewGeneratedUrlPath') ?: 'javascript:void(0);';

                $shipments = $order->getShipmentsCollection();
                foreach ($shipments as $shipment) {
                    $incrementId = $shipment->getIncrementId();
                    $shipmentReturns = $this->helperShipment->getReturnsForShipment($shipment->getId());
                    foreach ($shipmentReturns as $shipmentReturn) {
                        $ltNumber = trim($shipmentReturn->getLtNumber());

                        $urlGenerated = $this->urlBuilder->getUrl(
                            $viewGeneratedUrlPath,
                            [
                                'track_number' => $shipmentReturn->getReservation(),
                                'shipment_id'  => $incrementId
                            ]
                        );

                        $deleteUrl = $this->urlBuilder->getUrl(
                            $deleteUrlPath,
                            [
                                'order_id'     => (string)$item['entity_id'],
                                'track_number' => $ltNumber,
                                'shipment_id'  => $incrementId
                            ]
                        );

                        $confirmMsg = __('Are you sure you want to cancel this return label?');

                        $item[$this->getData('name')] .= '<a class="printlink" href="' . $urlGenerated . '">' .
                            $ltNumber . '</a>';
                        $item[$this->getData('name')] .= '<a onclick="return confirm(\'' . $confirmMsg . '\');"
                            class="printlink" href="' . $deleteUrl . '"> ' . __('(Cancel)') . '</a><br />';
                    }

                    $printLabel = __('Printing return labels');
                    if (count($shipmentReturns) >= 1) {
                        $printLabel = __('Print a new return label');
                    }

                    $defaultAddress = $this->_scopeConfig->getValue('chronorelais/retour/defaultadress');
                    $url = $this->urlBuilder->getUrl($viewUrlPath, ['shipment_increment_id' => $incrementId]);
                    if (count($shipments) === 1) {
                        $item[$this->getData('name')] .= '<a href="' . $url . '?recipient_address_type=' .
                            $defaultAddress . '" class="label_return_link">' . $printLabel . '</a><br />';
                    } else {
                        $item[$this->getData('name')] .= '<a href="' . $url . '?recipient_address_type=' .
                            $defaultAddress . '" class="label_return_link">' . $printLabel . ' ' . $incrementId
                            . '</a><br />';
                    }
                }
            }
        }

        return $dataSource;
    }
}
