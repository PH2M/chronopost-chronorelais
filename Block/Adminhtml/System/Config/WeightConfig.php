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

namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class WeightConfig
 *
 * @package Chronopost\Chronorelais\Block\Adminhtml\System\Config
 */
class WeightConfig extends Template
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * WeightConfig constructor.
     *
     * @param Data    $helper
     * @param Context $context
     * @param array   $data
     */
    public function __construct(Data $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * Get weight coefficient
     */
    public function getWeightCoef()
    {
        return $this->helper->getWeightCoef();
    }
}
