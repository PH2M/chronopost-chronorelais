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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Export;

use Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression\AbstractImpression;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Shipment;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use DateTime;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class Export
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Export
 */
class Export extends AbstractImpression
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Shipment
     */
    private $helperShipment;

    /**
     * Export constructor.
     *
     * @param Context           $context
     * @param DirectoryList     $directoryList
     * @param PageFactory       $resultPageFactory
     * @param HelperData        $helperData
     * @param PDFMerger         $PDFMerger
     * @param ManagerInterface  $messageManager
     * @param CollectionFactory $collectionFactory
     * @param CarrierFactory    $carrierFactory
     * @param Filter            $filter
     * @param Shipment          $helperShipment
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager,
        CollectionFactory $collectionFactory,
        CarrierFactory $carrierFactory,
        Filter $filter,
        Shipment $helperShipment
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->collectionFactory = $collectionFactory;
        $this->carrierFactory = $carrierFactory;
        $this->filter = $filter;
        $this->helperShipment = $helperShipment;
    }

    /**
     * Export action
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $format = $this->getRequest()->getParam('format');
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->export($collection, $format);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/export');

            return $resultRedirect;
        }
    }

    /**
     * Export action
     *
     * @param CollectionFactory $collection
     * @param string            $format
     *
     * @return Export
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function export($collection, $format = 'css')
    {
        $separator = $this->helperData->getConfig('chronorelais/export_' . $format . '/field_separator');
        $delimiter = $this->helperData->getConfig('chronorelais/export_' . $format . '/field_delimiter');

        if ($delimiter === 'simple_quote') {
            $delimiter = "'";
        } elseif ($delimiter === 'double_quotes') {
            $delimiter = '"';
        } else {
            $delimiter = '';
        }

        $lineBreak = $this->helperData->getConfig('chronorelais/export_' . $format . '/endofline_character');
        if ($lineBreak === 'lf') {
            $lineBreak = "\n";
        } elseif ($lineBreak === 'cr') {
            $lineBreak = "\r";
        } elseif ($lineBreak === 'crlf') {
            $lineBreak = "\r\n";
        }

        $fileExtension = $this->helperData->getConfig('chronorelais/export_' . $format . '/file_extension');
        $fileCharset = $this->helperData->getConfig('chronorelais/export_' . $format . '/file_charset');
        $filename = 'orders_export' . $format . '_' . date('Ymd_His') . $fileExtension;

        // Initialize the content variable
        $content = '';
        $weightUnit = $this->helperData->getChronoWeightUnit();
        foreach ($collection->getItems() as $order) {
            $shipments = $order->getShipmentsCollection();
            foreach ($shipments as $shipment) {
                $historyTracks = $this->helperShipment->getTrackingForShipment($shipment->getId());
                foreach ($historyTracks as $historyTrack) {
                    $address = $order->getShippingAddress();
                    $billingAddress = $order->getBillingAddress();
                    $shippingMethod = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
                    $carrier = $this->carrierFactory->get($shippingMethod);

                    // Customer ID
                    $content = $this->_addFieldToCsv(
                        $content,
                        $delimiter,
                        ($order->getCustomerId() ?: $address->getLastname())
                    );
                    $content .= $separator;

                    // Name of relay point OR company if home delivery
                    $content = $this->_addFieldToCsv($content, $delimiter, $address->getCompany());
                    $content .= $separator;

                    // Customer name
                    $content = $this->_addFieldToCsv(
                        $content,
                        $delimiter,
                        ($address->getFirstname() ?: $billingAddress->getFirstname())
                    );
                    $content .= $separator;
                    $content = $this->_addFieldToCsv(
                        $content,
                        $delimiter,
                        ($address->getLastname() ?: $billingAddress->getLastname())
                    );
                    $content .= $separator;

                    // Street address
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getStreetLine(1)));
                    $content .= $separator;
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getStreetLine(2)));
                    $content .= $separator;

                    // Digital code
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Postcode
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getPostcode()));
                    $content .= $separator;

                    // City
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getCity()));
                    $content .= $separator;

                    // Country code
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($address->getCountryId()));
                    $content .= $separator;

                    // Telephone
                    $telephone = '';
                    if ($address->getTelephone()) {
                        $telephone = trim(preg_replace('[^0-9.-]', ' ', $address->getTelephone()));
                        $telephone = (strlen($telephone) >= 10 ? $telephone : '');
                    }

                    $content = $this->_addFieldToCsv($content, $delimiter, $telephone);
                    $content .= $separator;

                    // Email
                    $customer_email = $address->getEmail() ?: $billingAddress->getEmail() ?: $order->getCustomerEmail();
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($customer_email));
                    $content .= $separator;

                    // Real order ID
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($order->getRealOrderId()));
                    $content .= $separator;

                    // EAN (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Product code
                    $productCode = $carrier->getChronoProductCodeStr($shippingMethod);
                    $content = $this->_addFieldToCsv($content, $delimiter, $productCode);
                    $content .= $separator;

                    // Account (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Sub Account (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Empty fields
                    $content = $this->_addFieldToCsv($content, $delimiter, 0);
                    $content .= $separator;
                    $content = $this->_addFieldToCsv($content, $delimiter, 0);
                    $content .= $separator;

                    // document / marchandise (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Content description (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Saturday delivery
                    $saturdayShipping = 'L'; //default value for the saturday shipping
                    if ($carrier->canDeliverOnSaturday()) {
                        $deliveryOnSaturday = $this->helperData->getShippingSaturdayStatus($order->getId());
                        if (!$deliveryOnSaturday) {
                            $deliveryOnSaturday = (bool)$this->helperData->getConfig(
                                'carriers/' . $carrier->getCarrierCode() . '/deliver_on_saturday'
                            );
                        } elseif ($deliveryOnSaturday === 'Yes') {
                            $deliveryOnSaturday = true;
                        } else {
                            $deliveryOnSaturday = false;
                        }

                        $isSendingDay = $this->helperData->isSendingDay();
                        if ($deliveryOnSaturday === true && $isSendingDay === true) {
                            $saturdayShipping = 'S';
                        } elseif ($isSendingDay === true) {
                            $saturdayShipping = 'L';
                        }
                    }

                    $content = $this->_addFieldToCsv($content, $delimiter, $saturdayShipping);
                    $content .= $separator;

                    // Chronorelay point
                    $content = $this->_addFieldToCsv($content, $delimiter, $this->getValue($order->getRelaisId()));
                    $content .= $separator;

                    // Total weight (in kg)
                    $orderWeight = number_format((float)$order->getWeight(), 2, '.', '');
                    if ($weightUnit === 'g') {
                        $orderWeight = $this->helperData->getConvertedWeight((float)$orderWeight);
                    }

                    $content = $this->_addFieldToCsv($content, $delimiter, $orderWeight);
                    $content .= $separator;

                    // Width (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Length (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Height (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Notify recipient (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Parcel number (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Date
                    $content = $this->_addFieldToCsv($content, $delimiter, date('d/m/Y'));
                    $content .= $separator;

                    // To integrate (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    // Notify sender (empty)
                    $content = $this->_addFieldToCsv($content, $delimiter, '');
                    $content .= $separator;

                    /* DLC */
                    $dlc = '';
                    if ($historyTrack->getExpirationDate()) {
                        $dlc = $this->helperData->getFormattedExpirationDate($historyTrack->getExpirationDate(), 'd/m/Y');
                    }

                    $content = $this->_addFieldToCsv($content, $delimiter, $dlc);
                    $content .= $separator;

                    // Appointment specific fields
                    $chronopostsrdvSlotInfo = $order->getData('chronopostsrdv_creneaux_info');
                    if ($chronopostsrdvSlotInfo) {
                        $chronopostsrdvSlotInfo = json_decode($chronopostsrdvSlotInfo, true);
                        $dateRdvStart = new DateTime($chronopostsrdvSlotInfo['deliveryDate']);
                        $dateRdvStart->setTime(
                            (int)$chronopostsrdvSlotInfo['startHour'],
                            (int)$chronopostsrdvSlotInfo['startMinutes']
                        );

                        $dateRdvEnd = new DateTime($chronopostsrdvSlotInfo['deliveryDate']);
                        $dateRdvEnd->setTime(
                            (int)$chronopostsrdvSlotInfo['endHour'],
                            (int)$chronopostsrdvSlotInfo['endMinutes']
                        );

                        $content = $this->_addFieldToCsv($content, $delimiter, $dateRdvStart->format("dmyHi"));
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, $dateRdvEnd->format("dmyHi"));
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, $chronopostsrdvSlotInfo['tariffLevel']);
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, $chronopostsrdvSlotInfo['serviceCode']);
                        $content .= $separator;
                    } else {
                        $content = $this->_addFieldToCsv($content, $delimiter, '');
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, '');
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, '');
                        $content .= $separator;

                        $content = $this->_addFieldToCsv($content, $delimiter, '');
                        $content .= $separator;
                    }

                    $content .= $lineBreak;
                }
            }
        }

        // Decode the content, depending on the charset
        if ($fileCharset === 'ISO-8859-1') {
            $content = utf8_decode($content);
        }

        // Pick file mime type, depending on the extension
        switch ($fileExtension) {
            case '.csv':
                $fileMimeType = 'application/csv';
                break;
            case '.chr':
                $fileMimeType = 'application/chr';
                break;
            default:
                $fileMimeType = 'text/plain';
                break;
        }

        return $this->prepareDownloadResponse($filename, $content, $fileMimeType . '; charset="' . $fileCharset . '"');
    }

    /**
     * Add a new field to the csv file
     *
     * @param $csvContent
     * @param $fieldDelimiter
     * @param $fieldContent
     *
     * @return string : the concatenation of current content and content to add
     */
    private function _addFieldToCsv($csvContent, $fieldDelimiter, $fieldContent)
    {
        return $csvContent . $fieldDelimiter . $fieldContent . $fieldDelimiter;
    }

    /**
     * Get value formatted
     *
     * @param $value
     *
     * @return string
     * @deprecated since 2.0.0
     */
    public function getValue($value)
    {
        return $value;
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
