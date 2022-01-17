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
 * Class Hour
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class Hour implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $hour = [];
        for ($ite = 0; $ite <= 23; $ite++) {
            $hourStr = str_pad((string)$ite, 2, '0', STR_PAD_LEFT);
            $hour[] = ['value' => $hourStr, 'label' => $hourStr];
        }

        return $hour;
    }
}
