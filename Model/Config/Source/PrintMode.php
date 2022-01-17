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

namespace Chronopost\Chronorelais\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PrintMode
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class PrintMode implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'PDF' => __('Print PDF laser with proof'),
            'SPD' => __('Print PDF laser without proof'),
            'THE' => __('Print PDF thermal')
        ];
    }
}
