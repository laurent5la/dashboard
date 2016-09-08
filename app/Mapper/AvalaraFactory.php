<?php
namespace App\Mapper;

use App\Lib\Dashboard\Avalara\AvalaraClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Mapper\LogFactory;
use App\Models\Helpers\AvalaraHelper;

use Config;

/**
 * Class AvalaraFactory
 *
 * This class is used to create request for the Avalara Endpoints
 *
 * @package App\Mapper
 */
class AvalaraFactory extends AvalaraClient
{
    private $config;
    private $client;
    private $avalaraClient;
    private $logMessage = array();

    /**
     * Class Constructor
     * Get avalara configurations , Guzzle Client
     * @param
     * @return
     * @use App\Lib\ECart\Avalara\AvalaraClient;
     * @author Kunal
     */
    public function __construct()
    {
        $this->config = app()['config'];
        $this->client = new client();
        $this->avalaraClient = new AvalaraClient();
        $this->logFactory = new LogFactory();
    }

    /**
     * Returns the validated address from the Get Request to the Avalara endpoints extended from AvalaraClient
     *
     * @param Array avalara parameters
     * @return Array $avalaraValidatedAddress validated address from avalara
     * @use App\Lib\ECart\Avalara\AvalaraClient;
     * @author Kunal
     */
    public function getValidAddress($avalaraParams)
    {
        $avalaraAddressValidateURL = env('AVALARA_ADDRESS_VALIDATION_BASE_URL');
        $avalaraValidatedAddress = $this->avalaraClient->avalaraGetRequest($avalaraAddressValidateURL, $avalaraParams);
        return $avalaraValidatedAddress;
    }

    /**
     * Returns the calculated tax returned from the Get Request to the Avalara endpoints extended from AvalaraClient
     *
     * @param Array avalara parameters
     * @return Array $calculatedTaxResponse
     * @use App\Lib\ECart\Avalara\AvalaraClient;
     * @author Kunal
     */
    public function getTaxInfo($avalaraParams)
    {
        $avalaraTaxURL = env('AVALARA_TAX_CALCULATION_BASE_URL');
        $taxResponse = $this->avalaraClient->avalaraPostRequest($avalaraTaxURL, $avalaraParams);
        $avalaraSuccessFlag = false;
        if($taxResponse['ResultCode'] == 'Success')
        {
            $avalaraSuccessFlag = true;
        }
        else
        {
            $avalaraSuccessFlag = true;
            //retry to make tax call one more time
            $taxResponse = $this->avalaraClient->avalaraPostRequest($avalaraTaxURL, $avalaraParams);
            if($taxResponse['ResultCode'] == 'Success')
                $avalaraSuccessFlag = true;
            else
            {
                $avalaraSuccessFlag = false;
            }
        }

        if(!$avalaraSuccessFlag)
        {
            $calculatedTaxResponse = array(
                'status' => 0,
                'response' => array(
                    'TotalTax' => 0,
                    'TaxCode'  => "P0000000",
                    'TaxRate'  => 0
                ),
            );
            $this->logMessage['AvalaraFactory->getTaxInfo']['Tax_Error'] = $taxResponse;
            $this->logFactory->writeAvalaraLog($this->logMessage);
        }
        else
        {
            /**
             *If Total Tax is 0
             *  If Taxable Amount == Total Exemption and Tax Rate != 0
             *      Then the customer is exempted from the Tax.
             *  Else
             *      Taxable Amount is 0 for other reason. Example: CA is no tax state.
             */

            if(isset($taxResponse['TotalTax']))
            {
                $totalTax = (float)$taxResponse['TotalTax'];

                if($totalTax === 0.0)
                {
                    $avalaraRequest = json_decode($avalaraParams,true);
                    $customerID = isset($avalaraRequest['CustomerCode']) ? $avalaraRequest['CustomerCode']:'';
                    $customerState  = isset($avalaraRequest['Addresses'][0]['Region']) ? $avalaraRequest['Addresses'][0]['Region']:'';

                    $avalaraHelperObject = new AvalaraHelper();

                    /**
                     * If a customer is exempted from tax, Tax Rate must be 0.
                     */

                    if($avalaraHelperObject->isCustomerTaxExempted($taxResponse))
                    {
                        $taxResponse['TaxLines'][0]['Rate'] = 0;

                        $this->logMessage['AvalaraFactory->getTaxInfo']['Tax_Response'] = "Customer: $customerID, is exempted from Tax.";
                    }

                    else
                    {
                        $this->logMessage['AvalaraFactory->getTaxInfo']['Tax_Response'] = "Customer: $customerID, in state: $customerState, has 0 tax returned from Avalara.";
                    }

                    $this->logFactory->writeAvalaraLog($this->logMessage);
                }
            }

            $calculatedTaxResponse = array(
                'status' => 1,
                'response' => array(
                    'TotalTax' => $taxResponse['TotalTax'],
                    'TaxCode'  => (is_string($taxResponse['TaxLines'][0]['TaxCode'])) ? $taxResponse['TaxLines'][0]['TaxCode'] : "P0000000",
                    'TaxRate'  => $taxResponse['TaxLines'][0]['Rate'] * 100
                ),
            );
            $this->logMessage['AvalaraFactory->getTaxInfo']['Tax_Response'] = $taxResponse;
            $this->logFactory->writeAvalaraLog($this->logMessage);
        }

        return $calculatedTaxResponse;
    }
}
