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
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PrintAction
 *
 * @package Chronopost\Chronorelais\Ui\Component\Listing\Column
 */
class PrintAction extends Column
{
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
     * @var Data
     */
    protected $helper;

    /**
     * @var Shipment
     */
    private $helperShipment;

    /**
     * PrintAction constructor.
     *
     * @param ContextInterface      $context
     * @param UiComponentFactory    $uiComponentFactory
     * @param UrlInterface          $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param FormKey               $formKey
     * @param Data                  $helper
     * @param Shipment              $helperShipment
     * @param array                 $components
     * @param array                 $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        FormKey $formKey,
        Data $helper,
        Shipment $helperShipment,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->formKey = $formKey;
        $this->helper = $helper;
        $this->helperShipment = $helperShipment;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['shipment_id'])) {
                    $item[$this->getData('name')] = '';

                    // If no shipment, no tracking possible
                    if (!isset($item['shipment_id']) || empty($item['shipment_id']) || $item['shipment_id'] === '') {
                        continue;
                    }

                    $order = $this->helper->getOrder($item['entity_id']);
                    $shipments = $order->getShipmentsCollection();
                    foreach ($shipments as $shipment) {
                        $shipmentTracks = $this->helperShipment->getTrackingForShipment($shipment->getId());
                        if (count($shipmentTracks)) {
                            foreach ($shipmentTracks as $shipmentTrack) {
                                $ltNumber = trim($shipmentTrack->getLtNumber());
                                $viewGeneratedUrlPath = $this->getData('config/viewUrlPath') ?: 'javascript:void(0);';

                                // Fix bug version 1.2
                                $trackNumber = $ltNumber;
                                if ($shipmentTrack->getReservation() !== '2147483647') {
                                    $trackNumber = $shipmentTrack->getReservation();
                                }
                                // End fix

                                $urlGenerated = $this->urlBuilder->getUrl(
                                    $viewGeneratedUrlPath,
                                    [
                                        'track_number' => $trackNumber,
                                        'shipment_id'  => $shipment->getIncrementId()
                                    ]
                                );

                                $item[$this->getData('name')] .= '<a class="printlink" href="' . $urlGenerated . '">' .
                                    $ltNumber . '</a><br/>';
                            }
                        }
                    }
                }
            }
        }

        return $dataSource;
    }
}
