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
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class SetSessionSaturdayOption
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class SetSessionSaturdayOption extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * SetSessionSaturdayOption constructor.
     *
     * @param Context         $context
     * @param JsonFactory     $jsonFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Set value to session
     *
     * @return Json
     */
    public function execute()
    {
        $saturdayOption = $this->getRequest()->getParam('saturday_option');

        $this->checkoutSession->setData('chronopost_saturday_option', $saturdayOption);
        $result = $this->resultJsonFactory->create();
        $result->setData([]);

        return $result;
    }
}
