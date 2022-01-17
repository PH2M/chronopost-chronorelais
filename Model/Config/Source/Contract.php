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

use Chronopost\Chronorelais\Helper\Contract as HelperContract;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Contract
 *
 * @package Chronopost\Chronorelais\Model\Config\Source
 */
class Contract implements ArrayInterface
{

    /**
     * @var HelperContract
     */
    protected $helper;

    /**
     * Contract constructor.
     *
     * @param HelperContract $helper
     */
    public function __construct(
        HelperContract $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $contracts = $this->helper->getConfigContracts();

        $toReturn = [];
        foreach ($contracts as $number => $contract) {
            $toReturn[] = [
                'value' => $number,
                'label' => $contract['name']
            ];
        }

        return $toReturn;
    }
}
