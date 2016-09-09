<?php

namespace App\Factory;

use App\Lib\Dashboard\Owl\OwlClient;
use Config;
use App\Mapper\LogFactory;
use App\Lib\Decrypt;


/**
 * Class OwlFactory
 *
 * This class is used to create request for the Owl Endpoints
 *
 * @package Ecomm\Factory
 */
class OwlFactory extends OwlClient
{
    private $config;
    private $logFactory;

    public function __construct()
    {
        $this->config = app()['config'];
        $this->logFactory = new LogFactory();
    }

    /**
     * This function will return the search results for company.
     * @param  Array $params input parameters company name, state, country, is_your_business
     * @return Array  $response returns response from OWL call for getting company details
     * @author kparakh
     */

    public function getSearchResults($params)
    {
        if($params['country']=='US' || $params['country']=='')
            $searchURL = $this->config->get('owl_endpoints.business_search');
        else
            $searchURL = $this->config->get('owl_endpoints.international_search');

        $query = array(
            'query' => $params
        );
        $owlInstance = OwlClient::getInstance();
        $response = $owlInstance->owlGetRequest($searchURL,$query);
        return $response;
    }

    /**
     * This function will return the product details from phoenix.
     * @param  Array $productIds ids of the products for which details are requested
     * @return Array  $response returns response from OWL call for getting product price details
     * @author kparakh
     */

    public function getProductDetails($productIds)
    {
        $productDetailsURL = $this->config->get('owl_endpoints.product_details');
        $productIdsString = '';
        $productIdsArray = array();
        foreach($productIds as $productId) {
            if(isset($productId["corelated_product_id_1"]) && strlen($productId["corelated_product_id_1"]) != 0)
                array_push($productIdsArray, $productId["corelated_product_id_1"]);
            if(isset($productId["corelated_product_id_2"]) && strlen($productId["corelated_product_id_2"]) != 0)
                array_push($productIdsArray, $productId["corelated_product_id_2"]);
            array_push($productIdsArray, $productId["product_id"]);

        }
        $productIdsString = implode(",", $productIdsArray);
        $params = array(
            'query' => array(
                'productId' => $productIdsString
            )
        );

        $owlInstance = OwlClient::getInstance();
        $response = $owlInstance->owlGetRequest($productDetailsURL,$params);

        return $response;
    }

    /**
     * This function will return the product details from phoenix.
     *
     * APP_VERSION is a flag to distinguish between where to get product data. As of now we have 4 versions -
     * APP_VERSION = 0 :- No call to contentful or phoenix. Everything comes from a config.
     * APP_VERSION = 1 :- Call Contetnful for the product details and call config to get the product prices.
     *                    Config files -
     *                      - product_key_coo_16.10 => Mock getProductDetailsBySlug response from owl/phx for COO products
     *                      - product_key_cos_16.10 => Mock getProductDetailsBySlug response from owl/phx for COS products
     * APP_VERSION = 2 :- Call Contetnful for the product details and call config to get the product prices. Here, COO products don't have packs.
     *                    Config files used -
     *                      - product_key_coo_one_pack => Mock getProductDetailsBySlug response from owl/phx for COO products having no packs
     *                      - product_key_cos_16.10 => Mock getProductDetailsBySlug response from owl/phx for COS products
     * APP_VERSION = 3 :- Call Contetnful for the product details and call owl/phx to get the product prices. No Config files required.
     *
     * @param array $productSlugs
     * @param boolean $isCOOPage
     * @return array $response returns response from OWL call for getting product price details
     * @author kparakh
     */

    public function getProductDetailsBySlug($productSlugs, $isCOOPage)
    {
        $productSlugsArray = array();
        $response = array();
        $logMessage = array();
        foreach($productSlugs as $productSlug) {
            array_push($productSlugsArray, $productSlug["main_product"]);
            if(isset($productSlug["co-related_product_1"]) && strlen($productSlug["co-related_product_1"]) != 0)
                array_push($productSlugsArray, $productSlug["co-related_product_1"]);
            if(isset($productSlug["co-related_product_2"]) && strlen($productSlug["co-related_product_2"]) != 0)
                array_push($productSlugsArray, $productSlug["co-related_product_2"]);
        }
        $productDetailsURL = $this->config->get('owl_endpoints.product_details');
        $productSlugsString = implode(",", $productSlugsArray);

        $params = array(
            'query' => array(
                'productSlug' => $productSlugsString
            )
        );
        if(env("APP_VERSION") == 3) {
            $owlInstance = OwlClient::getInstance();
            $response = $owlInstance->owlGetRequest($productDetailsURL,$params);
        } elseif (env("APP_VERSION") == 1 || env("APP_VERSION") == 2) {
            if($isCOOPage) {
                if(file_exists(config_path(). '/product_key_coo_16.10.php'))
                    $response = Config::get('product_key_coo_16.10');
                else {
                    $logMessage["OwlFactory->getProductDetailsBySlug"]["product_key_coo_16.10"] = "File Not Found!";
                    $this->logFactory->writeErrorLog($logMessage);
                }
            } else {
                if(file_exists(config_path(). '/product_key_cos_16.10.php'))
                    $response = Config::get('product_key_cos_16.10');
                else {
                    $logMessage["OwlFactory->getProductDetailsBySlug"]["product_key_cos_16.10"] = "File Not Found!";
                    $this->logFactory->writeErrorLog($logMessage);
                }
            }
        }
        return $response;
    }


    public function postCreditSignalSignUp($inputArr)
    {
        $decryptObj = new Decrypt();
        $dunsLength = 9;
        $params = array();

        if ((isset($inputArr['email']) && strlen($inputArr['email']) != 0) &&
            (isset($inputArr['firstName']) && strlen($inputArr['firstName']) != 0) &&
            (isset($inputArr['lastName']) && strlen($inputArr['lastName']) != 0) &&
            (isset($inputArr['encryptedDuns']) && strlen($inputArr['encryptedDuns']) != 0))
        {
            $params["email"] = filter_var($inputArr['email'], FILTER_SANITIZE_EMAIL);
            $params["first_name"] = preg_replace('/[^A-Za-z]/', '', $inputArr['firstName']);
            $params["last_name"] = preg_replace('/[^A-Za-z]/', '', $inputArr['lastName']);
            $params["duns"] = $decryptObj->decryptData($inputArr['encryptedDuns']);
            $params["accepted_tos"] = "1";

            if (preg_match('/^[0-9]{9}$/', $params["duns"]))
            {
                $CreditSignalSignUpURL = $this->config->get('owl_endpoints.creditsignal_signup');

                $owlInstance = OwlClient::getInstance();
                $response = $owlInstance->owlPostRequest($CreditSignalSignUpURL, $params);
                return $response;
            }
            else
            {
                $errorLog = "Encrypted DUNS did not decrypt into a length of ".$dunsLength;
            }
        }
        else
        {
            $errorLog = "Required parameters are not in the request or are empty";
        }

        if (strlen($errorLog) != 0)
        {
            $logMessage['OwlFactory->creditSignalSignUp']['error'] = $errorLog;
            $logFactoryObject = new LogFactory();
            $logFactoryObject->writeErrorLog($logMessage);
        }
    }
}