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
 * Class SamedayTime
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class SamedayTime implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $time = [];
        for ($ite = 7; $ite <= 15; $ite++) {
            $timeStr = str_pad((string)$ite, 2, '0', STR_PAD_LEFT) . ':00';
            $time[$timeStr] = $timeStr;

            if ($ite < 15) {
                $timeStr = $ite . ':30';
                $time[$timeStr] = $timeStr;
            }
        }

        return $time;
    }
}
