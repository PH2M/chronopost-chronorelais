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

namespace Chronopost\Chronorelais\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Chronopost\Chronorelais\Helper\Data as HelperDataChronorelais;
use Chronopost\Chronorelais\Helper\Webservice as HelperWebserviceChronorelais;

/**
 * Class Checklogin
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\System\Config
 */
class Checklogin extends Action
{

    protected $resultJsonFactory;

    /**
     * @var HelperDataChronorelais
     */
    protected $helperData;

    /**
     * @var HelperWebserviceChronorelais
     */
    protected $helperWebservice;

    /**
     * Checklogin constructor.
     *
     * @param Context                      $context
     * @param JsonFactory                  $resultJsonFactory
     * @param HelperDataChronorelais       $helperData
     * @param HelperWebserviceChronorelais $helperWebservice
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperDataChronorelais $helperData,
        HelperWebserviceChronorelais $helperWebservice
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperData = $helperData;
        $this->helperWebservice = $helperWebservice;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return Json
     */
    public function execute()
    {
        $params = $this->_request->getParams();
        $account_number = $params['account_number'];
        $account_pass = $params['account_pass'];

        $result = $this->resultJsonFactory->create();

        try {
            if (!$account_number || !$account_pass) {
                throw new \Exception((string)__('Please enter your account number and password'));
            }

            if (!$this->helperData->getConfig('chronorelais/shipperinformation/country')) {
                throw new \Exception((string)__('Please enter the addresses below'));
            }

            $WSParams = [
                'accountNumber'  => $account_number,
                'password'       => $account_pass,
                'depCountryCode' => $this->helperData->getConfig('chronorelais/shipperinformation/country'),
                'depZipCode'     => $this->helperData->getConfig('chronorelais/shipperinformation/zipcode'),
                'arrCountryCode' => $this->helperData->getConfig('chronorelais/shipperinformation/country'),
                'arrZipCode'     => $this->helperData->getConfig('chronorelais/shipperinformation/zipcode'),
                'arrCity'        => $this->helperData->getConfig('chronorelais/shipperinformation/city'),
                'type'           => 'M',
                'weight'         => 1
            ];

            $webservbt = (array)$this->helperWebservice->checkLogin($WSParams);

            $result->setData($webservbt);
        } catch (\Exception $exception) {
            $result->setData(['return' => ['errorCode' => 1, 'message' => $exception->getMessage()]]);
        }

        return $result;
    }

    /**
     * Check if allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::config_chronorelais');
    }
}
