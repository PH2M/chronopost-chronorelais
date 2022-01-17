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

use Chronopost\Chronorelais\Helper\Data;
use Magento\Framework\Exception\StateException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class PaymentInformationManagement
 *
 * @package Chronopost\Chronorelais\Plugin
 */
class PaymentInformationManagement
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
     * PaymentInformationManagement constructor.
     *
     * @param CheckoutSession         $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $cartRepository;
    }

    /**
     * Verify session if appointment mode
     *
     * @param                                               $subject
     * @param string                                        $cartId
     * @param PaymentInterface                              $paymentMethod
     * @param AddressInterface|null                         $billingAddress
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);

        $shippingAddress = $quote->getShippingAddress();

        if (preg_match(
            '/' . Data::CHRONO_SRDV_CODE . '/',
            $shippingAddress->getShippingMethod(),
            $matches,
            PREG_OFFSET_CAPTURE
        ) && !$this->checkoutSession->getData('chronopostsrdv_creneaux_info')) {
            throw new StateException(__('Please select an appointment date'));
        }
    }
}
