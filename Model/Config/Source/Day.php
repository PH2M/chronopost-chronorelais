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
 * Class Day
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class Day implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'monday', 'label' => __('Monday')],
            ['value' => 'tuesday', 'label' => __('Tuesday')],
            ['value' => 'wednesday', 'label' => __('Wednesday')],
            ['value' => 'thursday', 'label' => __('Thursday')],
            ['value' => 'friday', 'label' => __('Friday')],
            ['value' => 'saturday', 'label' => __('Saturday')],
            ['value' => 'sunday', 'label' => __('Sunday')]
        ];
    }
}
