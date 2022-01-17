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

namespace Chronopost\Chronorelais\Model\ResourceModel\Order\Grid\Bordereau;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;

/**
 * Class Collection
 *
 * @package Chronopost\Chronorelais\Model\ResourceModel\Order\Grid\Bordereau
 */
class Collection extends \Chronopost\Chronorelais\Model\ResourceModel\Order\Grid\Collection
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
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $request,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * Init selection
     *
     * @return $this|Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $action = $this->request->getActionName();
        if (!preg_match('/impression_[a-zA-Z]*Mass|bordereau_printBordereau/', $action)) {
            $this->addFilterToMap('entity_id', 'main_table.entity_id');
            $this->getSelect()
                ->where($this->getTable('sales_order') . ".status = 'processing' OR " .
                    $this->getTable('sales_order') . ".status = 'complete'");
        }

        return $this;
    }
}
