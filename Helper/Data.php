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

use Chronopost\Chronorelais\Model\ResourceModel\OrderExportStatus\CollectionFactory as OrderExportStatusFactory;
use DateTime;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * @package Chronopost\Chronorelais\Helper
 */
class Data extends AbstractHelper
{
    const MODULE_NAME = 'Chronopost_Chronorelais';

    const CHRONO_EXPRESS_CODE = 'chronoexpress';
    const CHRONO_FRESH_CODE = 'chronofresh';
    const CHRONO_CODE = 'chronopost';
    const CHRONO_10_CODE = 'chronopostc10';
    const CHRONO_18_CODE = 'chronopostc18';
    const CHRONO_CLASSIC_CODE = 'chronocclassic';
    const CHRONO_SAMEDAY_CODE = 'chronosameday';
    const CHRONO_SRDV_CODE = 'chronopostsrdv';
    const CHRONO_RELAIS_CODE = 'chronorelais';
    const CHRONO_RELAIS_DOM_CODE = 'chronorelaisdom';
    const CHRONO_RELAIS_EUROPE_CODE = 'chronorelaiseur';

    const CHRONO_POST = '01';
    const CHRONO_POST_STR = '13H';
    const CHRONO_POST_BAL = '58';
    const CHRONO_POST_BAL_STR = '13H BAL';

    const CHRONORELAY = '86';
    const CHRONORELAY_STR = 'PR';

    const CHRONO_EXPRESS = '17';
    const CHRONO_EXPRESS_STR = 'EI';

    const CHRONOPOST_C10 = '02';
    const CHRONOPOST_C10_STR = '10H';

    const CHRONOPOST_C18 = '16';
    const CHRONOPOST_C18_STR = '18H';
    const CHRONOPOST_C18_BAL = '2M';
    const CHRONOPOST_C18_BAL_STR = '18H BAL';

    const CHRONOPOST_CCLASSIC = '44';
    const CHRONOPOST_CCLASSIC_STR = 'CClassic';

    const CHRONO_RELAY_EUROPE = '49';
    const CHRONO_RELAY_EUROPE_STR = 'PRU';

    const CHRONO_RELAY_DOM = '4P';
    const CHRONO_RELAY_DOM_STR = 'PRDOM';

    const CHRONOPOST_SMD = '4I';
    const CHRONOPOST_SMD_STR = 'SMD';

    const CHRONOPOST_SRDV = '2O'; // Uppercase 'O' and not zero
    const CHRONOPOST_SRDV_STR = 'SRDV';

    const CHRONOFRESH = ['sec_old' => '1T', 'sec' => '5T', 'freeze' => '2S', 'fresh' => '2R'];
    const CHRONOFRESH_STR = ['sec_old' => '1T', 'sec' => '5T', 'freeze' => '2S', 'fresh' => '2R'];

    const CHRONOPOST_REVERSE_R = '4R'; // for Chronopost Reverse 9
    const CHRONOPOST_REVERSE_S = '4S'; // for Chronopost Reverse 10
    const CHRONOPOST_REVERSE_T = '4T'; // for Chronopost Reverse 13
    const CHRONOPOST_REVERSE_U = '4U'; // for Chronopost Reverse 18
    const CHRONOPOST_REVERSE_DEFAULT = '01'; // Default value
    const CHRONOPOST_REVERSE_RELAY_EUROPE = '3T'; // for Chronopost Reverse Relay Europe

    const CHRONOPOST_REVERSE_R_SERVICE = '885'; // for Chronopost Reverse 9
    const CHRONOPOST_REVERSE_S_SERVICE = '180'; // for Chronopost Reverse 10
    const CHRONOPOST_REVERSE_T_SERVICE = '898'; // for Chronopost Reverse 13
    const CHRONOPOST_REVERSE_U_SERVICE = '835'; // for Chronopost Reverse 18
    const CHRONOPOST_REVERSE_DEFAULT_SERVICE = '226'; // Default
    const CHRONOPOST_REVERSE_RELAY_EUROPE_SERVICE = '332';

    const COUNTRY_ALLOWED_RETURN = [
        'FR',
        'DE',
        'LU',
        'BE',
        'NL',
        'PT',
        'CH',
        'GB',
        'EE',
        'LT',
        'CH',
        'AT',
        'LV'
    ];

