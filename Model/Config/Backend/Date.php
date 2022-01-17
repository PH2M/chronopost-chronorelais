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

namespace Chronopost\Chronorelais\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Class Date
 *
 * @package Chronopost\Chronorelais\Model\Config\Backend
 */
class Date extends Value
{
    public function beforeSave(){
        $range = $this->getValue();
        $this->setValue($range[0].':'.$range[1].':'.$range[2]);

        return $this;
    }
}
