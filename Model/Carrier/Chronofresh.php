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
 * Class Chronofresh
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class Chronofresh extends AbstractChronopost
{
    const PRODUCT_CODE = 'FRESH';
    const PRODUCT_CODE_SEC = '5T';
    const PRODUCT_CODE_SEC_OLD = '1T';
    const PRODUCT_CODE_FRESH = '2R';
    const PRODUCT_CODE_FREEZE = '2S';
    const PRODUCT_CODE_STR = 'Fresh';
    const CHECK_CONTRACT = true;

    /**
     * @var string
     */
    protected $_code = 'chronofresh';
}