    const SHIPPING_METHODS_RETURN_ALLOWED = [
        self::CHRONO_RELAIS_EUROPE_CODE,
        self::CHRONO_RELAIS_CODE,
        self::CHRONO_CODE,
        self::CHRONO_10_CODE,
        self::CHRONO_18_CODE,
        self::CHRONO_SRDV_CODE,
        self::CHRONO_CLASSIC_CODE,
        self::CHRONO_EXPRESS_CODE,
        self::CHRONO_SAMEDAY_CODE
    ];

    const SHIPPING_METHODS_SATURDAY_ALLOWED = [
        self::CHRONO_CODE,
        self::CHRONO_10_CODE,
        self::CHRONO_18_CODE,
        self::CHRONO_SAMEDAY_CODE
    ];

    const SHIPPING_METHODS_RETURN_INTERNATIONAL = [
        self::CHRONO_EXPRESS_CODE,
        self::CHRONO_RELAIS_EUROPE_CODE,
        self::CHRONO_RELAIS_DOM_CODE,
        self::CHRONO_CLASSIC_CODE,
        self::CHRONO_SRDV_CODE
    ];

    /**
     * @var OrderExportStatusFactory
     */
    protected $orderExportStatusCollectionFactory;

    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Reader
     */
    protected $moduleReader;

    /**
     * @var array
     */
    private $saturdayShippingDays;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $order = [];

    /**
     * @var int
     */
    private $customerType = 0;

    /**
     * @var float|null
     */
    private $saturdayShippingAmount = null;

    /**
     * @var bool|null
     */
    private $optionBalIsEnabled = null;

    /**
     * @var bool|null
     */
    private $displaySaturdayOption = null;

    /**
     * @var array
     */
    private $chronoWeightUnit = [];

    /**
     * @var string|null
     */
    private $defaultExpirationDate = null;

    /**
     * @var array
     */
    private $shippingMethodIsEnabled = [];

    /**
     * @var array
     */
    private $configPerStore = [];

    /**
     * @var string|null
     */
    private $defaultChronoFreshOffer;

    /**
     * Data constructor.
     *
     * @param Context                  $context
     * @param OrderExportStatusFactory $collectionFactory
     * @param CarrierFactory           $carrierFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Reader                   $moduleReader
     * @param StoreManagerInterface    $storeManager
     */
    public function __construct(
        Context $context,
        OrderExportStatusFactory $collectionFactory,
        CarrierFactory $carrierFactory,
        OrderRepositoryInterface $orderRepository,
        Reader $moduleReader,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->orderExportStatusCollectionFactory = $collectionFactory;
        $this->carrierFactory = $carrierFactory;
        $this->orderRepository = $orderRepository;
        $this->moduleReader = $moduleReader;
        $this->storeManager = $storeManager;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param null   $storeCode
     *
     * @return null|string
     */
    public function getConfig(string $path, $storeCode = null)
    {
        if (!isset($this->configPerStore[$path][$storeCode])) {
            $valuePerStore = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
            if ($valuePerStore === null) {
                $valuePerStore = $this->scopeConfig->getValue($path);
            }

            $this->configPerStore[$path][$storeCode] = $valuePerStore;
        }

        return $this->configPerStore[$path][$storeCode];
    }

    /**
     * return true if method is chrono
     *
     * @param string $shippingMethod
     *
     * @return bool
     */
    public function isChronoMethod(string $shippingMethod): bool
    {
        $carrier = $this->carrierFactory->get($shippingMethod);

        return $carrier ? $carrier->getIsChronoMethod() : false;
    }

    /**
     * Check if sending day
     *
     * @return bool
     * @throws \Exception
     */
    public function isSendingDay(): bool
    {
        $shippingDays = $this->getSaturdayShippingDays();
        $currentDate = $this->getCurrentTimeByZone('Europe/Paris', 'Y-m-d H:i:s');

        $startTimestamp = strtotime($shippingDays['startday'] . ' this week ' . $shippingDays['starttime']);
        $endTimestamp = strtotime($shippingDays['endday'] . ' this week ' . $shippingDays['endtime']);
        $currentTimestamp = strtotime($currentDate);

        $sendingDay = false;
        if ($currentTimestamp >= $startTimestamp && $currentTimestamp <= $endTimestamp) {
            $sendingDay = true;
        }

        return $sendingDay;
    }

    /**
     * Get saturday shipping days
     *
     * @return array
     */
    public function getSaturdayShippingDays(): array
    {
        $starday = explode(':', $this->getConfig('chronorelais/saturday/startday'));
        $endday = explode(':', $this->getConfig('chronorelais/saturday/endday'));

        $saturdayDays = [];
        $saturdayDays['startday'] = (count($starday) === 3 && isset($starday[0])) ?
            $starday[0] : $this->saturdayShippingDays['startday'];
        $saturdayDays['starttime'] = (count($starday) === 3 && isset($starday[1])) ?
            $starday[1] . ':' . $starday[2] . ':00' : $this->saturdayShippingDays['starttime'];
        $saturdayDays['endday'] = (count($endday) === 3 && isset($endday[0])) ?
            $endday[0] : $this->saturdayShippingDays['endday'];
        $saturdayDays['endtime'] = (count($endday) === 3 && isset($endday[1])) ?
            $endday[1] . ':' . $endday[2] . ':00' : $this->saturdayShippingDays['endtime'];

        return $saturdayDays;
    }

    /**
     * Get current datetime by zone
     *
     * @param string $timezone
     * @param string $format
     *
     * @return string
     * @throws \Exception
     */
    public function getCurrentTimeByZone($timezone = 'Europe/Paris', $format = 'l H:i'): string
    {
        $currentDate = new DateTime('NOW', new \DateTimeZone($timezone));

        return $currentDate->format($format);
    }

    /**
     * Get status shipping
     *
     * @param string $orderId
     *
     * @return string|false
     */
    public function getShippingSaturdayStatus($orderId)
    {
        $collection = $this->orderExportStatusCollectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId)->addFieldToSelect('livraison_le_samedi');
        $status = $collection->getFirstItem();

        return $status->getData('livraison_le_samedi');
    }

