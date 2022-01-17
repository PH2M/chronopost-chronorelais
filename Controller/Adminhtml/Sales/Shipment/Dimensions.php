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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Shipment;

use Chronopost\Chronorelais\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\LayoutFactory;
use Chronopost\Chronorelais\Block\Adminhtml\Sales\Shipment\Dimensions as ShipmentDimensions;

/**
 * Class Dimensions
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Shipment
 */
class Dimensions extends Action
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
     * @var Data
     */
    protected $helperData;

    /**
     * @var mixed
     */
    protected $shippingMethod;

    /**
     * @var mixed
     */
    protected $orderId;

    /**
     * Dimensions constructor.
     *
     * @param Context       $context
     * @param JsonFactory   $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     * @param Data          $helperData
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->helperData = $helperData;
        $this->shippingMethod = $context->getRequest()->getParam('shipping_method');
        $this->orderId = $context->getRequest()->getParam('order_id');
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $result = $this->resultJsonFactory->create();
        $layout = $this->layoutFactory->create();

        $data['html'] = $layout->getLayout()
            ->createBlock(
                ShipmentDimensions::class,
                '',
                [
                    'data' => [
                        'shipping_method' => $this->shippingMethod,
                        'order_id'        => $this->orderId
                    ]
                ]
            )
            ->setTemplate('Chronopost_Chronorelais::sales/shipment/dimensions.phtml')
            ->toHtml();

        $data['error'] = $data['html'] !== null ? 0 : 1;

        return $result->setData($data);
    }
}
