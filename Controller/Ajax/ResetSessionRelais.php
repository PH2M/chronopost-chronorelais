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
 * Class ResetSessionRelais
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class ResetSessionRelais extends Action
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
     * ResetSessionRelais constructor.
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
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $relaisidbefore = $this->checkoutSession->getData('chronopost_chronorelais_relais_id');
        $this->checkoutSession->unsetData('chronopost_chronorelais_relais_id');
        $relaisidafter = $this->checkoutSession->getData('chronopost_chronorelais_relais_id');

        $data = ['success' => true, 'relais_id_before' => $relaisidbefore, 'relais_id_after' => $relaisidafter];
        $result = $this->resultJsonFactory->create();
        $result->setData($data);

        return $result;
    }
}
