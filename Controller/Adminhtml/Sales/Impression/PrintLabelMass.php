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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class PrintLabelMass
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 */
class PrintLabelMass extends AbstractImpression
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var HelperShipment
     */
    protected $helperShipment;

    /**
     * PrintLabelMass constructor.
     *
     * @param Context           $context
     * @param DirectoryList     $directoryList
     * @param PageFactory       $resultPageFactory
     * @param HelperData        $helperData
     * @param PDFMerger         $PDFMerger
     * @param ManagerInterface  $messageManager
     * @param HelperShipment    $helperShipment
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager,
        HelperShipment $helperShipment,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->helperShipment = $helperShipment;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Mass print label
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParam('data');

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            $labelUrl = [];
            foreach ($collection->getItems() as $order) {
                if ($order && $order->getId()) {
                    $shippingMethodCode = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
                    if (!$this->helperData->isChronoMethod($shippingMethodCode)) {
                        throw new \Exception(
                            (string)__('Delivery option not Chronopost for order %1', $order->getIncrementId())
                        );
                    }

                    $packageList = $data[$order->getId()];
                    $dimensions = json_decode($packageList['dimensions'], true);

                    $offer = $packageList['offer'] ?? null;
                    $expirationDate = $packageList['expiration_date'] ?? null;
                    if ($expirationDate && $offer) {
                        $expirationDate = $this->helperData->validateExpirationDate($expirationDate, $offer);
                        if ($expirationDate === false) {
                            throw new \Exception(
                                (string)__(
                                    'Order %1: You cannot ship merchandise with an expiration date of less than 3 days.',
                                    $order->getIncrementId()
                                )
                            );
                        }
                    }

                    $shipments = $order->getShipmentsCollection();
                    if ($shipments->count()) {
                        foreach ($shipments as $shipment) {
                            $labelUrl = array_merge(
                                $labelUrl,
                                $this->helperShipment->getLabelUrl(
                                    $shipment,
                                    null,
                                    'shipping',
                                    $dimensions,
                                    $packageList['nb_colis']
                                )
                            );
                        }
                    } elseif (isset($data[$order->getId()])) {
                        $createdShipment = $this->helperShipment->createNewShipment(
                            $order,
                            [],
                            [],
                            $dimensions,
                            isset($packageList['nb_colis']) ? (int)$packageList['nb_colis'] : 1,
                            false,
                            (int)$packageList['contract_id'],
                            $expirationDate,
                            $offer
                        );

                        if ($createdShipment) {
                            $labelUrl = array_merge(
                                $labelUrl,
                                $this->helperShipment->getLabelUrl(
                                    $createdShipment,
                                    null,
                                    'shipping',
                                    $dimensions,
                                    $packageList['nb_colis']
                                )
                            );
                        }
                    }
                }
            }

            if (count($labelUrl) === 1) {
                $this->prepareDownloadResponse('Etiquette_chronopost.pdf', $labelUrl[0]);
            } elseif (count($labelUrl) > 1) {
                $this->_processDownloadMass($labelUrl);
            } else {
                $this->messageManager->addNoticeMessage(__('No LT for the selected order(s).'));
                $resultRedirect->setPath('chronorelais/sales/impression');

                return $resultRedirect;
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }
    }
}
