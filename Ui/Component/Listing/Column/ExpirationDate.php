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

use Chronopost\Chronorelais\Helper\Data;
use Chronopost\Chronorelais\Helper\Shipment;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ExpirationDate
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class ExpirationDate extends Column
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Shipment
     */
    private $helperShipment;

    /**
     * ExpirationDate constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Data               $helper
     * @param Shipment           $helperShipment
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Data $helper,
        Shipment $helperShipment,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->helper = $helper;
        $this->helperShipment = $helperShipment;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws \Exception
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $render = '';
                $entityId = $item['entity_id'];
                $order = $this->helper->getOrder($entityId);
                if ($order) {
                    $shippingMethod = $this->helper->getShippingMethodeCode($order->getShippingMethod());
                    if ($shippingMethod === Data::CHRONO_FRESH_CODE) {
                        $class = 'expiration-date-' . $item['entity_id'];
                        $expirationDate = $this->helper->getDefaultExpirationDate();
                        $shipmentIds = explode(',', $item['shipment_id']);
                        if (count($shipmentIds) > 1) {
                            $item['shipment_id'] = $shipmentIds[0];
                        }

                        $shipmentId = $this->helperShipment->getShipmentIdFromIncrementId($item['shipment_id']);
                        if ($shipmentId) {
                            $history = $this->helperShipment->getHistoryLt('shipment_id', $shipmentId);
                            if ($history->getId()) {
                                $expirationDateHistory = $history->getExpirationDate();
                                if ($expirationDateHistory) {
                                    $expirationDate = $this->helper->getFormattedExpirationDate(
                                        $expirationDateHistory,
                                        'd-m-Y'
                                    );
                                }
                            }
                        }

                        $render = '<div class="expiration-date-container"><input value="' . $expirationDate . '" type="text" class="input-text expiration-date ' . $class . '"
                        name="expiration_date" data-entityid="' . $item['entity_id'] . '" readonly/></div>';

                        $render .= '<script>
                                require(["jquery", "mage/calendar"],
                                    function ($) {
                                        $(".' . $class . '").calendar({
                                            changeYear: true,
                                            changeMonth: true,
                                            yearRange: "' . date('Y') . ':2050",
                                            buttonText: "' . __('Select Date') . '",
                                            dateFormat: "dd-mm-yy"
                                        });
                                    });
                            </script>';
                    } else {
                        $render = __('Option not available for this delivery method');
                    }
                }

                $item[$this->getData('name')] = $render;
            }
        }

        return $dataSource;
    }
}
