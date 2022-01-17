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
 * Class Chronopost
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class Chronopost extends AbstractChronopost
{
    const PRODUCT_CODE = '01';
    const PRODUCT_CODE_STR = '13H';
    const OPTION_BAL_ENABLE = true;
    const PRODUCT_CODE_BAL = '58';
    const PRODUCT_CODE_BAL_STR = '13H BAL';
    const DELIVER_ON_SATURDAY = true;
    const CHECK_CONTRACT = true;

    /**
     * @var string
     */
    protected $_code = 'chronopost';
}
