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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Bordereau;

use Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression\AbstractImpression;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWS;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Chronopost\Chronorelais\Model\HistoryLtFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Ui\Component\MassAction\Filter;
use Zend_Pdf;
use Zend_Pdf_Color_GrayScale;
use Zend_Pdf_Color_Rgb;
use Zend_Pdf_Font;
use Zend_Pdf_Page;

/**
 * Class PrintBordereau
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Bordereau
 */
class PrintBordereau extends AbstractImpression
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
     * @var HistoryLtFactory
     */
    protected $historyLtFactory;

    /**
     * @var $helperWS
     */
    protected $helperWS;
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * PrintBordereau constructor.
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
     * @param HelperWS          $helperWS
     * @param HistoryLtFactory  $historyLtFactory
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
        HelperWS $helperWS,
        HistoryLtFactory $historyLtFactory
    ) {
        parent::__construct($context, $directoryList, $resultPageFactory, $helperData, $PDFMerger, $messageManager);
        $this->helperData = $helperData;
        $this->collectionFactory = $collectionFactory;
        $this->carrierFactory = $carrierFactory;
        $this->filter = $filter;
        $this->helperWS = $helperWS;
        $this->historyLtFactory = $historyLtFactory;
    }

    /**
     * Print delivery slip
     *
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $weightCoef = $this->helperData->getWeightCoef();

        try {
            $weightNational = 0;
            $nbNational = 0;
            $weightInternational = 0;
            $nbInternational = 0;

            $shipper = [
                'name'     => $this->helperData->getConfig('chronorelais/shipperinformation/name'),
                'address1' => $this->helperData->getConfig('chronorelais/shipperinformation/address1'),
                'address2' => $this->helperData->getConfig('chronorelais/shipperinformation/address2'),
                'city'     => $this->helperData->getConfig('chronorelais/shipperinformation/city'),
                'postcode' => $this->helperData->getConfig('chronorelais/shipperinformation/zipcode'),
                'country'  => $this->helperData->getConfig('chronorelais/shipperinformation/country'),
                'phone'    => $this->helperData->getConfig('chronorelais/shipperinformation/phone')
            ];

            $detail = [];
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection->getItems() as $order) {
                $shippingMethod = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
                $carrier = $this->carrierFactory->get($shippingMethod);
                $productCode = sprintf('Chrono %s', $carrier->getChronoProductCodeToShipmentStr($shippingMethod));
                $contract = $this->helperWS->getContractData($order);

                /** @var Shipment $shipment */
                $shipments = $order->getShipmentsCollection();
                foreach ($shipments as $shipment) {
                    $tracks = $shipment->getAllTracks();
                    foreach ($tracks as $track) {
                        $dlcTrack = null;
                        $weightTrack = 0;

                        $ltHistory = $this->getHistoryFromLt($track->getNumber());
                        if ($ltHistory) {
                            $weightTrack = $ltHistory->getWeight() / $weightCoef;
                            if ($ltHistory->getExpirationDate()) {
                                $expirationDate = new \DateTime($ltHistory->getExpirationDate());
                                $dlcTrack = $expirationDate->format('d/m/Y');
                            }
                        }

                        // Shipping address
                        $address = $shipment->getShippingAddress();
                        if ($address->getCountryId() === 'FR') {
                            $weightNational += $weightTrack;
                            $nbNational++;
                        } else {
                            $weightInternational += $weightTrack;
                            $nbInternational++;
                        }

                        $detail[] = [
                            'trackNumber'  => $track->getNumber(),
                            'numContract'  => $contract['number'],
                            'weight'       => $weightTrack,
                            'product_code' => $productCode,
                            'postcode'     => $address->getPostcode(),
                            'country'      => $address->getCountryId(),
                            'city'         => substr($address->getCity(), 0, 17),
                            'weightLt'     => $weightTrack,
                            'dlc'          => $dlcTrack
                        ];
                    }
                }
            }

            $resume = [
                'NATIONAL'      => ['unite' => $nbNational, 'poids' => $weightNational],
                'INTERNATIONAL' => ['unite' => $nbInternational, 'poids' => $weightInternational],
                'TOTAL'         => [
                    'unite' => ($nbNational + $nbInternational),
                    'poids' => ($weightNational + $weightInternational)
                ]
            ];

            // Create PDF
            $fileName = 'bordereau.pdf';
            $content = $this->getPdfFile($shipper, $detail, $resume);
            $this->prepareDownloadResponse($fileName, $content);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            $resultRedirect->setPath('chronorelais/sales/bordereau');

            return $resultRedirect;
        }
    }

    /**
     * Get history from lt number
     *
     * @param string $number
     *
     * @return mixed
     */
    protected function getHistoryFromLt($number)
    {
        return $this->historyLtFactory->create()->getCollection()
            ->addFieldToFilter('lt_number', $number)
            ->getFirstItem();
    }

    /**
     * Get PDF file
     *
     * @param $shipper
     * @param $detail
     * @param $resume
     *
     * @return string
     * @throws \Zend_Pdf_Exception|NoSuchEntityException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getPdfFile($shipper, $detail, $resume)
    {
        $pdf = new Zend_Pdf();
        $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);

        $minYPosToChangePage = 60;
        $xPos = 20;
        $yPos = $page->getHeight() - 40;
        $lineHeight = 15;

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $fontBold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);

        /* DATE */
        $page->setFont($font, 11);
        $page->drawText(__('date') . ' : ' . date('d/m/Y'), $page->getWidth() - 100, $yPos);
        $yPos -= ($lineHeight);
        $page->setFont($font, 11);

        /* TITLE */
        $page->setFont($fontBold, 11);
        $page->drawText(__('SUMMARY SLIP'), $xPos, $yPos);
        $yPos -= ($lineHeight + 20);
        $page->setFont($font, 11);

        /* EMETTEUR */
        $page->setFont($fontBold, 11);
        $page->drawText(__('TRANSMITTER'), $xPos, $yPos);
        $yPos -= ($lineHeight + 5);

        $page->setFont($font, 11);
        $page->drawText(__('NAME'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['name'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('ADDRESS'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['address1'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('ADDITIONAL ADDRESS'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['address2'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('TOWN/CITY'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['city'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('POSTCODE'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['postcode'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('COUNTRY'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['country'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('TELEPHONE'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText($shipper['phone'], $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        $page->setFont($font, 11);
        $page->drawText(__('ACCOUNTING POST'), $xPos, $yPos);
        $page->setFont($fontBold, 11);
        $page->drawText(substr($shipper['postcode'], 0, 2) . '999', $xPos + 175, $yPos);
        $yPos -= $lineHeight;

        /* DETAILS OF SHIPMENTS */
        $yPos -= 50;
        $page->setFont($fontBold, 11);
        $page->drawText(__('DETAIL OF SHIPMENTS'), $xPos, $yPos);
        $yPos -= ($lineHeight + 5);

        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.85, 0.85, 0.85));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle($xPos, $yPos, 570, $yPos - 20);
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
        $yPos -= 15;

        $page->setFont($font, 10);
        $page->drawText(__('TL Number'), $xPos + 5, $yPos, 'UTF-8');
        $page->drawText(__('Num contrat'), $xPos + 100, $yPos, 'UTF-8');
        $page->drawText(__('Product Code'), $xPos + 175, $yPos);
        $page->drawText(__('Postcode'), $xPos + 260, $yPos);
        $page->drawText(__('Country'), $xPos + 330, $yPos);
        $page->drawText(__('Town/City'), $xPos + 370, $yPos);
        $page->drawText(__('DLC'), $xPos + 440, $yPos);

        $yPos -= 5;
        foreach ($detail as $line) {
            $page->setFillColor(new Zend_Pdf_Color_Rgb(255, 255, 255));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle($xPos, $yPos, 570, $yPos - 20);
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
            $yPos -= 15;

            $page->drawText($line['trackNumber'], $xPos + 5, $yPos, 'UTF-8');
            $page->drawText($line['numContract'], $xPos + 100, $yPos, 'UTF-8');
            $page->drawText($line['product_code'], $xPos + 175, $yPos);
            $page->drawText($line['postcode'], $xPos + 260, $yPos);
            $page->drawText($line['country'], $xPos + 330, $yPos);
            $page->drawText($line['city'], $xPos + 370, $yPos, 'UTF-8');
            $page->drawText($line['dlc'], $xPos + 440, $yPos, 'UTF-8');
            $yPos -= 5;

            if ($yPos <= $minYPosToChangePage) {
                $pdf->pages[] = $page;
                $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
                $page->setFont($font, 11);
                $yPos = $page->getHeight() - 20;
            }
        }

        /* ABSTRACT */
        $yPos -= 50;
        $page->setFont($fontBold, 11);
        $page->drawText(__('SUMMARY'), $xPos, $yPos);
        $yPos -= ($lineHeight + 5);
        $page->setFont($font, 11);

        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.85, 0.85, 0.85));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle($xPos, $yPos, 570, $yPos - 20);
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
        $yPos -= 15;

        $page->setFont($font, 10);
        $page->drawText(__("DESTINATION"), $xPos + 5, $yPos, 'UTF-8');
        $page->drawText(__('UNIT'), $xPos + 170, $yPos);
        $page->drawText(__('TOTAL WEIGHT (kg)'), $xPos + 320, $yPos);
        $yPos -= 5;

        foreach ($resume as $destination => $line) {
            $page->setFillColor(new Zend_Pdf_Color_Rgb(255, 255, 255));
            $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
            $page->setLineWidth(0.5);
            $page->drawRectangle($xPos, $yPos, 570, $yPos - 20);
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
            $yPos -= 15;

            $lineWeight = $line['poids'];

            $page->drawText(__($destination), $xPos + 5, $yPos, 'UTF-8');
            $page->drawText($line['unite'], $xPos + 180, $yPos);
            $page->drawText($lineWeight, $xPos + 340, $yPos);
            $yPos -= 5;
        }

        if ($yPos <= $minYPosToChangePage) {
            $pdf->pages[] = $page;
            $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
            $yPos = $page->getHeight() - 20;
        }

        $yPos -= 50;
        $page->setFont($fontBold, 11);
        $page->drawText(__('Well taken care of %1 package', $resume['TOTAL']['unite']), $xPos, $yPos);

        if ($yPos <= $minYPosToChangePage) {
            $pdf->pages[] = $page;
            $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
            $yPos = $page->getHeight() - 20;
        }

        /* SIGNATURES */
        $yPos -= 60;
        $page->setFont($font, 11);
        $page->drawText(__("Client's signature"), $xPos, $yPos);
        $page->drawText(__('Signature of Chronopost Messenger'), 400, $yPos);

        $pdf->pages[] = $page;

        return $pdf->render();
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
