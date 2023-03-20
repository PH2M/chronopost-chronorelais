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

namespace Chronopost\Chronorelais\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as GridCollection;

/**
 * Class Collection
 *
 * @package Chronopost\Chronorelais\Model\ResourceModel\Order\Grid
 */
class Collection extends GridCollection
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Collection constructor.
     *
     * @param EntityFactory    $entityFactory
     * @param Logger           $logger
     * @param FetchStrategy    $fetchStrategy
     * @param EventManager     $eventManager
     * @param RequestInterface $request
     * @param string           $mainTable
     * @param string           $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        RequestInterface $request,
        $mainTable = 'sales_order_grid',
        $resourceModel = Order::class
    ) {
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFilterToMap('entity_id', 'main_table.entity_id');

        $this->getSelect()
            ->join(
                $this->getTable('sales_order'),
                'main_table.entity_id = ' . $this->getTable('sales_order') . '.entity_id ',
                [
                    $this->getTable('sales_order') . '.shipping_method',
                    $this->getTable('sales_order') . '.total_qty_ordered',
                    $this->getTable('sales_order') . '.force_saturday_option',
                    $this->getTable('sales_order') . '.force_saturday_option_generated'
                ]
            )
            ->joinLeft(
                $this->getTable('sales_shipment'),
                'main_table.entity_id = ' . $this->getTable('sales_shipment') . '.order_id',
                [new \Zend_Db_Expr('if(isNull(' . $this->getTable('sales_shipment') . '.increment_id) , "" , GROUP_CONCAT(DISTINCT ' . $this->getTable('sales_shipment') . '.increment_id SEPARATOR ", ")) as shipment_id')]
            )
            ->joinLeft(
                $this->getTable('sales_shipment_track'),
                'main_table.entity_id = ' . $this->getTable('sales_shipment_track') . '.order_id',
                [new \Zend_Db_Expr('if(isNull(' . $this->getTable('sales_shipment_track') . '.track_number) , "" , GROUP_CONCAT(DISTINCT ' . $this->getTable('sales_shipment_track') . '.track_number SEPARATOR ", ")) as track_number')]
            )
            ->joinLeft(
                $this->getTable('chronopost_order_export_status'),
                'main_table.entity_id = ' . $this->getTable('chronopost_order_export_status') . '.order_id',
                [new \Zend_Db_Expr("if(isNull(" . $this->getTable('chronopost_order_export_status') . ".livraison_le_samedi) , '' , " . $this->getTable('chronopost_order_export_status') . ".livraison_le_samedi) as force_saturday_option_export")]
            )
            ->where($this->getTable('sales_order') . '.shipping_method LIKE "chrono%"')
            ->where($this->getTable('sales_order') . ".state = 'processing' OR " . $this->getTable('sales_order') . ".state = 'complete'")
            ->group('main_table.entity_id');

        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('shipment_id', 'sales_shipment.increment_id');
        $this->addFilterToMap('increment_id', 'main_table.increment_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');

        return $this;
    }
}
