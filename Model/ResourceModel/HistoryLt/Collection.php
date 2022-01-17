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

namespace Chronopost\Chronorelais\Model\ResourceModel\HistoryLt;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Chronopost\Chronorelais\Model\HistoryLt;

/**
 * Class Collection
 *
 * @package Chronopost\Chronorelais\Model\ResourceModel\HistoryLt
 */
class Collection extends AbstractCollection
{

    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(HistoryLt::class, \Chronopost\Chronorelais\Model\ResourceModel\HistoryLt::class);
    }
}
