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

namespace Chronopost\Chronorelais\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class saveOrderRdvInfo
 *
 * @package Chronopost\Chronorelais\Observer
 */
class saveOrderRdvInfo implements ObserverInterface
{
    /**
     * Execute observer
     *
     * @param EventObserver $observer
     *
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $order->setData('chronopostsrdv_creneaux_info', $quote->getData('chronopostsrdv_creneaux_info'));

        return $this;
    }
}
