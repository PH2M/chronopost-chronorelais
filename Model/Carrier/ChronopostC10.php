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
 * Class ChronopostC10
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class ChronopostC10 extends AbstractChronopost
{
    const PRODUCT_CODE = '02';
    const PRODUCT_CODE_STR = '10H';
    const CHECK_CONTRACT = true;
    const DELIVER_ON_SATURDAY = true;

    /**
     * @var string
     */
    protected $_code = 'chronopostc10';
}
