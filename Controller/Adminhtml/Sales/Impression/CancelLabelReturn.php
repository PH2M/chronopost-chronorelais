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

use Chronopost\Chronorelais\Helper\Contract as HelperContract;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Chronopost\Chronorelais\Helper\Webservice;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;

/**
 * Class CancelLabelReturn
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 * @SuppressWarnings("CouplingBetweenObjects")
 */
class CancelLabelReturn extends AbstractImpression
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

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
     * @var Webservice
     */
    private $helperWebservice;

    /**
     * @var HelperContract
     */
    private $helperContract;

    /**
     * CancelLabelReturn constructor.
     *
     * @param Context                      $context
     * @param DirectoryList                $directoryList
     * @param PageFactory                  $resultPageFactory
     * @param HelperData                   $helperData
     * @param PDFMerger                    $PDFMerger
     * @param ManagerInterface             $messageManager
     * @param OrderFactory                 $orderFactory
     * @param HelperShipment               $helperShipment
     * @param Webservice                   $helperWebservice
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param HelperContract               $helperContract
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager,
        OrderFactory $orderFactory,
        HelperShipment $helperShipment,
        Webservice $helperWebservice,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        HelperContract $helperContract
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->orderFactory = $orderFactory;
        $this->helperShipment = $helperShipment;
        $this->helperWebservice = $helperWebservice;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helperContract = $helperContract;
    }

    /**
     * Return label cancellation
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $errors = [];
        $nbDeleted = 0;
        $trackNumber = $this->getRequest()->getParam('track_number');
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderFactory->create()->load($orderId);
        $shipmentIncrementId = $this->getRequest()->getParam('shipment_id');

        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $contract = $this->helperContract->getContractByOrderId($orderId);
            if ($contract) {
                $contractArr = [];
                $contractArr['contract_account_number'] = $contract->getData('contract_account_number');
                $contractArr['contract_account_password'] = $contract->getData('contract_account_password');

                $webservbt = $this->helperWebservice->cancelLabel($trackNumber, $contractArr);
                if ($webservbt) {
                    if ($webservbt->return->errorCode === 0) {
                        $shipmentId = $this->helperShipment->getShipmentIdFromIncrementId($shipmentIncrementId);
                        $returns = $this->helperShipment->getReturnsForShipment($shipmentId);
                        foreach ($returns as $return) {
                            if ($return->getLtNumber() === $trackNumber) {
                                $return->delete();
                                $nbDeleted++;
                            }
                        }
                    } else {
                        switch ($webservbt->return->errorCode) {
                            case 1:
                                $errorMessage = __('A system error has occurred');
                                break;
                            case 2:
                                $errorMessage = __('The parcel’s parameters do not fall within the scope of the' .
                                    ' contract passed or it has not yet been registered in the Chronopost tracking' .
                                    ' system');
                                break;
                            case 3:
                                $errorMessage = __(
                                    'The parcel cannot be cancelled because it has been dispatched by Chronopost'
                                );
                                break;
                            default:
                                $errorMessage = '';
                                break;
                        }

                        $errors[] = __('An error occurred while deleting label %1: %2.', $trackNumber, $errorMessage);
                    }
                } else {
                    $errors[] = __('The parcel’s parameters do not fall within the scope of the' .
                        ' contract passed or it has not yet been registered in the Chronopost tracking' .
                        ' system');
                }
            } else {
                $errors[] = __('Contract not found for order %1', $order->getIncrementId());
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }

        if ($nbDeleted > 1) {
            $this->messageManager->addSuccessMessage(
                __('%1 return shipping labels have been cancelled.', $nbDeleted)
            );
        } elseif ($nbDeleted === 1) {
            $this->messageManager->addSuccessMessage(
                __('%1 return shipping label has been cancelled.', $nbDeleted)
            );
        }

        foreach ($errors as $error) {
            $this->messageManager->addErrorMessage(__($error));
        }

        $resultRedirect->setPath('chronorelais/sales/impression');

        return $resultRedirect;
    }
}
