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

use Magento\Backend\Block\Template\Context;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Data;
use Magento\Shipping\Block\Adminhtml\Create\Items;
use Magento\Shipping\Model\CarrierFactory;

/**
 * Class Ajax
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment
 */
class Ajax extends Items
{

    /**
     * Ajax constructor.
     *
     * @param Context                     $context
     * @param StockRegistryInterface      $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param Registry                    $registry
     * @param Data                        $salesData
     * @param CarrierFactory              $carrierFactory
     * @param array                       $data
     */
    public function __construct(
        Context $context,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        Registry $registry,
        Data $salesData,
        CarrierFactory $carrierFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $stockRegistry,
            $stockConfiguration,
            $registry,
            $salesData,
            $carrierFactory,
            $data
        );
    }

    /**
     * Get dimension url
     *
     * @return string
     */
    public function getDimensionsUrl()
    {
        return $this->getUrl('chronorelais/sales_shipment/dimensions');
    }
}
