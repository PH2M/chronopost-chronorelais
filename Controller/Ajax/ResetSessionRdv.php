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
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class ResetSessionRdv
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class ResetSessionRdv extends Action
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
     * ResetSessionRdv constructor.
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
     * Reset value session
     *
     * @return Json
     */
    public function execute()
    {
        // Reset session value
        $this->checkoutSession->unsetData('chronopostsrdv_creneaux_info');

        $data = ['success' => true];
        $result = $this->resultJsonFactory->create();
        $result->setData($data);

        return $result;
    }
}
