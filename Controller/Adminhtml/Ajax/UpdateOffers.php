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

namespace Chronopost\Chronorelais\Controller\Adminhtml\Ajax;

use Chronopost\Chronorelais\Model\Config\Source\ChronofreshOffers;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Helper\Webservice as HelperWS;

/**
 * Class UpdateOffers
 *
 * Chronopost\Chronorelais\Controller\Adminhtml\Ajax
 */
class UpdateOffers extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var $helperWS
     */
    protected $helperWS;

    /**
     * @var ChronofreshOffers
     */
    private $chronofreshOffers;

    /**
     * UpdateOffers constructor.
     *
     * @param Context           $context
     * @param JsonFactory       $jsonFactory
     * @param HelperData        $helperData
     * @param HelperWS          $helperWS
     * @param ChronofreshOffers $chronofreshOffers
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        HelperData $helperData,
        HelperWS $helperWS,
        ChronofreshOffers $chronofreshOffers
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->helperData = $helperData;
        $this->helperWS = $helperWS;
        $this->chronofreshOffers = $chronofreshOffers;
        parent::__construct($context);
    }

    /**
     * Check if shipping method is enabled
     *
     * @return Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $html = '';
        $defaultOffer = $this->helperData->getDefaultChronofreshOffer();
        $offers = $this->chronofreshOffers->toOptionArray();
        foreach ($offers as $key => $offer) {
            $isEnable = $this->helperWS->shippingMethodEnabled(
                HelperData::CHRONO_FRESH_CODE,
                (int)$params['contract_id'],
                $key
            );

            if ($isEnable === true) {
                $selected = $defaultOffer === $key ? 'selected' : '';
                $html .= '<option value="' . $key . '" ' . $selected . '>' . $offer . '</option>';
            }
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($html);

        return $result;
    }
}
