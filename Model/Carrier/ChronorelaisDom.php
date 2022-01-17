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
 * Class ChronorelaisDom
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class ChronorelaisDom extends AbstractChronopost
{
    const PRODUCT_CODE = '4P';
    const CHECK_CONTRACT = true;
    const CHECK_RELAI_WS = true;

    /**
     * @var string
     */
    protected $_code = 'chronorelaisdom';
}
