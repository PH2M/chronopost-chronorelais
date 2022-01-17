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
 * Class Chronorelais
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class Chronorelais extends AbstractChronopost
{
    const PRODUCT_CODE = '86';
    const CARRIER_CODE = 'chronorelais';
    const CHECK_RELAI_WS = true;
    const DELIVER_ON_SATURDAY = true;
    const CHECK_CONTRACT = true;

    /**
     * @var string
     */
    protected $_code = 'chronorelais';
}
