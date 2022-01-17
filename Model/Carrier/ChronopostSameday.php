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

use Chronopost\Chronorelais\Helper\Contract;
use Chronopost\Chronorelais\Helper\Data;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

/**
 * Class ChronopostSameday
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class ChronopostSameday extends AbstractChronopost
{
    const PRODUCT_CODE = '4I';
    const CHECK_CONTRACT = true;
    const DELIVER_ON_SATURDAY = true;

    /**
     * @var string
     */
    protected $_code = 'chronosameday';

    /**
     * @var DateTime
     */
    private $datetime;

    /**
     * ChronopostSameday constructor.
     *
     * @param ScopeConfigInterface                       $scopeConfig
     * @param ErrorFactory                               $rateErrorFactory
     * @param LoggerInterface                            $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param MethodFactory                              $rateMethodFactory
     * @param HelperWebservice                           $helperWebservice
     * @param ResultFactory                              $trackFactory
     * @param StatusFactory                              $trackStatusFactory
     * @param Data                                       $helperData
     * @param Contract                                   $helperContract
     * @param SerializerInterface                        $jsonSerializer
     * @param CheckoutSession                            $checkoutSession
     * @param DateTime                                   $dateTime
     * @param array                                      $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        HelperWebservice $helperWebservice,
        ResultFactory $trackFactory,
        StatusFactory $trackStatusFactory,
        Data $helperData,
        Contract $helperContract,
        SerializerInterface $jsonSerializer,
        CheckoutSession $checkoutSession,
        DateTime $dateTime,
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateResultFactory,
            $rateMethodFactory,
            $helperWebservice,
            $trackFactory,
            $trackStatusFactory,
            $helperData,
            $helperContract,
            $jsonSerializer,
            $checkoutSession,
            $data
        );
        $this->datetime = $dateTime;
    }

    /**
     * Check if request is valid
     *
     * @param RateRequest $request
     *
     * @return bool
     * @throws \Exception
     */
    public function validateMethod(RateRequest $request)
    {
        $validate = parent::validateMethod($request);

        if ($validate === true) {
            // Check if we should auto disable the module (it's past hour)
            date_default_timezone_set($this->_scopeConfig->getValue('general/locale/timezone'));
            $deliveryTimeLimitConf = $this->getConfigData('delivery_time_limit');

            // Safe fallback
            if (!$deliveryTimeLimitConf) {
                $deliveryTimeLimitConf = '15:00';
            }

            $deliveryTimeLimit = new \DateTime(date('Y-m-d') . ' ' . $deliveryTimeLimitConf . ':00');
            $currentTime = new \DateTime('NOW');

            $validate = false;
            if ($this->datetime->timestamp($currentTime->getTimestamp()) <= $deliveryTimeLimit->getTimestamp()) {
                $validate = true;
            }
        }

        return $validate;
    }
}
