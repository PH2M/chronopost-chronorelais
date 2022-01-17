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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class ResetSessionSaturdayOption
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class ResetSessionSaturdayOption extends Action
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
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ResetSessionSaturdayOption constructor.
     *
     * @param Context            $context
     * @param JsonFactory        $jsonFactory
     * @param CheckoutSession    $checkoutSession
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Reset data from quote and checkout session
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->checkoutSession->unsetData('chronopost_saturday_option');

        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $values = ['force_saturday_option' => '0'];
            $connection = $this->resourceConnection->getConnection('core_write');
            $connection->update($this->resourceConnection->getTableName('quote'), $values, 'entity_id = ' . $quoteId);
        }

        $result = $this->resultJsonFactory->create();
        $result->setData([]);

        return $result;
    }
}