    /**
     * Get return product codes allowed
     *
     * @param array $productCodes
     *
     * @return array
     */
    public function getReturnProductCodesAllowed(array $productCodes): array
    {
        $possibleReturnProductCode = [
            self::CHRONOPOST_REVERSE_R,
            self::CHRONOPOST_REVERSE_S,
            self::CHRONOPOST_REVERSE_T,
            self::CHRONOPOST_REVERSE_U,
            self::CHRONOPOST_REVERSE_RELAY_EUROPE
        ];

        $returnProductCode = [];
        foreach ($productCodes as $code) {
            if (in_array($code, $possibleReturnProductCode)) {
                $returnProductCode[] = $code;
            }

            if ($code === self::CHRONO_EXPRESS) {
                $returnProductCode[] = self::CHRONOPOST_REVERSE_RELAY_EUROPE;
            }
        }

        return (sizeof($returnProductCode) > 0) ? $returnProductCode : [self::CHRONOPOST_REVERSE_DEFAULT];
    }

    /**
     * Get return service code
     *
     * @param string $code
     *
     * @return string
     */
    public function getReturnServiceCode(string $code): string
    {
        switch ($code) {
            case self::CHRONOPOST_REVERSE_R:
                return self::CHRONOPOST_REVERSE_R_SERVICE;
            case self::CHRONOPOST_REVERSE_S:
                return self::CHRONOPOST_REVERSE_S_SERVICE;
            case self::CHRONOPOST_REVERSE_T:
                return self::CHRONOPOST_REVERSE_T_SERVICE;
            case self::CHRONOPOST_REVERSE_U:
                return self::CHRONOPOST_REVERSE_U_SERVICE;
            case self::CHRONOPOST_REVERSE_RELAY_EUROPE:
                return self::CHRONOPOST_REVERSE_RELAY_EUROPE_SERVICE;
            default:
                return self::CHRONOPOST_REVERSE_DEFAULT_SERVICE;
        }
    }

