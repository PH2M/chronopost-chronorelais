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

namespace Chronopost\Chronorelais\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\AddressFactory;
use \Magento\Framework\View\Asset\Repository;
use Chronopost\Chronorelais\Block\Chronorelais;

/**
 * Class GetRelais
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class GetRelais extends Action
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
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * GetRelais constructor.
     *
     * @param Context          $context
     * @param JsonFactory      $jsonFactory
     * @param LayoutFactory    $layoutFactory
     * @param HelperData       $helperData
     * @param HelperWebservice $helperWebservice
     * @param Session          $session
     * @param AddressFactory   $addressFactory
     * @param Repository       $repository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        HelperData $helperData,
        HelperWebservice $helperWebservice,
        Session $session,
        AddressFactory $addressFactory,
        Repository $repository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->helperData = $helperData;
        $this->helperWebservice = $helperWebservice;
        $this->checkoutSession = $session;
        $this->addressFactory = $addressFactory;
        $this->assetRepo = $repository;
    }

    /**
     * Get relay data
     *
     * @return Json
     */
    public function execute()
    {
        $methodCode = $this->getRequest()->getParam('method_code');
        $postcode = $this->getRequest()->getParam('postcode');
        $result = $this->resultJsonFactory->create();

        $resultData = [];

        try {
            $shippingAddressData = $this->getRequest()->getParam('shipping_address');
            $shippingAddress = $this->addressFactory->create()->setData($shippingAddressData);
            if (!$postcode || $postcode == 'false') {
                $postcode = $shippingAddress->getPostcode();
            } else {
                $shippingAddress->setData('postcode', $postcode);
                $shippingAddress->setData('city', 'unknown');
                $shippingAddress->setData('country', 'unknown');
                $shippingAddress->setData('street', 'unknown');
            }

            $relaypoints = $this->helperWebservice->getPointRelaisByAddress($methodCode, $shippingAddress);
            if ($relaypoints) {
                $layout = $this->layoutFactory->create();
                $content = $layout->createBlock(Chronorelais::class)
                    ->setMethodCode($methodCode)
                    ->setCanChangePostcode(
                        $this->helperData->getConfig('carriers/' . $methodCode . '/can_change_postcode')
                    )
                    ->setCanShowMap($this->helperData->getConfig('carriers/' . $methodCode . '/show_map'))
                    ->setPostcode($postcode)
                    ->setRelaypoints($relaypoints)
                    ->setTemplate('Chronopost_Chronorelais::chronorelais.phtml')
                    ->toHtml();

                $resultData['method_code'] = $methodCode;
                $resultData['content'] = $content;
                $resultData['relaypoints'] = $relaypoints;
                $resultData['chronopost_chronorelais_relais_id'] =
                    $this->checkoutSession->getData('chronopost_chronorelais_relais_id');

                $paramsImg = ['_secure' => $this->getRequest()->isSecure()];

                $resultData['relay_icon'] = $this->assetRepo->getUrlWithParams(
                    'Chronopost_Chronorelais::images/relay_icon.png',
                    $paramsImg
                );

                $resultData['home_icon'] = $this->assetRepo->getUrlWithParams(
                    'Chronopost_Chronorelais::images/home_icon.png',
                    $paramsImg
                );

                $resultData['trads'] = [
                    'horaires'     => $this->helperData->getLabelGmap('horaires'),
                    'informations' => $this->helperData->getLabelGmap('informations'),
                    'ferme'        => $this->helperData->getLabelGmap('ferme'),
                    'lundi'        => $this->helperData->getLabelGmap('lundi'),
                    'mardi'        => $this->helperData->getLabelGmap('mardi'),
                    'mercredi'     => $this->helperData->getLabelGmap('mercredi'),
                    'jeudi'        => $this->helperData->getLabelGmap('jeudi'),
                    'vendredi'     => $this->helperData->getLabelGmap('vendredi'),
                    'samedi'       => $this->helperData->getLabelGmap('samedi'),
                    'dimanche'     => $this->helperData->getLabelGmap('dimanche')
                ];
            } else {
                throw new \Exception((string)__('There is no pick-up for this address'));
            }
        } catch (\Exception $e) {
            $resultData['error'] = true;
            $resultData['message'] = $e->getMessage();
        }

        $result->setData($resultData);

        return $result;
    }
}
