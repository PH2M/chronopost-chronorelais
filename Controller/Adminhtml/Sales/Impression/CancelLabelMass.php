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
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Chronopost\Chronorelais\Helper\Contract as HelperContract;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Chronopost\Chronorelais\Model\ContractsOrdersFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class CancelLabelMass
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 */
class CancelLabelMass extends AbstractImpression
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
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * @var HelperContract
     */
    private $helperContract;

    /**
     * CancelLabelMass constructor.
     *
     * @param Context           $context
     * @param DirectoryList     $directoryList
     * @param PageFactory       $resultPageFactory
     * @param HelperData        $helperData
     * @param PDFMerger         $PDFMerger
     * @param ManagerInterface  $messageManager
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     * @param HelperShipment    $helperShipment
     * @param HelperWebservice  $helperWebservice
     * @param HelperContract    $helperContract
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager,
        Filter $filter,
        CollectionFactory $collectionFactory,
        HelperShipment $helperShipment,
        HelperWebservice $helperWebservice,
        HelperContract $helperContract
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->helperShipment = $helperShipment;
        $this->helperWebservice = $helperWebservice;
        $this->helperContract = $helperContract;
    }

    /**
     * Mass cancellation of labels
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $deleteCount = 0;
            $errors = [];
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection->getItems() as $order) {
                $contractArr = [];
                $contract = $this->helperContract->getContractByOrderId($order->getId());
                if (!$contract) {
                    $errors[] = __('Contract not found for order %1', $order->getIncrementId());
                    continue;
                }

                $contractArr['contract_account_number'] = $contract->getData('contract_account_number');
                $contractArr['contract_account_password'] = $contract->getData('contract_account_password');

                $shipments = $order->getShipmentsCollection();
                if ($shipments->count()) {
                    foreach ($shipments as $shipment) {
                        $tracks = $shipment->getAllTracks();
                        foreach ($tracks as $track) {
                            if ($track->getChronoReservationNumber()) {
                                $webservbt = $this->helperWebservice->cancelLabel($track->getNumber(), $contractArr);
                                if ($webservbt) {
                                    $webservbt->return->errorCode = 0;
                                    if ($webservbt->return->errorCode === 0) {
                                        $deleteCount++;

                                        // Remove magento tracking
                                        $track->delete();

                                        // Remove LT number
                                        $history = $this->helperShipment->getHistoryLt(
                                            'lt_number',
                                            $track->getNumber()
                                        );

                                        if ($history->getId()) {
                                            $history->delete();
                                        }
                                    } else {
                                        switch ($webservbt->return->errorCode) {
                                            case 1:
                                                $errorMessage = __('A system error has occurred');
                                                break;
                                            case 2:
                                                $errorMessage = __('The parcel’s parameters do not fall within the ' .
                                                    'scope of the contract passed or it has not yet been ' .
                                                    'registered in the Chronopost tracking system');
                                                break;
                                            case 3:
                                                $errorMessage = __('The parcel cannot be cancelled because it has ' .
                                                    'been dispatched by Chronopost');
                                                break;
                                            default:
                                                $errorMessage = '';
                                                break;
                                        }

                                        $errors[] = __(
                                            'An error occurred while deleting label %1: %2.',
                                            $track->getNumber(),
                                            $errorMessage
                                        );
                                    }
                                } else {
                                    $errors[] = __(
                                        'Sorry, an error occurred while deleting label %1. ' .
                                        'Please contact Chronopost or try again later',
                                        $track->getNumber()
                                    );
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }

        if ($deleteCount > 1) {
            $this->messageManager->addSuccessMessage(
                __('%1 shipping labels have been cancelled.', $deleteCount)
            );
        } elseif ($deleteCount === 1) {
            $this->messageManager->addSuccessMessage(
                __('%1 shipping label has been cancelled.', $deleteCount)
            );
        }

        if (count($errors)) {
            foreach ($errors as $error) {
                $this->messageManager->addErrorMessage(__($error));
            }
        }

        $resultRedirect->setPath('chronorelais/sales/impression');

        return $resultRedirect;
    }
}
