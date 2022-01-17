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
 * Class SetSessionRelais
 *
 * @package Chronopost\Chronorelais\Controller\Ajax
 */
class SetSessionRelais extends Action
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
     * SetSessionRelais constructor.
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
     * Reset value session
     *
     * @return Json
     */
    public function execute()
    {
        $relaisId = $this->getRequest()->getParam('relais_id');

        try {
            $relais = $this->helperWebservice->getDetailRelaisPoint($relaisId);
            if ($relais) {
                $relaisidbefore = $this->checkoutSession->getData('chronopost_chronorelais_relais_id');
                $this->checkoutSession->setData('chronopost_chronorelais_relais_id', $relaisId);
                $relaisidafter = $this->checkoutSession->getData('chronopost_chronorelais_relais_id');

                if (isset($relais->nom)) {
                    $nom = $relais->nom;
                } else {
                    $nom = $relais->nomEnseigne;
                }

                $data = [
                    'success'          => true,
                    'relais_id_before' => $relaisidbefore,
                    'relais_id_after'  => $relaisidafter,
                    'relais' => [
                        'city'              => $relais->localite,
                        'postcode'          => $relais->codePostal,
                        'street'            => [$relais->adresse1, $relais->adresse2, $relais->adresse3],
                        'company'           => $nom,
                        'saveInAddressBook' => 0,
                        'sameAsBilling' => 0
                    ]
                ];
            } else {
                $data = ['error' => true, 'message' => __('The pick-up point does not exist.')];
            }
        } catch (\Exception $exception) {
            $data = ['error' => true, 'message' => __($exception->getMessage())];
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($data);

        return $result;
    }
}
