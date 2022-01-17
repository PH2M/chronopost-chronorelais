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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Import;

use Chronopost\Chronorelais\Helper\Shipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment as HelperShipment;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\File\Csv;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request as RequestPhp;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Sales\Model\Order\Shipment as OrderShipment;

/**
 * Class Save
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Import
 */
class Save extends Action
{

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperShipment
     */
    protected $helperShipment;

    /**
     * @var Csv
     */
    protected $fileCsv;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var RequestPhp
     */
    protected $requestPhp;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var OrderShipment
     */
    protected $shipment;

    /**
     * @param Context         $context
     * @param HelperShipment  $helperShipment
     * @param HelperData      $helperData
     * @param Csv             $csv
     * @param OrderFactory    $orderFactory
     * @param RequestPhp      $requestPhp
     * @param WriterInterface $configWriter
     * @param Shipment        $shipment
     */
    public function __construct(
        Context $context,
        HelperShipment $helperShipment,
        HelperData $helperData,
        Csv $csv,
        OrderFactory $orderFactory,
        RequestPhp $requestPhp,
        WriterInterface $configWriter,
        Shipment $shipment
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->helperShipment = $helperShipment;
        $this->fileCsv = $csv;
        $this->orderFactory = $orderFactory;
        $this->requestPhp = $requestPhp;
        $this->configWriter = $configWriter;
        $this->shipment = $shipment;
    }

    /**
     * Save import action
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $chronoFile = $this->requestPhp->getFiles('import_chronorelais_file');

            if ($this->getRequest()->getParams() && $chronoFile && !empty($chronoFile['tmp_name'])) {
                $trackingTitle = $this->requestPhp->getPost('import_chronorelais_tracking_title');
                $numberColumnParcel = $this->requestPhp->getPost('import_chronorelais_column_parcel');
                $numberColumnOrder = $this->requestPhp->getPost('import_chronorelais_column_order');

                if (!$trackingTitle) {
                    throw new \Exception((string)__('Please enter a title for the tracking'));
                }

                if ($numberColumnParcel === null || $numberColumnParcel === '' || !is_numeric($numberColumnParcel)) {
                    throw new \Exception((string)__('Please fill in a column number containing the package number.'));
                }

                if ($numberColumnOrder === null || $numberColumnOrder === '' || !is_numeric($numberColumnOrder)) {
                    throw new \Exception(
                        (string)__('Please fill in a column number containing the order number.')
                    );
                }

                $this->configWriter->save('chronopost/import/number_column_parcel', $numberColumnParcel);
                $this->configWriter->save('chronopost/import/number_column_order', $numberColumnOrder);

                $numberColumnParcel--;
                $numberColumnOrder--;

                if ($this->importChronorelaisFile(
                    $chronoFile['tmp_name'],
                    $trackingTitle,
                    $numberColumnParcel,
                    $numberColumnOrder
                )) {
                    $this->messageManager->addSuccessMessage(__('The parcels have been imported'));
                }
            } else {
                throw new \Exception((string)__('Please select a file'));
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
        }

        $resultRedirect->setPath('chronorelais/sales/import');

        return $resultRedirect;
    }

    /**
     * Import file
     *
     * @param string $fileName
     * @param string $trackingTitle
     * @param string $numberColumnParcel
     * @param string $numberColumnOrder
     *
     * @return bool
     * @throws \Exception
     * @SuppressWarnings("Unused")
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function importChronorelaisFile($fileName, $trackingTitle, $numberColumnParcel, $numberColumnOrder)
    {
        ini_set('auto_detect_line_endings', '1');
        $sendEmail = $this->helperData->getConfig('chronorelais/import/send_mail');
        $comment = $this->helperData->getConfig('chronorelais/import/shipping_comment');
        $includeComment = $this->helperData->getConfig('chronorelais/import/include_comment');

        $csvData = $this->fileCsv->setDelimiter(';')->getData($fileName);
        foreach ($csvData as $key => $data) {
            if (!is_numeric($data[$numberColumnOrder])) {
                continue;
            }

            if (count($data) <= 1 && $data[0] === '') {
                continue;
            }

            if (!isset($data[$numberColumnOrder], $data[$numberColumnParcel])) {
                continue;
            }

            $orderId = $data[$numberColumnOrder];
            $trackingNumbers = $data[$numberColumnParcel];

            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            if (!$order->getId()) {
                $this->messageManager->addErrorMessage(__('The order %1 does not exist', $orderId));
                continue;
            }

            try {
                $shippingMethod = $this->helperData->getShippingMethodeCode($order->getShippingMethod());

                $popup = 0;
                $carrierCode = 'custom';
                if (!$this->helperData->isChronoMethod($shippingMethod)) {
                    $carrierCode = $shippingMethod;
                    $popup = 1;
                }

                $shipmentCreated = false;
                if ($trackingNumbers) {
                    $trackingNumbers = explode(',', trim($trackingNumbers, '[]'));
                    foreach ($trackingNumbers as $trackingNumber) {
                        $shipmentsCollection = $order->getShipmentsCollection();
                        $trackingNumber = trim($trackingNumber);
                        $trackData = [
                            'track_number'    => $trackingNumber,
                            'carrier'         => ucwords($carrierCode),
                            'carrier_code'    => $carrierCode,
                            'title'           => $trackingTitle,
                            'popup'           => $popup,
                            'send_mail'       => $sendEmail,
                            'comment'         => $comment,
                            'include_comment' => $includeComment
                        ];

                        if ($shipmentCreated || count($shipmentsCollection) > 0) {
                            if (!$shipmentCreated) {
                                $this->shipment = $shipmentsCollection->getFirstItem();
                            }

                            $this->helperShipment->createTrackToShipment($this->shipment, $trackData, []);
                        } else {
                            $this->shipment = $this->helperShipment->createNewShipment(
                                $order,
                                [],
                                $trackData,
                                [],
                                1,
                                true
                            );
                            $shipmentCreated = true;
                        }

                        $this->messageManager->addSuccessMessage(
                            __(
                                'The shipment with the number %1 has been created for order %2',
                                $trackingNumber,
                                $orderId
                            )
                        );
                    }
                }
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }

        return $this->messageManager->getMessages()->getCount() === 0;
    }

    /**
     * Check is the current user is allowed to access this section
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }
}
