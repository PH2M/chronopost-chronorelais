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
 * Class Civility
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class Civility implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'E' => __('Mrs.'),
            'L' => __('Miss'),
            'M' => __('Mr')
        ];
    }
}
