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
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\OrderFactory;
use Zend_Mail;
use Zend_Mime;

/**
 * Class PrintLabelReturn
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 */
class PrintLabelReturn extends AbstractImpression
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
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * @var HelperData
     */
    private $helperContract;

    /**
     * PrintLabelReturn constructor.
     *
     * @param Context          $context
     * @param DirectoryList    $directoryList
     * @param PageFactory      $resultPageFactory
     * @param HelperData       $helperData
     * @param PDFMerger        $PDFMerger
     * @param ManagerInterface $messageManager
     * @param HelperShipment   $helperShipment
     * @param OrderFactory     $orderFactory
     * @param HelperWebservice $helperWebservice
     * @param HelperContract   $helperContract
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
        HelperWebservice $helperWebservice,
        HelperContract $helperContract
    ) {
        parent::__construct(
            $context,
            $directoryList,
            $resultPageFactory,
            $helperData,
            $PDFMerger,
            $messageManager
        );
        $this->helperShipment = $helperShipment;
        $this->orderFactory = $orderFactory;
        $this->helperWebservice = $helperWebservice;
        $this->helperContract = $helperContract;
    }

    /**
     * Print return label
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $shipmentIncrementId = $this->getRequest()->getParam('shipment_increment_id');

        $shipment = $this->helperShipment->getShipmentByIncrementId($shipmentIncrementId);
        $order = $shipment->getOrder();

        $shippingAddress = $shipment->getShippingAddress();
        $billingAddress = $shipment->getBillingAddress();
        $shippingCountryId = $shippingAddress->getCountryId();

        // Check if return is allowed for current shipping method
        $shippingMethod = $this->helperData->getShippingMethodeCode($order->getShippingMethod());
        $shippingMethodsAllowed = HelperData::SHIPPING_METHODS_RETURN_ALLOWED;
        if (!in_array($shippingMethod, $shippingMethodsAllowed)) {
            $this->messageManager->addErrorMessage(
                __('Returns are not available for the delivery option %1', $shippingMethod)
            );
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }

        // Check if return is authorized
        if (!$this->helperData->returnAuthorized($shippingCountryId, $shippingMethod)) {
            $this->messageManager->addErrorMessage(
                __('Return labels are not available for this country: %1', $shippingCountryId)
            );
            $resultRedirect->setPath('chronorelais/sales/impression');

            return $resultRedirect;
        }

        try {
            $contractFromOrder = $this->helperContract->getContractByOrderId($order->getId());
            $contract = $this->helperContract->getContractByNumber(
                $contractFromOrder->getContractAccountNumber()
            );

            if (count($contract)) {
                $label = $this->helperWebservice->createLabel(
                    $shipment,
                    'return',
                    $this->getRequest()->getParam('recipient_address_type'),
                    [],
                    1,
                    (int)$contract['contract_id']
                );

                if ($label) {
                    $this->helperShipment->saveLtHistory(
                        (string)$shipment->getId(),
                        (string)$label->return->resultParcelValue->skybillNumber,
                        (float)$shipment->getTotalWeight(),
                        $label->return->reservationNumber,
                        HelperShipment::HISTORY_TYPE_RETURN
                    );

                    $productCode = $this->helperWebservice->getReturnProductCode($shippingAddress, $shippingMethod);
                    if ($productCode !== HelperData::CHRONOPOST_REVERSE_T) {
                        $reservationNumber = $label->return->reservationNumber;
                        $pdf = $this->helperWebservice->getLabelByReservationNumber(
                            $reservationNumber,
                            $shippingMethod,
                            'return',
                            $shippingAddress
                        );
                        $path = $this->savePdfWithContent(base64_decode($pdf), $shipment->getId());

                        // send mail with pdf
                        $messageEmail = __('Hello, <br />You will soon be using Chronopost to send an item. ' .
                            'The person who sent you this email has already prepared the waybill that you will use. ' .
                            'Once it has been printed, put the waybill into an adhesive pouch and affix it to your ' .
                            'shipment. Make sure the barcode is clearly visible.<br />Kind regards,');

                        $billingEmail = $billingAddress->getEmail();
                        $shippingEmail = $shippingAddress->getEmail();
                        $orderEmail = $order->getCustomerEmail();
                        $customerEmail = $shippingEmail ?: $billingEmail ?: $orderEmail;
                        $recipientEmail = $this->helperData->getConfig('contact/email/recipient_email');

                        $mail = new Zend_Mail('utf-8');
                        $mail->setType('multipart/alternative');
                        $mail->setBodyHtml($messageEmail);
                        $mail->setFrom($recipientEmail);
                        $mail->setSubject(__('%1: Chronopost return label', $order->getStoreName()));
                        $mail->createAttachment(
                            file_get_contents($path),
                            Zend_Mime::TYPE_OCTETSTREAM,
                            Zend_Mime::DISPOSITION_ATTACHMENT,
                            Zend_Mime::ENCODING_BASE64,
                            'etiquette_retour.pdf'
                        );
                        $mail->addTo($customerEmail);
                        $mail->send();

                        $mail->clearRecipients();
                        $mail->addTo($recipientEmail);
                        $mail->send();
                    }

                    $this->messageManager->addSuccessMessage(__('The return label has been sent to the customer.'));
                }
            } else {
                $this->messageManager->addErrorMessage(
                    __('Contract not found for order %1', $order->getIncrementId())
                );
            }
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
        }

        $resultRedirect->setPath('chronorelais/sales/impression');

        return $resultRedirect;
    }

    /**
     * Save PDF with content
     *
     * @param string $content
     * @param string $shipmentId
     *
     * @return string
     * @throws FileSystemException
     */
    protected function savePdfWithContent(string $content, string $shipmentId)
    {
        $this->createMediaChronopostFolder();

        $path = $this->directoryList->getPath('media') . '/chronopost';
        $path .= '/etiquetteRetour-' . $shipmentId . '.pdf';
        file_put_contents($path, $content);

        return $path;
    }
}
