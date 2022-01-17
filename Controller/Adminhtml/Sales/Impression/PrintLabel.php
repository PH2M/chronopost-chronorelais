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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;

/**
 * Class PrintLabel
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 */
class PrintLabel extends AbstractImpression
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var HelperShipment
     */
    protected $helperShipment;

    /**
     * PrintLabel constructor.
     *
     * @param Context          $context
     * @param DirectoryList    $directoryList
     * @param PageFactory      $resultPageFactory
     * @param OrderFactory     $orderFactory
     * @param HelperData       $helperData
     * @param HelperShipment   $helperShipment
     * @param PDFMerger        $PDFMerger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory,
        HelperData $helperData,
        HelperShipment $helperShipment,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->helperShipment = $helperShipment;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Print label
     *
     * @return Redirect
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $trackNumber = $this->getRequest()->getParam('track_number');

        try {
            $labelUrl = $this->helperShipment->getLabelUrl($shipmentId, $trackNumber, $type);
            if (count($labelUrl) === 1) {
                $this->prepareDownloadResponse('Etiquette_chronopost.pdf', $labelUrl[0]);
            } elseif (count($labelUrl) > 1) {
                $this->_processDownloadMass($labelUrl);
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }
    }
}
