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
 * Class Minute
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class Minute implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $minute = [];
        for ($ite = 0; $ite <= 59; $ite++) {
            $minute_str = str_pad((string)$ite, 2, '0', STR_PAD_LEFT);
            $minute[] = ['value' => $minute_str, 'label' => $minute_str];
        }

        return $minute;
    }
}
