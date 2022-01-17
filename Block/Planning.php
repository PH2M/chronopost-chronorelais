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

namespace Chronopost\Chronorelais\Block;

use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Pricing\Helper\Data as HelperPricing;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Planning
 *
 * @package Chronopost\Chronorelais\Block
 */
class Planning extends Template
{

    /**
     * @var DateTime
     */
    protected $_datetime;

    /**
     * @var HelperPricing
     */
    protected $_helperPricing;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Planning constructor.
     *
     * @param Template\Context $context
     * @param DateTime         $dateTime
     * @param HelperPricing    $helperPricing
     * @param CheckoutSession  $checkoutSession
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        DateTime $dateTime,
        HelperPricing $helperPricing,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_datetime = $dateTime;
        $this->_helperPricing = $helperPricing;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Get timestamp from date
     *
     * @param $date
     *
     * @return int
     */
    public function getTimestamp($date)
    {
        return $this->_datetime->timestamp($date);
    }

    /**
     * @return mixed
     */
    public function getRdvConfig()
    {
        return json_decode($this->_scopeConfig->getValue("carriers/chronopostsrdv/rdv_config"), true);
    }

    /**
     * Get currency
     *
     * @param $price
     *
     * @return float|string
     */
    public function currency($price)
    {
        return $this->_helperPricing->currency($price);
    }

    /**
     * Get carrier base price
     *
     * @return mixed
     */
    public function getCarrierBasePrice()
    {
        $address = $this->getAddress();

        $ratePrice = 0;
        $rates = $address->setCollectShippingRates(true)->collectShippingRates()
            ->getGroupedAllShippingRates();
        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                if (preg_match('/' . Data::CHRONO_RELAIS_CODE . '/', $rate->getCode(), $matches, PREG_OFFSET_CAPTURE)) {
                    $ratePrice = $rate->getPrice();
                    $_srdvConfig = json_decode(
                        $this->_scopeConfig->getValue("carriers/chronopostsrdv/rdv_config"),
                        true
                    );

                    if ($this->_checkoutSession->getData('chronopostsrdv_creneaux_info')) {
                        $rdvInfo = json_decode($this->_checkoutSession->getData('chronopostsrdv_creneaux_info'), true);
                        $ratePrice -= $_srdvConfig[$rdvInfo['tariffLevel'] . "_price"];
                    } else {
                        $minimal_price = '';
                        for ($i = 1; $i <= 4; $i++) {
                            if ($minimal_price === '' || isset($_srdvConfig["N" . $i . "_price"]) && $_srdvConfig["N" . $i . "_price"] < $minimal_price) {
                                $minimal_price = $_srdvConfig["N" . $i . "_price"];
                            }
                        }
                        $ratePrice -= $minimal_price;
                    }
                }
            }
        }

        return $ratePrice;
    }
}
