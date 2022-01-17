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
 * Class ChronofreshOffers
 *
 * Chronopost\Chronorelais\Model\Config\Source
 */
class ChronofreshOffers implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'fresh'  => __('Chrono 13 Fresh'),
            'freeze' => __('Chrono 13 Freeze'),
            'sec'    => __('Chrono 13 Sec')
        ];
    }
}
