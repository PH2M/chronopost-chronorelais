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

namespace Chronopost\Chronorelais\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\Exception\StateException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

/**
 * Class ShippingInformationManagement
 *
 * @package Chronopost\Chronorelais\Plugin
 */
class ShippingInformationManagement
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * @var Data
     */
    private $helper;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param CheckoutSession         $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param HelperWebservice        $webservice
     * @param Data                    $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        HelperWebservice $webservice,
        Data $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $cartRepository;
        $this->helperWebservice = $webservice;
        $this->helper = $helper;
    }

    /**
     * Before save shipping information
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param string                                                $cartId
     * @param ShippingInformationInterface                          $addressInformation
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @throws StateException|NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $methodCode = $addressInformation->getShippingMethodCode();

        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setRelaisId('');
        $quote->setData('chronopostsrdv_creneaux_info', '');

        // If relay delivery method: check if relay point checked
        if (preg_match('/' . Data::CHRONO_RELAIS_CODE . '/', $methodCode, $matches, PREG_OFFSET_CAPTURE)) {
            $relayId = $this->checkoutSession->getData('chronopost_chronorelais_relais_id');
            if (!$relayId) {
                throw new StateException(__('Select a pick-up point'));
            }

            $relay = $this->helperWebservice->getDetailRelaisPoint($relayId);
            if ($relay) {
                $address = $addressInformation->getShippingAddress();
                $address->setCustomerAddressId(0);
                $address->setSaveInAddressBook(0);
                $address->setSameAsBilling(0);
                $address->setCity($relay->localite);
                $address->setPostcode($relay->codePostal);
                $address->setStreet([$relay->adresse1, $relay->adresse2, $relay->adresse3]);

                $nom = '';
                if (isset($relay->nom)) {
                    $nom = $relay->nom;
                } elseif (isset($relay->nomEnseigne)) {
                    $nom = $relay->nomEnseigne;
                }

                $address->setCompany($nom);
                $addressInformation->setShippingAddress($address);

                $quote->setShippingAddress($address)->setRelaisId($relayId);
            } else {
                throw new StateException(__('The pick-up point does not exist.'));
            }
        } elseif (preg_match('/' . Data::CHRONO_SRDV_CODE . '/', $methodCode, $matches, PREG_OFFSET_CAPTURE)) {
            // If delivery method RDV: check if Time selected
            $rdvInfo = $this->checkoutSession->getData('chronopostsrdv_creneaux_info');
            if (!$rdvInfo) {
                throw new StateException(__('Please select an appointment date'));
            }

            // Verification of the chosen slots
            $arrayRdvInfo = json_decode($rdvInfo, true);
            $confirm = $this->helperWebservice->confirmDeliverySlot($arrayRdvInfo);
            if ($confirm->return->code !== 0) {
                throw new StateException(__($confirm->return->message));
            }

            $arrayRdvInfo['productCode'] = $confirm->return->productServiceV2->productCode;
            $arrayRdvInfo['serviceCode'] = $confirm->return->productServiceV2->serviceCode;
            if (isset($confirm->return->productServiceV2->asCode)) {
                $arrayRdvInfo['asCode'] = $confirm->return->productServiceV2->asCode;
            }

            $quote->setData('chronopostsrdv_creneaux_info', json_encode($arrayRdvInfo));
        }

        $quote->collectTotals(); // recollect totals to apply potential saturday additional cost.

        $isSendingDay = $this->helper->isSendingDay();
        $saturdayOptionValueSession = $this->checkoutSession->getData('chronopost_saturday_option');
        $customerChoiceEnabled = $this->helper->displaySaturdayOption();

        if ($saturdayOptionValueSession && $customerChoiceEnabled === true && $isSendingDay === true) {
            $quote->setData('force_saturday_option', '1');
        } else {
            $quote->setData('force_saturday_option', '0');
        }

        $this->quoteRepository->save($quote);
    }
}
