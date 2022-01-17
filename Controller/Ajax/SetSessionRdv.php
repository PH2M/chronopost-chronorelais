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
use Magento\Checkout\Model\Session as CheckoutSession;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebservice;

/**
 * Class SetSessionRdv
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class SetSessionRdv extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var HelperWebservice
     */
    protected $helperWebservice;

    /**
     * SetSessionRdv constructor.
     *
     * @param Context          $context
     * @param JsonFactory      $jsonFactory
     * @param CheckoutSession  $checkoutSession
     * @param HelperWebservice $webservice
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        HelperWebservice $webservice
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->helperWebservice = $webservice;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute()
    {
        $chronopostsrdvSlotsInfo = $this->getRequest()->getParam('chronopostsrdv_creneaux_info');

        try {
            $confirm = $this->helperWebservice->confirmDeliverySlot($chronopostsrdvSlotsInfo);
            if ($confirm->return->code === 0) {
                $this->checkoutSession->setData(
                    'chronopostsrdv_creneaux_info',
                    json_encode($chronopostsrdvSlotsInfo)
                );

                $dateRdv = new \DateTime($chronopostsrdvSlotsInfo['deliveryDate']);
                $dateRdv = $dateRdv->format('d/m/Y');

                $heureDebut = $chronopostsrdvSlotsInfo['startHour'] . ':' . str_pad(
                    $chronopostsrdvSlotsInfo['startMinutes'],
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $heureFin = $chronopostsrdvSlotsInfo['endHour'] . ':' . str_pad(
                    $chronopostsrdvSlotsInfo['endMinutes'],
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $data = [
                    'success' => true,
                    'rdvInfo' => ' - ' . __('On %1 between %2 and %3', $dateRdv, $heureDebut, $heureFin)
                ];
            } else {
                $data = ['error' => true, 'message' => __($confirm->return->message)];
            }
        } catch (\Exception $e) {
            $data = ['error' => true, 'message' => __($e->getMessage())];
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($data);

        return $result;
    }
}
