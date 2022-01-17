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
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\OrderFactory;

/**
 * Class GenerateLabel
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 * @SuppressWarnings("CouplingBetweenObjects")
 */
class GenerateLabel extends AbstractImpression
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
     * @var ShipmentRepository
     */
    private $shipmentRepository;

    /**
     * @var HelperContract
     */
    private $helperContract;

    /**
     * GenerateLabel constructor.
     *
     * @param Context            $context
     * @param DirectoryList      $directoryList
     * @param PageFactory        $resultPageFactory
     * @param HelperData         $helperData
     * @param PDFMerger          $PDFMerger
     * @param ManagerInterface   $messageManager
     * @param HelperShipment     $helperShipment
     * @param OrderFactory       $orderFactory
     * @param ShipmentRepository $shipmentRepository
     * @param HelperContract     $helperContract
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager,
        HelperShipment $helperShipment,
        OrderFactory $orderFactory,
        ShipmentRepository $shipmentRepository,
        HelperContract $helperContract
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->helperShipment = $helperShipment;
        $this->orderFactory = $orderFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->helperContract = $helperContract;
    }

    /**
     * Generate label
     *
     * @return Redirect
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $offer = $this->getRequest()->getParam('offer');
            $contractId = $this->getRequest()->getParam('contract');
            $packageNumber = (int)$this->getRequest()->getParam('nb_colis');
            $orderId = $this->getRequest()->getParam('order_id');
            $expirationDate = $this->getRequest()->getParam('expiration_date');

            if ($orderId) {
                $order = $this->orderFactory->create()->load($orderId);
                if ($order && $order->getId()) {
                    $shippingMethodCode = $this->helperData->getShippingMethodeCode($order->getShippingMethod());

                    if (!$this->helperData->isChronoMethod($shippingMethodCode)) {
                        throw new \Exception(
                            (string)__('Delivery option not Chronopost for order %1', $order->getIncrementId())
                        );
                    }

                    $dimensions = json_decode($this->getRequest()->getParam('dimensions'), true);

                    $expirationDate = $this->helperData->validateExpirationDate($expirationDate, $offer);
                    if ($expirationDate === false) {
                        $this->messageManager->addErrorMessage(
                            __('You cannot ship merchandise with an expiration date of less than 3 days.')
                        );
                        $resultRedirect->setPath('chronorelais/sales/impression');

                        return $resultRedirect;
                    }

                    $count = count($dimensions);
                    for ($ite = 0; $ite < $count; $ite++) {
                        $msg = [];
                        $error = false;

                        $dimensionsLimit = $dimensions[$ite];
                        $weightLimit = $this->helperData->getWeightLimit($shippingMethodCode);
                        $dimLimit = $this->helperData->getInputDimensionsLimit($shippingMethodCode);
                        $globalLimit = $this->helperData->getGlobalDimensionsLimit($shippingMethodCode);

                        if (isset($dimensionsLimit['weight']) && $dimensionsLimit['weight'] > $weightLimit) {
                            $msg[] = __(
                                'One or several packages are above the weight limit (%1 kg)',
                                $weightLimit / $this->helperData->getWeightCoef()
                            );
                            $error = true;
                        }

                        if (isset($dimensionsLimit['width']) && $dimensionsLimit['width'] > $dimLimit) {
                            $msg[] = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                            $error = true;
                        }

                        if (isset($dimensionsLimit['height']) && $dimensionsLimit['height'] > $dimLimit) {
                            $msg[] = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                            $error = true;
                        }

                        if (isset($dimensionsLimit['length']) && $dimensionsLimit['length'] > $dimLimit) {
                            $msg[] = __('One or several packages are above the size limit (%1 cm)', $dimLimit);
                            $error = true;
                        }

                        if (isset($dimensionsLimit['height'], $dimensionsLimit['width'], $dimensionsLimit['length'])) {
                            $global = 2 * $dimensionsLimit['height'] + $dimensionsLimit['width'] + 2 *
                                $dimensionsLimit['length'];
                            if ($global > $globalLimit) {
                                $msg[] = __(
                                    'One or several packages are above the total (L+2H+2l) size limit (%1 cm)',
                                    $globalLimit
                                );
                                $error = true;
                            }
                        }

                        if ($error) {
                            $this->messageManager->addErrorMessage(__(implode('\n', $msg)));
                            $resultRedirect->setPath('chronorelais/sales/impression');

                            return $resultRedirect;
                        }
                    }

                    $shipments = $order->getShipmentsCollection();
                    if ($shipments->count()) {
                        $shipmentId = $this->_request->getParam('shipment_id');
                        if ($shipmentId) {
                            $shipmentId = $this->helperShipment->getShipmentIdFromIncrementId($shipmentId);
                            $shipment = $this->shipmentRepository->get($shipmentId);
                        } else {
                            $shipment = $shipments->getFirstItem();
                        }

                        if ($contractId === null) {
                            // Shipment exist so contract too
                            $contractFromOrder = $this->helperContract->getContractByOrderId($order->getId());
                            $contract = $this->helperContract->getContractByNumber(
                                $contractFromOrder->getContractAccountNumber()
                            );

                            if ($contract) {
                                $contractId = $contract['contract_id'];
                            }
                        }

                        if ($contractId !== null) {
                            $this->createTracksWithNumber(
                                $shipment,
                                $packageNumber,
                                (int)$contractId,
                                $dimensions,
                                $expirationDate,
                                $offer
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __('Contract not found for order %1', $order->getIncrementId())
                            );
                            $resultRedirect->setPath('chronorelais/sales/impression');

                            return $resultRedirect;
                        }
                    } else {
                        $this->helperShipment->createNewShipment(
                            $order,
                            [],
                            [],
                            $dimensions,
                            $packageNumber,
                            false,
                            (int)$contractId,
                            $expirationDate,
                            $offer
                        );
                    }
                }
            }

            $this->messageManager->addSuccessMessage(__('Labels are correctly generated'));
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }
    }

    /**
     * Create tracks with number
     *
     * @param Shipment    $shipment
     * @param int         $nbColis
     * @param int         $contractId
     * @param array       $dimensions
     * @param string|null $expirationDate
     * @param string|null $offer
     *
     * @throws NoSuchEntityException
     * @throws \SoapFault
     */
    private function createTracksWithNumber(
        Shipment $shipment,
        int $nbColis,
        int $contractId,
        array $dimensions,
        $expirationDate = null,
        $offer = null
    ) {
        $trackData = $shipment->getTrackData() ?: [];

        $shipment = $shipment->loadByIncrementId($shipment->getIncrementId());

        $this->helperShipment->createTrackToShipment(
            $shipment,
            $trackData,
            $dimensions,
            $nbColis,
            $contractId,
            null,
            $expirationDate,
            $offer
        );
    }
}
