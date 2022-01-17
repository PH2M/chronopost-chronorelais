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

namespace Chronopost\Chronorelais\Model\Carrier;

/**
 * Class Chronoexpress
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class Chronoexpress extends AbstractChronopost
{
    const PRODUCT_CODE = '17';
    const CHECK_CONTRACT = true;

    /**
     * @var string
     */
    protected $_code = 'chronoexpress';
}
