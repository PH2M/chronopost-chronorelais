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
use Chronopost\Chronorelais\Helper\Webservice;
use DateTime;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ChronopostSrdv
 *
 * @package Chronopost\Chronorelais\Model\Carrier
 */
class ChronopostSrdv extends AbstractChronopost
{
    const PRODUCT_CODE = '2O';
    const CARRIER_CODE = 'chronopostsrdv';
    const CHECK_CONTRACT = true;
    const DELIVER_ON_SATURDAY = true;

    /**
     * @var string
     */
    protected $_code = 'chronopostsrdv';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * ChronopostSrdv constructor.
     *
     * @param ScopeConfigInterface                       $scopeConfig
     * @param ErrorFactory                               $rateErrorFactory
     * @param LoggerInterface                            $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param MethodFactory                              $rateMethodFactory
     * @param Webservice                                 $helperWebservice
     * @param ResultFactory                              $trackFactory
     * @param StatusFactory                              $trackStatusFactory
     * @param CheckoutSession                            $checkoutSession
     * @param Data                                       $helperData
     * @param Contract                                   $helperContract
     * @param SerializerInterface                        $jsonSerialize
     * @param array                                      $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Webservice $helperWebservice,
        ResultFactory $trackFactory,
        StatusFactory $trackStatusFactory,
        CheckoutSession $checkoutSession,
        Data $helperData,
        Contract $helperContract,
        SerializerInterface $jsonSerialize,
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
            $jsonSerialize,
            $checkoutSession,
            $data
        );
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Add additional price
     *
     * @param $price
     *
     * @return mixed
     */
    public function additionalPrice($price)
    {
        $price = parent::additionalPrice($price);

        $srdvConfig = $this->getConfigData('rdv_config');
        $srdvConfig = json_decode($srdvConfig, true);

        if ($this->checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            $chronopostsrdvCreneauxInfo = json_decode(
                $this->checkoutSession->getData('chronopostsrdv_creneaux_info'),
                true
            );

            $tarifLevel = $chronopostsrdvCreneauxInfo['tariffLevel'];
            $price += $srdvConfig[$tarifLevel . '_price'];
        } else {
            $minPrice = '';
            for ($i = 1; $i <= 4; $i++) {
                if ($minPrice === '' || (isset($srdvConfig['N' . $i . '_price']) &&
                        $srdvConfig['N' . $i . '_price'] < $minPrice)) {
                    $minPrice = $srdvConfig['N' . $i . '_price'];
                }
            }

            $price += $minPrice;
        }

        return $price;
    }

    /**
     * Add delivery date in title
     *
     * @return false|string
     * @throws \Exception
     */
    public function getMethodTitle()
    {
        $methodTitle = parent::getMethodTitle();

        if ($this->checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            $chronopostsrdvCreneauxInfo = json_decode(
                $this->checkoutSession->getData('chronopostsrdv_creneaux_info'),
                true
            );

            $dateRdvStart = new DateTime($chronopostsrdvCreneauxInfo['deliveryDate']);
            $dateRdvStart->setTime(
                (int)$chronopostsrdvCreneauxInfo['startHour'],
                (int)$chronopostsrdvCreneauxInfo['startMinutes']
            );

            $dateRdvEnd = new DateTime($chronopostsrdvCreneauxInfo['deliveryDate']);
            $dateRdvEnd->setTime(
                (int)$chronopostsrdvCreneauxInfo['endHour'],
                (int)$chronopostsrdvCreneauxInfo['endMinutes']
            );

            $methodTitle .= sprintf(' - %s %s', __('On'), $dateRdvStart->format('d/m/Y'));
            $methodTitle .= sprintf(
                ' %s',
                __('between %1 and %2', $dateRdvStart->format('H:i'), $dateRdvEnd->format('H:i'))
            );
        }

        return $methodTitle;
    }
}
