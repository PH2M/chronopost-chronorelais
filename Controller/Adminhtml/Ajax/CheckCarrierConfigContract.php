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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWS;

/**
 * Class CheckCarrierConfigContract
 *
 * Chronopost\Chronorelais\Controller\Adminhtml\Ajax
 */
class CheckCarrierConfigContract extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var $helperWS
     */
    protected $helperWS;

    /**
     * CheckCarrierConfigContract constructor.
     *
     * @param Context     $context
     * @param JsonFactory $jsonFactory
     * @param HelperData  $helperData
     * @param HelperWS    $helperWS
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        HelperData $helperData,
        HelperWS $helperWS
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->helperData = $helperData;
        $this->helperWS = $helperWS;
    }

    /**
     * Check if shipping method is enabled
     *
     * @return Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $shippingMethod = $params['shippingMethod'];
        $contractId = $params['contractId'];
        $offer = $params['offer'];

        $result = $this->resultJsonFactory->create();

        $data = 'not allowed';
        if ($this->helperWS->shippingMethodEnabled($shippingMethod, (int)$contractId, $offer) &&
            $this->helperData->shippingMethodIsEnabled($shippingMethod)) {
            $data = 'allowed';
        }

        $result->setData($data);

        return $result;
    }
}