    /**
     * Get matrix return code
     *
     * @return array
     */
    public function getMatrixReturnCode(): array
    {
        return [
            self::CHRONOPOST_REVERSE_R            => [
                [self::CHRONOPOST_REVERSE_R],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_U]
            ],
            self::CHRONOPOST_REVERSE_S            => [
                [self::CHRONOPOST_REVERSE_S],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_S],
                [self::CHRONOPOST_REVERSE_S, self::CHRONOPOST_REVERSE_U],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_S, self::CHRONOPOST_REVERSE_U]
            ],
            self::CHRONOPOST_REVERSE_U            => [
                [self::CHRONOPOST_REVERSE_U]
            ],
            self::CHRONOPOST_REVERSE_RELAY_EUROPE => [
                [self::CHRONOPOST_REVERSE_RELAY_EUROPE]
            ],
            self::CHRONOPOST_REVERSE_T            => [
                [self::CHRONOPOST_REVERSE_T],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_T],
                [self::CHRONOPOST_REVERSE_S, self::CHRONOPOST_REVERSE_T],
                [self::CHRONOPOST_REVERSE_T, self::CHRONOPOST_REVERSE_U],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_S, self::CHRONOPOST_REVERSE_T],
                [self::CHRONOPOST_REVERSE_R, self::CHRONOPOST_REVERSE_T, self::CHRONOPOST_REVERSE_U],
                [self::CHRONOPOST_REVERSE_S, self::CHRONOPOST_REVERSE_T, self::CHRONOPOST_REVERSE_U],
                [
                    self::CHRONOPOST_REVERSE_R,
                    self::CHRONOPOST_REVERSE_S,
                    self::CHRONOPOST_REVERSE_T,
                    self::CHRONOPOST_REVERSE_U
                ]
            ],
            self::CHRONOPOST_REVERSE_DEFAULT      => [
                [self::CHRONOPOST_REVERSE_DEFAULT]
            ]
        ];
    }

    /**
     * Check if has option BAL
     *
     * @param Order $order
     *
     * @return bool
     */
    public function hasOptionBAL(Order $order): bool
    {
        $shippingMethod = explode('_', $order->getShippingMethod());
        if (isset($shippingMethod[1])) {
            $carrier = $this->carrierFactory->get($shippingMethod[1]);

            return $carrier && $carrier->getIsChronoMethod() ? $carrier->optionBalEnable() : false;
        }

        return false;
    }

    /**
     * Get order
     *
     * @param string $orderId
     *
     * @return OrderInterface
     */
    public function getOrder(string $orderId)
    {
        if (!isset($this->order[$orderId])) {
            $this->order[$orderId] = $this->orderRepository->get($orderId);
        }

        return $this->order[$orderId];
    }

    /**
     * Get ad valorem insurance by order id
     *
     * @param string $orderId
     *
     * @return int|float
     */
    public function getOrderAdValoremById(string $orderId)
    {
        $order = $this->getOrder($orderId);
        if ($order->getId()) {
            return $this->getOrderAdValorem($order);
        }

        return 0;
    }

    /**
     * Get ad valorem order
     *
     * @param Order|OrderRepositoryInterface $order
     *
     * @return float|int
     */
    public function getOrderAdValorem($order)
    {
        $totalAdValorem = 0;

        if ($this->getConfig('chronorelais/assurance/enabled')) {
            $minAmount = $this->getConfig('chronorelais/assurance/amount');
            $maxAmount = $this->getMaxAdValoremAmount();

            $items = $order->getAllItems();
            foreach ($items as $item) {
                if ($item->getParentItemId() === null) {
                    $totalAdValorem += $item->getPrice() * $item->getQtyOrdered();
                }
            }

            $totalAdValorem = min($totalAdValorem, $maxAmount);
            if ($totalAdValorem < $minAmount) {
                $totalAdValorem = 0;
            }
        }

        return $totalAdValorem;
    }

    /**
     * Get max ad valorem amount
     *
     * @return int
     */
    public function getMaxAdValoremAmount(): int
    {
        return 20000;
    }

    /**
     * Get weight of specific order
     *
     * @param string $orderId
     * @param bool   $fromGrid
     *
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getWeightOfOrder(string $orderId, bool $fromGrid = false)
    {
        $order = $this->getOrder($orderId);

        $weightCoef = 1;
        if ($fromGrid === false) {
            $weightCoef = $this->getWeightCoef();
        }

        $totalWeight = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $weightUnit = $this->getWeightUnit($order->getId(), $order->getStoreId());
            if ($weightUnit === 'kgs') {
                $totalWeight += $item->getWeight() * $weightCoef * $item->getQtyOrdered();
            } elseif ($weightUnit === 'lbs') {
                $totalWeight += $item->getWeight() * 0.453592 * $weightCoef * $item->getQtyOrdered();
            } else {
                $totalWeight += $item->getWeight() * $item->getQtyOrdered();
            }
        }

        return $totalWeight;
    }

    /**
     * Get chronopost product code by code
     *
     * @param string      $code
     * @param bool        $withBal
     * @param string|null $offer
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getChronoProductCode(string $code, bool $withBal = false, $offer = null): string
    {
        $code = strtolower($code);

        switch ($code) {
            case self::CHRONO_FRESH_CODE:
                if ($offer) {
                    $productCode = self::CHRONOFRESH[$offer];
                } else {
                    $productCode = self::CHRONOFRESH[$this->getDefaultChronofreshOffer()];
                }

                break;
            case self::CHRONO_RELAIS_CODE:
                $productCode = self::CHRONORELAY;
                break;
            case self::CHRONO_EXPRESS_CODE:
                $productCode = self::CHRONO_EXPRESS;
                break;
            case self::CHRONO_10_CODE:
                $productCode = self::CHRONOPOST_C10;
                break;
            case self::CHRONO_18_CODE:
                if ($withBal === true && $this->getConfigOptionBAL()) {
                    $productCode = self::CHRONOPOST_C18_BAL;
                } else {
                    $productCode = self::CHRONOPOST_C18;
                }
                break;
            case self::CHRONO_CLASSIC_CODE:
                $productCode = self::CHRONOPOST_CCLASSIC;
                break;
            case self::CHRONO_RELAIS_EUROPE_CODE:
                $productCode = self::CHRONO_RELAY_EUROPE;
                break;
            case self::CHRONO_RELAIS_DOM_CODE:
                $productCode = self::CHRONO_RELAY_DOM;
                break;
            case self::CHRONO_SAMEDAY_CODE:
                $productCode = self::CHRONOPOST_SMD;
                break;
            case self::CHRONO_SRDV_CODE:
                $productCode = self::CHRONOPOST_SRDV;
                break;
            default:
                if ($withBal === true && $this->getConfigOptionBAL()) {
                    $productCode = self::CHRONO_POST_BAL;
                } else {
                    $productCode = self::CHRONO_POST;
                }
                break;
        }

        return $productCode;
    }

    /**
     * Get chronopost product code to shipment
     *
     * @param string $code
     * @param bool   $withBal
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getChronoProductCodeStr(string $code, $withBal = false): string
    {
        $code = strtolower($code);

        switch ($code) {
            case self::CHRONO_FRESH_CODE:
                $productCode = self::CHRONOFRESH_STR[$this->getDefaultChronofreshOffer()];
                break;
            case self::CHRONO_RELAIS_CODE:
                $productCode = self::CHRONORELAY_STR;
                break;
            case self::CHRONO_EXPRESS_CODE:
                $productCode = self::CHRONO_EXPRESS_STR;
                break;
            case self::CHRONO_10_CODE:
                $productCode = self::CHRONOPOST_C10_STR;
                break;
            case self::CHRONO_18_CODE:
                if ($withBal === true && $this->getConfigOptionBAL()) {
                    $productCode = self::CHRONOPOST_C18_BAL_STR;
                } else {
                    $productCode = self::CHRONOPOST_C18_STR;
                }
                break;
            case self::CHRONO_CLASSIC_CODE:
                $productCode = self::CHRONOPOST_CCLASSIC_STR;
                break;
            case self::CHRONO_RELAIS_EUROPE_CODE:
                $productCode = self::CHRONO_RELAY_EUROPE_STR;
                break;
            case self::CHRONO_RELAIS_DOM_CODE:
                $productCode = self::CHRONO_RELAY_DOM_STR;
                break;
            case self::CHRONO_SAMEDAY_CODE:
                $productCode = self::CHRONOPOST_SMD_STR;
                break;
            case self::CHRONO_SRDV_CODE:
                $productCode = self::CHRONOPOST_SRDV_STR;
                break;
            default:
                if ($withBal === true && $this->getConfigOptionBAL()) {
                    $productCode = self::CHRONO_POST_BAL_STR;
                } else {
                    $productCode = self::CHRONO_POST_STR;
                }
                break;
        }

        return $productCode;
    }

    /**
     * Get chronopost customer type
     *
     * @return int
     */
    public function getCustomerType(): int
    {
        if ($this->customerType === 0) {
            $this->customerType = (int)$this->getConfig('chronorelais/shipping/customer_type');
        }

        return $this->customerType;
    }

    /**
     * Check if config enable
     *
     * @return bool
     */
    public function getConfigOptionBAL(): bool
    {
        if ($this->optionBalIsEnabled === null) {
            if ($this->getCustomerType() === 2) {
                $this->optionBalIsEnabled = false;
            } else {
                $this->optionBalIsEnabled = (bool)$this->getConfig('chronorelais/optionbal/enabled');
            }
        }

        return $this->optionBalIsEnabled;
    }

    /**
     * Get saturday shipping amount
     *
     * @return float
     */
    public function getSaturdayShippingAmount(): float
    {
        if ($this->saturdayShippingAmount === null) {
            $this->saturdayShippingAmount = (float)$this->getConfig('chronorelais/saturday/amount');
        }

        return $this->saturdayShippingAmount;
    }

    /**
     * Display saturday option for customer
     *
     * @return bool
     */
    public function displaySaturdayOption(): bool
    {
        if ($this->displaySaturdayOption === null) {
            if ($this->getCustomerType() === 2) {
                $this->displaySaturdayOption = false;
            } else {
                $this->displaySaturdayOption = (bool)$this->getConfig('chronorelais/saturday/display_to_customer');
            }
        }

        return $this->displaySaturdayOption;
    }

    /**
     * Shipper Information
     *
     * @param $field
     *
     * @return string
     */
    public function getConfigurationShipperInfo($field): string
    {
        $fieldValue = '';
        if ($field && $this->getConfig('chronorelais/shipperinformation/' . $field)) {
            $fieldValue = $this->getConfig('chronorelais/shipperinformation/' . $field);
        }

        return $fieldValue;
    }

    /**
     * Get config file path
     *
     * @param string $name
     *
     * @return string
     */
    public function getConfigFilePath(string $name): string
    {
        $path = $this->moduleReader->getModuleDir('', 'Chronopost_Chronorelais');

        return $path . '/config/' . $name;
    }

    /**
     * Get country return allowed
     *
     * @param string $countryId
     * @param string $shippingMethod
     *
     * @return bool
     */
    public function returnAuthorized(string $countryId, string $shippingMethod): bool
    {
        return in_array($countryId, self::COUNTRY_ALLOWED_RETURN);
    }

    /**
     * Get label map
     *
     * @param string $label
     *
     * @return string|null
     */
    public function getLabelGmap($label)
    {
        return $this->getConfig('chronorelais/libelles_gmap/' . $label);
    }

    /**
     * Get weight limit
     *
     * @param string $shippingMethod
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWeightLimit(string $shippingMethod): int
    {
        if ($shippingMethod === self::CHRONO_RELAIS_EUROPE_CODE || $shippingMethod === self::CHRONO_RELAIS_CODE) {
            return 20 * $this->getWeightCoef();
        }

        return 30 * $this->getWeightCoef();
    }

    /**
     * Get input dimensions limit
     *
     * @param string $shippingMethod
     *
     * @return int
     */
    public function getInputDimensionsLimit(string $shippingMethod): int
    {
        if ($shippingMethod === self::CHRONO_RELAIS_EUROPE_CODE || $shippingMethod === self::CHRONO_RELAIS_CODE) {
            return 100;
        }

        return 150;
    }

    /**
     * Get global dimensions limit
     *
     * @param string $shippingMethod
     *
     * @return int
     */
    public function getGlobalDimensionsLimit(string $shippingMethod): int
    {
        if ($shippingMethod === self::CHRONO_RELAIS_EUROPE_CODE || $shippingMethod === self::CHRONO_RELAIS_CODE) {
            return 250;
        }

        return 300;
    }

    /**
     * Get converted weight
     *
     * @param float $weight
     *
     * @return float
     */
    public function getConvertedWeight(float $weight)
    {
        return $weight / 1000;
    }

    /**
     * Get chrono weight unit
     *
     * @param string|null $orderId
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getChronoWeightUnit($orderId = null)
    {
        if (!isset($this->chronoWeightUnit[$orderId])) {
            $code = null;
            if ($orderId !== null) {
                $order = $this->getOrder($orderId);
                $store = $this->storeManager->getStore($order->getStoreId());
                $code = $store->getCode();
            }

            $this->chronoWeightUnit[$orderId] = $this->getConfig('chronorelais/weightunit/unit', $code);
        }

        return $this->chronoWeightUnit[$orderId];
    }

    /**
     * Get weight unit
     *
     * @param string      $orderId
     * @param string|null $storeId
     *
     * @return null|string
     * @throws NoSuchEntityException
     */
    public function getWeightUnit(string $orderId, $storeId = null)
    {
        if ($storeId === null) {
            $order = $this->getOrder($orderId);
            $storeId = $order->getStoreId();
        }

        $store = $this->storeManager->getStore($storeId);

        return $this->getConfig('general/locale/weight_unit', $store->getCode());
    }

    /**
     * Get weight coefficient
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWeightCoef(): int
    {
        $coefficient = 1;

        if ($this->getChronoWeightUnit() === 'g') {
            $coefficient = 1000; // Convert g to kg
        }

        return $coefficient;
    }

    /**
     * Get expiration date
     *
     * @return string
     * @throws \Exception
     */
    public function getDefaultExpirationDate()
    {
        if ($this->defaultExpirationDate === null) {
            $date = new DateTime(date('Y-m-d 00:00:00'));
            $value = (string)$this->getConfig('chronorelais/expiration_date/expiration_delay');
            if ($value < 3) {
                $value = '3'; // Minimum value
            }

            $date = $date->modify('+' . $value . ' days');
            $this->defaultExpirationDate = $date->format('d-m-Y');
        }

        return $this->defaultExpirationDate;
    }

    /**
     * Get expiration date
     *
     * @param string $date
     * @param string $format
     *
     * @return string
     * @throws \Exception
     */
    public function getFormattedExpirationDate(string $date, string $format = 'Y-m-d\TH:i:s')
    {
        $date = new DateTime($date);

        return $date->format($format);
    }

    /**
     * Validate expiration date
     *
     * @param string|null $expirationDate
     * @param string|null $offer
     *
     * @return string|null|false
     * @throws \Exception
     */
    public function validateExpirationDate($expirationDate, $offer)
    {
        if ($expirationDate !== null) {
            $expirationDate = $this->getFormattedExpirationDate(
                $expirationDate
            );

            $today = $this->getFormattedExpirationDate(date('d-m-Y'));
            if ($expirationDate < $today) {
                return false;
            }

            $defaultExpirationDate = $this->getDefaultExpirationDate();
            $defaultExpirationDate = $this->getFormattedExpirationDate(
                $defaultExpirationDate
            );

            if ($expirationDate < $defaultExpirationDate && $offer !== 'sec') {
                return false;
            }
        }

        return $expirationDate;
    }

    /**
     * Check if shipping is enabled
     *
     * @param string $shippingMethod
     *
     * @return bool
     */
    public function shippingMethodIsEnabled(string $shippingMethod): bool
    {
        if (!isset($this->shippingMethodIsEnabled[$shippingMethod])) {
            $customerType = $this->getCustomerType();
            $this->shippingMethodIsEnabled[$shippingMethod] = true;

            if ($customerType === 2 && $shippingMethod !== Data::CHRONO_FRESH_CODE) {
                $this->shippingMethodIsEnabled[$shippingMethod] = false;
            }

            if ($customerType === 1 && $shippingMethod === Data::CHRONO_FRESH_CODE) {
                $this->shippingMethodIsEnabled[$shippingMethod] = false;
            }
        }

        return $this->shippingMethodIsEnabled[$shippingMethod];
    }

    /**
     * Get chronofresh offers
     *
     * @return string|null
     */
    public function getDefaultChronofreshOffer()
    {
        if ($this->defaultChronoFreshOffer === null) {
            $this->defaultChronoFreshOffer = $this->getConfig('carriers/chronofresh/offers');
        }

        return $this->defaultChronoFreshOffer;
    }

    /**
     * Get shipping method code
     *
     * @param string $shippingMethod
     *
     * @return string
     */
    public function getShippingMethodeCode(string $shippingMethod)
    {
        $shippingMethod = explode('_', $shippingMethod);

        return $shippingMethod[1] ?? $shippingMethod[0];
    }
}
