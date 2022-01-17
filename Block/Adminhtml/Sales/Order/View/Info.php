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

namespace Chronopost\Chronorelais\Block\Adminhtml\Sales\Order\View;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin;

/**
 * Class Info
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\Sales\Order\View
 */
class Info extends AbstractOrder
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * Info constructor.
     *
     * @param Context $context
     * @param Registry             $registry
     * @param Admin             $adminHelper
     * @param HelperData                              $helperData
     * @param array                                   $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);
        $this->helperData = $helperData;
    }

    /**
     * Check if order has option BAL
     *
     * @return bool
     * @throws LocalizedException
     */
    public function hasOptionBAL()
    {
        return $this->helperData->hasOptionBAL($this->getOrder());
    }

    /**
     * Get ad valorem for order
     *
     * @return int
     * @throws LocalizedException
     */
    public function getOrderAdValorem()
    {
        $adValoremAmount = $this->helperData->getOrderAdValorem($this->getOrder());

        return $adValoremAmount ? $this->getOrder()->formatPrice($adValoremAmount) : 0;
    }
}
