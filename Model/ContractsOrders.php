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

namespace Chronopost\Chronorelais\Model;

use \Magento\Framework\Model\AbstractModel;
use Chronopost\Chronorelais\Model\ResourceModel\ContractsOrders as ResContractsOrders;

/**
 * Class ContractsOrders
 *
 * @package Chronopost\Chronorelais\Model
 */
class ContractsOrders extends AbstractModel
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'chronopost';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'contracts_orders';

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResContractsOrders::class);
    }
}
