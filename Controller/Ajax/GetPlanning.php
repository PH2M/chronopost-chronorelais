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

namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\AddressFactory;
use Chronopost\Chronorelais\Block\Planning;

/**
 * Class GetPlanning
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class GetPlanning extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * GetPlanning constructor.
     *
     * @param Context          $context
     * @param JsonFactory      $jsonFactory
     * @param LayoutFactory    $layoutFactory
     * @param HelperData       $helperData
     * @param HelperWebservice $helperWebservice
     * @param Session          $session
     * @param AddressFactory   $addressFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        HelperData $helperData,
        HelperWebservice $helperWebservice,
        Session $session,
        AddressFactory $addressFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->helperData = $helperData;
        $this->helperWebservice = $helperWebservice;
        $this->checkoutSession = $session;
        $this->addressFactory = $addressFactory;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $methodCode = $this->getRequest()->getParam('method_code');
        $result = $this->resultJsonFactory->create();

        $resultData = [];

        try {
            $shippingAddressData = $this->getRequest()->getParam('shipping_address');
            $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
            $shippingAddress->addData($shippingAddressData);

            $slots = $this->helperWebservice->GetPlanning($shippingAddress);
            if ($slots) {
                $layout = $this->layoutFactory->create();
                $content = $layout->createBlock(Planning::class)
                    ->setAddress($shippingAddress)
                    ->setMethodCode($methodCode)
                    ->setSlots($slots)
                    ->setTemplate('Chronopost_Chronorelais::chronopostsrdv_planning.phtml')
                    ->toHtml();

                $resultData['method_code'] = $methodCode;
                $resultData['content'] = $content;
                $resultData['creneaux'] = $slots;
            } else {
                throw new \Exception(
                    (string)__("It is not yet possible to use this service for your order. We are working to make this new service available in other cities.")
                );
            }
        } catch (\Exception $e) {
            $resultData['error'] = true;
            $resultData['message'] = $e->getMessage();
        }

        $result->setData($resultData);

        return $result;
    }
}
