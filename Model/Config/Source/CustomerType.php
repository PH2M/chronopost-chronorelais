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
 * Class CustomerType
 *
 * Chronopost\Chronorelais\Model\Config\Source
 */
class CustomerType implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '1' => __('I\'m a %1 customer', 'Chronopost'),
            '2' => __('I\'m a %1 customer', 'Chronofresh')
        ];
    }
}
