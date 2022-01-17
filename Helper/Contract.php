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

namespace Chronopost\Chronorelais\Helper;

use Chronopost\Chronorelais\Model\ContractsOrders;
use Chronopost\Chronorelais\Model\ContractsOrdersFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Chronopost\Chronorelais\Helper\Data as HelperData;

/**
 * Class Contract
 *
 * @package Chronopost\Chronorelais\Helper
 */
class Contract extends AbstractHelper
{

    /**
     * @var string|null
     */
    private $configContracts = null;

    /**
     * @var array
     */
    private $contractByOrderId = [];

    /**
     * @var ContractsOrdersFactory
     */
    private $contractsFactory;

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var array
     */
    private $carrierContract = [];

    /**
     * Data constructor.
     *
     * @param Context                $context
     * @param ContractsOrdersFactory $contractsFactory
     * @param HelperData             $helperData
     */
    public function __construct(
        Context $context,
        ContractsOrdersFactory $contractsFactory,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->contractsFactory = $contractsFactory;
        $this->helperData = $helperData;
    }

    /**
     * Get specific contract
     *
     * @param int $id
     *
     * @return array
     */
    public function getSpecificContract(int $id)
    {
        $contracts = $this->getConfigContracts();

        return $contracts[$id] ?? [];
    }

    /**
     * Get config contracts
     *
     * @return array
     */
    public function getConfigContracts()
    {
        if ($this->configContracts === null) {
            $config = $this->helperData->getConfig('chronorelais/contracts/contracts');
            $this->configContracts = json_decode($config, true);
        }

        return $this->configContracts;
    }

    /**
     * Get contract by number
     *
     * @param string $number
     *
     * @return array
     */
    public function getContractByNumber(string $number)
    {
        $contracts = $this->getConfigContracts();
        foreach ($contracts as $id => $contract) {
            $contract['contract_id'] = $id;
            if ($contract['number'] === $number) {
                return $contract;
            }
        }

        return [];
    }

    /**
     * Get carrier contract by code
     *
     * @param string $code
     *
     * @return mixed
     */
    public function getCarrierContract(string $code)
    {
        if (!isset($this->carrierContract[$code])) {
            $contracts = (array)$this->getConfigContracts();
            $numContract = $this->helperData->getConfig('carriers/' . $code . '/contracts');

            foreach ($contracts as $key => $contract) {
                $contracts[$key]['contract_id'] = $key;
            }

            if (isset($contracts[$numContract])) {
                $this->carrierContract[$code] = $contracts[$numContract];
            } else {
                $this->carrierContract[$code] = null;
            }
        }

        return $this->carrierContract[$code];
    }

    /**
     * Get contract by order
     *
     * @param string $orderId
     *
     * @return ContractsOrders|null
     */
    public function getContractByOrderId(string $orderId)
    {
        if (!isset($this->contractByOrderId[$orderId])) {
            $this->contractByOrderId[$orderId] = null;
            $collection = $this->contractsFactory->create()->getCollection()->addFieldToFilter('order_id', $orderId);
            if (count($collection)) {
                $this->contractByOrderId[$orderId] = $collection->getFirstItem();
            }
        }

        return $this->contractByOrderId[$orderId];
    }
}
