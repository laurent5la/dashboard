<?php
namespace App\Models;

//TODO: Adding this class for compatability with UserObjectFactory

use App\Mapper\OwlFactory;
use Config;
use Session;
use App\Lib\ECart\Helper\CrossCookie;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Mapper\LogFactory;
use App\Mapper\ProductMapper;
use App\Mapper\CompanyMapper;
use App\Models\Helpers\CartItemHelper;
use App\Mapper\CartItemMapper;
use App\Models\Helpers\ShoppingCartHelper;
use Illuminate\Support\Facades\Cache;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\CartConditionCollection;
use App\Lib\Tools;

class ShoppingCart
{
    private $darryl_shoppingcart_instance;
    private $cart_items;
    private $sub_total_amount;
    private $total_amount;
    private $tax_amount;
    private $promo_amount;
    private static $ENCRYPTION_KEY = "iSOrzgVSht5jStfy";
    private static $ENCRYPTION_IV  = "MWFiM2Q4ZjZkMmUy";
    private $log_factory;
    private $log_message = array();
    private $log_time = array();
    private $redirect_url;
    private $shopping_cart_helper;
    private $tax_code;
    private $tax_rate;
    private $last_deleted_product = null;

    public function formatToWriteIntoLog($funcName)
    {
        $this->log_message['ShoppingCart'] = $funcName;
        $this->log_message['sub_total'] = $this->sub_total_amount;
        $this->log_message['total'] = $this->total_amount;
        $this->log_message['tax'] = $this->tax_amount;
        $this->log_message['promo'] = $this->promo_amount;
        $this->log_message['Cart Items'] = $this->cart_items;
        return $this->log_message;
    }

    /**
     * Class Constructor
     *
     * This function instantiates the Shopping Cart instance to be used so that we add/ delete products from just that Cart
     * @author Rashmi
     */
    public function __construct($attributes = null)
    {
        $this->log_factory = new LogFactory();
        $this->darryl_shoppingcart_instance = app('cartlist');
        $this->config = app()['config'];
        $this->shopping_cart_helper = new ShoppingCartHelper();
    }

    /*
     * This function is not functional yet
     * PLEASE DO NOT USE
     */
    public function getAllCartItems()
    {
        $this->log_time['ShoppingCart'] = "getAllCartItems";
        $this->log_time['start_time'] = time();
        $cartItems = array();
        $darrylCartItems = $this->darryl_shoppingcart_instance->getContent();
        if (count($darrylCartItems))
        {
            foreach($darrylCartItems as $id => $darrylCartItem) {
                // create product object
                if ($darrylCartItem->attributes->has('product_type') && ($darrylCartItem->attributes['product_type'] == 'coo'))
                    $productObj = new Coo();
                else
                    $productObj = new Cos();

                // set product obj properties
                $productObj->setProductId($darrylCartItem->id);
                if ($darrylCartItem->attributes->has('name'))
                    $productObj->setName($darrylCartItem->name);

                // create cost strategy object
                if ($darrylCartItem->attributes->has('product_billing_type_code') && ($darrylCartItem->attribute['product_billing_type_code'] == 'ONTM'))
                    $costStrategyObj = new OneTimePrice();
                else
                    $costStrategyObj = new RecurringPrice();

                // set cost strategy product properties
                if ($darrylCartItem->attributes->has('subscription_frequency_unit_of_measurement'))
                    $costStrategyObj->setSubscriptionFrequencyUnitOfMeasurement($darrylCartItem->attribute['subscription_frequency_unit_of_measurement']);

                // create product price object
                $productPriceObj = new ProductPrice($productObj, $costStrategyObj);
                $productPriceObj->setProductPriceAmount($darrylCartItem->price);

                if ($darrylCartItem->attributes->has('price_id'))
                    $productPriceObj->setPriceId($darrylCartItem->attribute['price_id']);

                $cartItemObj = new CartItem($productPriceObj);
                $cartItems[] = $cartItemObj;
            }
        }
        $this->log_time['end_time'] = time();
        $this->log_time['elapsedTime'] = $this->log_time['end_time'] - $this->log_time['start_time'];
        $this->log_factory->writeTimingLog($this->log_time);
        return $cartItems;
    }


    /**
     * Checking if a product already exists in the Shopping Cart
     *
     * This function returns true if the product exits in the Cart; false otherwise.
     * @param int $slug
     * @return bool
     * @author Rashmi
     */
    public function checkIfProductAdded($slug)
    {
        return ( $this->darryl_shoppingcart_instance->get($slug));

    }

    /**
     * Adding a product to the Shopping Cart
     *
     * This function adds a product to the shopping cart
     * @param int $cartData['id']
     * @param string $cartData['name']
     * @param int $cartData['price']
     * @author Rashmi
     */
    public function addToCart(CartItem $cartItemObj)
    {
        $this->log_time['ShoppingCart'] = "addToCart";
        $this->log_time['start_time'] = time();
        $attributes = array();
        $productColor = $this->getProductColor($cartItemObj);
        $costStrategy = $cartItemObj->getCostStrategy();
        $productPrice = $cartItemObj->getProductPrice();
        $coRelatedPrice = $cartItemObj->getCoRelatedPrice();
        $contentfulProductSlug = $cartItemObj->getContentFulProductSlug();

        if($costStrategy instanceof OneTimePrice)
        {
            $attributes['sub_type'] = 0;
            $attributes['duration'] = 'monthly';
            $attributes['monthly_price'] = $cartItemObj->getProductPrice();
            $attributes['annual_price'] = null;
            $attributes['product_billing_type_code'] = 'ONTM';
            $attributes['subscription_frequency_unit_of_measurement'] = 'YRLY';
            $attributes['coupon'] = null;
            $attributes['product_color'] = $productColor;
            $attributes['product_type']=$cartItemObj->getProductType();
            $attributes['savings'] = null;
            $attributes['duns'] = $cartItemObj->getProductDuns();
            $attributes['company_name'] = $cartItemObj->getCompanyName();
        }

        $division = ($productPrice) ? (abs(($coRelatedPrice-$productPrice)/$productPrice)) : 0;

        if($costStrategy instanceof RecurringPrice && ($division < 0.00001))
        {
            $attributes['sub_type'] = 0;
            $attributes['duration'] = 'monthly';
            $attributes['monthly_price'] = $cartItemObj->getProductPrice();
            $attributes['annual_price'] = null;
            $attributes['product_billing_type_code'] = 'ONTM';
            $attributes['subscription_frequency_unit_of_measurement'] = 'YRLY';
            $attributes['coupon'] = null;
            $attributes['product_color'] = $productColor;
            $attributes['product_type']=$cartItemObj->getProductType();
            $attributes['savings'] = null;
            $attributes['duns'] = $cartItemObj->getProductDuns();
            $attributes['company_name'] = $cartItemObj->getCompanyName();
        }
        elseif($costStrategy instanceof RecurringPrice)
        {
            if($cartItemObj->getProductPrice() > $cartItemObj->getCoRelatedPrice())
            {
                $attributes['duration'] = 'annual';
                $attributes['annual_price'] = $cartItemObj->getProductPrice();
                $attributes['monthly_price']  = $cartItemObj->getCoRelatedPrice();
            }
            else
            {
                $attributes['duration'] = 'monthly';
                $attributes['monthly_price'] = $cartItemObj->getProductPrice();
                $attributes['annual_price']  = $cartItemObj->getCoRelatedPrice();
            }



			$attributes['sub_type'] = 1;
			$attributes["savings"] = isset($attributes["monthly_price"]) && isset($attributes["annual_price"]) ?
				$attributes["monthly_price"] * 12 - $attributes["annual_price"] :
				null;

			$attributes['product_billing_type_code'] = 'SBSN';
			$attributes['subscription_frequency_unit_of_measurement'] = 'MTHLY';
            $attributes['coupon'] = null;
            $attributes['product_color'] = $productColor;
            $attributes['product_type']=$cartItemObj->getProductType();
            $attributes['duns'] = $cartItemObj->getProductDuns();
            $attributes['company_name'] = $cartItemObj->getCompanyName();
        }
        else{
            $attributes['sub_type'] = 0;
            $attributes['duration'] = 'monthly';
            $attributes['monthly_price'] = $cartItemObj->getProductPrice();
            $attributes['annual_price'] = null;
            $attributes['product_billing_type_code'] = 'ONTM';
            $attributes['subscription_frequency_unit_of_measurement'] = 'YRLY';
            $attributes['coupon'] = null;
            $attributes['product_color'] = $productColor;
            $attributes['product_type']=$cartItemObj->getProductType();
            $attributes['savings'] = null;
            $attributes['duns'] = $cartItemObj->getProductDuns();
            $attributes['company_name'] = $cartItemObj->getCompanyName();
        }
        
        $attributes['contentful_product_slug'] = $contentfulProductSlug;

        $cartItem = array(
            'id' => $cartItemObj->getProductSlug(),
            'name' => $cartItemObj->getProductName(),
            'price' =>  $cartItemObj->getProductPrice(),
            'quantity' => 1,
            'attributes' => $attributes
        );
        $this->darryl_shoppingcart_instance->add($cartItem);
        $this->log_time['end_time'] = time();
        $this->log_time['elapsedTime'] = $this->log_time['end_time'] - $this->log_time['start_time'];
        $this->log_factory->writeTimingLog($this->log_time);
    }

    /**
     * Getting a product code for a product, used for styling purpose
     *
     * This function retrieves a specific color for a specific product
     * @param object $cartItemObj
     * @return string $productColor
     * @author Aswin
     */
    public function getProductColor($cartItemObj)
    {
        $product_slug = $cartItemObj->getProductSlug();
        $productId = substr($product_slug, 0, 4);
        $productColor = "light-blue";

        $colorCodes = Config::get('color_codes');

        foreach($colorCodes as $key => $value)
        {

            if($key == $productId)
            {
                $productColor = $value;
            }
        }
        return $productColor;
    }

    /**
     * Applying tax to the Shopping Cart
     *
     * This function adds tax to the shopping cart. It's specified in the value parameter and can be absolute value or a percentage
     * @author Rashmi
     */
    public function taxCondition()
    {
        $taxCondition = new \Darryldecode\Cart\CartCondition(array(
            'name' => 'VAT',
            'type' => 'tax',
            'target' => 'subtotal',
            'value' => '0',
            'attributes' => array('tax_code' => null)
        ));

        $this->darryl_shoppingcart_instance->condition($taxCondition);

    }

    /**
     * Switching product price and slug
     *
     * This function switches the concerned product with a new product_price_id and price
     * @param String $slug
     * @param int $newPrice
     * @author Rashmi, Aswin
     */
    public function switchProducts($slug)
    {
        $this->log_time['ShoppingCart'] = "switchProducts";
        $this->log_time['start_time'] = time();
        $finalRegisterResponse = array();
        //get current cart items
        $this->cart_items = $this->darryl_shoppingcart_instance->getContent();

        //clear the cart
        $this->darryl_shoppingcart_instance->clear();

        //Add all the cart items back to the cart
        foreach($this->cart_items as $item)
        {
            if($item->id == $slug)
            {
                $changedSlug = $this->getSlugForAnnualOrMonthlyPriceChange($slug);

                if($changedSlug)
                {
                    // storing the previous attributes
                    $duration = $item->attributes['duration'];
                    $coupon = $item->attributes['coupon'];
                    $subType = $item->attributes['sub_type'];
                    $monthlyPrice = $item->attributes['monthly_price'];
                    $annualPrice = $item->attributes['annual_price'];
                    $billingTypeCode = $item->attributes['product_billing_type_code'];
                    $subsFreq = $item->attributes['subscription_frequency_unit_of_measurement'];
                    $productColor = $item->attributes['product_color'];
                    $productType = $item->attributes['product_type'];
                    $savings = $item->attributes['savings'];
                    $duns = $item->attributes['duns'];
                    $companyName = $item->attributes['company_name'];
                    $contentfulProductSlug = isset($item->attributes['contentful_product_slug']) ? $item->attributes['contentful_product_slug'] : "";

                    if($duration == "monthly")
                    {
                        $price = $item->attributes['annual_price'];
                        $newDuration = "annual";
                    }
                    else
                    {
                        $price = $item->attributes['monthly_price'];
                        $newDuration = "monthly";
                    }

                    $cartItem = array(
                        'id' => $changedSlug,
                        'name' => $item->name,
                        'price' => $price,
                        'quantity' => 1,
                        'attributes' => array(
                            'sub_type' => $subType,
                            'duration' => $newDuration,
                            'monthly_price' => $monthlyPrice,
                            'annual_price' => $annualPrice,
                            'savings' => $savings,
                            'coupon' => $coupon,
                            'product_color' => $productColor,
                            'product_billing_type_code' => $billingTypeCode,
                            'subscription_frequency_unit_of_measurement' => $subsFreq,
                            'product_type' => $productType,
                            'duns' => $duns,
                            'company_name' => $companyName,
                            'contentful_product_slug' => $contentfulProductSlug
                        )
                    );
                }
                else
                {
                    $cartItem = array(
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' =>  $item->price,
                        'quantity' => 1,
                        'attributes' => $item->attributes
                    );
                    $finalRegisterResponse['status'] = Config::get('Enums.Status.FAILURE');
                    $finalRegisterResponse['error_code'] = "display_message";
                    $finalRegisterResponse['error_message'] = Config::get('Enums.Status.MESSAGE');
                }
            }
            else
            {
                $cartItem = array(
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' =>  $item->price,
                    'quantity' => 1,
                    'attributes' => $item->attributes
                );
            }
            $this->darryl_shoppingcart_instance->add($cartItem);
            $this->reValidateCoupon();
        }

        $this->log_time['end_time'] = time();
        $this->log_time['elapsedTime'] = $this->log_time['end_time'] - $this->log_time['start_time'];
        $this->log_factory->writeTimingLog($this->log_time);

        if(!empty($finalRegisterResponse))
        {
            return $finalRegisterResponse;
        }
    }

    /**
     * Removing a product from the Shopping Cart
     *
     * This function removes the specified product from the shopping cart.
     * @param int $slug
     * @author Rashmi
     */
    public function removeProductFromCart($slug)
    {
        $this->log_time['ShoppingCart'] = "removeProductFromCart";
        $this->log_time['start_time'] = time();
        $product = $this->darryl_shoppingcart_instance->get($slug);
        //We check for 'intl' in the contentful_product_slug to determine if the product is international, if so we want to set redirect url to intl-search
        $isIntl = 0;
        if(isset($product->attributes["contentful_product_slug"])) {
            $intlIndex = strpos($product->attributes["contentful_product_slug"], 'intl');
            if($intlIndex !== false)
                $isIntl = 1;
        }

        if ($isIntl) {
            $this->redirect_url = "intl-search";
        } else {
            $this->redirect_url = (isset($product->attributes['product_type'])) ? $product->attributes['product_type'] : 'cos';
        }
        $this->last_deleted_product = "Product slug = " . $slug . "; Product Name = " . $product->name . "; Redirect url value = " . $this->redirect_url;

        $this->darryl_shoppingcart_instance->remove($slug);

        //If Shopping cart is empty then remove coupon condition
        if($this->darryl_shoppingcart_instance->isEmpty())
            $this->darryl_shoppingcart_instance->removeCartCondition('Discount');
        $this->log_time['end_time'] = time();
        $this->log_time['elapsedTime'] = $this->log_time['end_time'] - $this->log_time['start_time'];
        $this->log_factory->writeTimingLog($this->log_time);
    }

    /**
     * Checks if the product exists in cart and returns the productPriceID_DUNS
     *
     * @param int product_id
     * @return String
     * @author Rashmi
     */
    public function checkIfProductExistsInCart($pid)
    {
        $cartProducts = $this->darryl_shoppingcart_instance->getContent();
        foreach($cartProducts as $item)
        {
            $slug = $item->id;
            $slugParts = explode("_", $slug);
            if($slugParts[0] == $pid)
                return $slugParts[1] ."_". $slugParts[2];
        }
        return null;
    }

    /*
     * Removes  coupon applied to the Shopping Cart
     *
     * This function removes the coupon applied to the shopping cart and also resets the coupon attribute if its set on product level
     * @return view
     * @author Rashmi
     */
    public function removeCouponAppliedOnCart()
    {
        $this->darryl_shoppingcart_instance->removeCartCondition('Discount');
        $this->resetCouponAttribute();
    }

    /**
     * Returns the shopping cart features that are required to be displayed in the view and sets the cookie
     *
     * This function returns the necessary features for the associated view depending on which function calls the method and also sets the shopping cart cookie
     * @param string $functionName
     * @param CrossCookieMock $crossCookie for unit testing
     * @param int $params ['dollars']
     * @param int $params ['cents']
     * @param int $params ['i']
     * @return array|null
     * @author Rashmi, Tanvi
     *
     */
    public function getCartFeaturesForDisplay($functionName = null, $crossCookie = null)
    {
        if($functionName == null || $functionName == "")
        {
            $logError["functionNameParameter"]["notSet"] = "The function name from which getCartFeaturesForDisplay() function is called is not being passed as a parameter. This shouldn't happen. Please check and rectify";
            $this->log_factory->writeErrorLog($logError);
        }

        $getCartAmount = $this->calculateCartAmount($functionName);

        $this->cart_items = $this->darryl_shoppingcart_instance->getContent();

        if($this->cart_items->count() == 0)
        {
            //Logging message
            $this->log_factory->writeInfoLog($functionName ." - Shopping Cart Empty. Last deleted product Info: " .$this->last_deleted_product);
        }

        $cartFeatures = array(
            'subTotalD' => $getCartAmount['subTotalValue'][0],
            'subTotalC' => $getCartAmount['subTotalValue'][1],
            'totalD' => $getCartAmount['totalValue'][0],
            'totalC' => $getCartAmount['totalValue'][1],
            'items' => $this->cart_items,
            'taxD' => $getCartAmount['taxValue'][0],
            'taxC' => $getCartAmount['taxValue'][1],
            'promoD' => $getCartAmount['promoValue'][0],
            'promoC' => $getCartAmount['promoValue'][1],
            'redirect_url' => $this->cart_items->count()==0 ? $this->redirect_url : ''
        );

        //Setting the cookie
        if(empty($crossCookie)) {
            $crossCookie = new CrossCookie();
        }
        $cookieValue = $this->setCookieValue();
        $crossCookie->shoppingCartCookie($cookieValue);

        //Logging message
        $cartString = $this->formatToWriteIntoLog($functionName);
        $this->log_factory->writeInfoLog($cartString);
        return $cartFeatures;
    }

    /**
     * Calculating the amounts associated with the Shopping Cart
     *
     * This function returns the amounts associated with the shopping cart which are subtotal, total, tax and promo amounts depending
     * @param string $functionName
     * @return mixed
     * @author Rashmi
     */
    public function calculateCartAmount($functionName = null)
    {
        $cartAmount = array();
        $logError = array();
        $this->sub_total_amount = $this->darryl_shoppingcart_instance->getSubTotal();
        $subTotalAmount = $this->sub_total_amount;
        $this->sub_total_amount = round($this->sub_total_amount,2);
        $this->sub_total_amount = number_format($this->sub_total_amount, 2, '.', '');
        $cartAmount['subTotalValue'] = $this->splitAmount($this->sub_total_amount);

        //Coupon Amount
        $couponCondition = $this->darryl_shoppingcart_instance->getCondition('Discount');
        if(is_object($couponCondition))
        {
            $this->promo_amount = $couponCondition->getValue();
            if(strpos($this->promo_amount, '%') !== false)
            {
                $this->promo_amount = $this->calculatePercentageValue($this->promo_amount);
            }
            $this->promo_amount = abs($this->promo_amount);
            $this->promo_amount = round($this->promo_amount,2);
            $this->promo_amount = number_format($this->promo_amount, 2, '.', '');
            $cartAmount['promoValue'] = $this->splitAmount($this->promo_amount);
        }
        else
        {
            $cartAmount['promoValue'][0] = 0;
            $cartAmount['promoValue'][1] = 0;
        }

        //Tax Amount
        $taxCondition = $this->darryl_shoppingcart_instance->getCondition('VAT');
        if(is_object($taxCondition))
        {
            $this->tax_amount = $taxCondition->getValue();
            if (strpos($this->tax_amount, '%') !== false)
            {
                $this->tax_amount = $this->calculateTaxValue($this->tax_amount, $cartAmount['promoValue']);
            }
            $this->tax_amount = round($this->tax_amount, 2);
            $this->tax_amount = number_format($this->tax_amount, 2, '.', '');
            $cartAmount['taxValue'] = $this->splitAmount($this->tax_amount);
        }
        else
        {
            $cartAmount['taxValue'][0] = 0;
            $cartAmount['taxValue'][1] = 0;
            $logError[$functionName]['taxObject'] = "Tax condition object does not exist. This should never have happened. Please check";

        }
        //Cart Total Amount
        $this->total_amount =  $this->getCartTotalAmount($subTotalAmount,$cartAmount['promoValue'], $cartAmount['taxValue']);
        $this->total_amount = round($this->total_amount,2);
        $this->total_amount = number_format($this->total_amount, 2, '.', '');
        $cartAmount['totalValue'] = $this->splitAmount($this->total_amount);

        $this->log_factory->writeErrorLog($logError);
        return $cartAmount;
    }

    /**
     * Splitting the Amount into dollars and cents
     *
     * This function returns the values of dollars and cents of an amount passed for display purpose
     * @param int $params
     * @return mixed
     * @author Rashmi
     */
    private function splitAmount($params)
    {
        $amount = explode(".", $params);
        if(count($amount) == 1)
            $amount[1] = '00';
        return $amount;
    }

    /*
     * Calculating the value when tax/coupon is applied as a percentage
     *
     * This function returns the value of in dollars when tax/coupon is applied in percentage
     * @params String $value
     * @return float
     * @author Rashmi
     */
    private function calculatePercentageValue($value)
    {
        $percentage = chop($value, "%");
        $percentageNumeric = floatval($percentage);
        $percentageValue = ($percentageNumeric /100 ) * $this->sub_total_amount;
        return $percentageValue;
    }

    /*
     * Calculating the dollar value when tax is applied
     *
     * This function returns the dollar value of tax when tax is applied in percentage
     * @params String $taxPercentage  It is the percentage value of tax to be applied
     * @params String $promoValue It is the dollar value of coupon that is currently applied to the cart
     * @return float $taxValue  this is the value of tax to be applied based on promocode and subtotal
     * @author Leena
     */
    private function calculateTaxValue($taxPercentage,$promoValue)
    {
        $taxPercentage = chop($taxPercentage, "%");
        $taxPercentageNumeric = floatval($taxPercentage);

        $promoDollarAmount = $promoValue[0];
        $promoCentAmount = $promoValue[1];
        $promoAmount = floatval($promoDollarAmount)+0.01*floatval($promoCentAmount);

        //coupon is applied
        if($promoAmount){
            $taxValue = ($taxPercentageNumeric /100 ) * (floatval($this->sub_total_amount) - $promoAmount);
        }else{
            $taxValue = ($taxPercentageNumeric /100 ) * $this->sub_total_amount;

        }
        return $taxValue;
    }

    /*
     * Calculating the cart total
     *
     * This function returns the total dollar value of the cart
     * @params String $subTotal - the subtotal of the current cart
     * @params String $promoValue - the dollar value of coupon that is currently applied to the cart
     * @params String $taxvalue -  the dollar value of tax applied to the cart

     * @return float $cartTotal- the current total for the cart(in dollars)
     * @author Leena
     */
    private function getCartTotalAmount($subTotal,$promoValue,$taxValue){
        $promoAmount = floatval($promoValue[0])+0.01*floatval($promoValue[1]);
        $taxAmount = floatval($taxValue[0])+0.01*floatval($taxValue[1]);
        $cartTotal = floatval($subTotal)-floatval($promoAmount)+floatval($taxAmount);
        return $cartTotal;
    }

    /*
         * Returns the annual product_price_id of the matching product if the current one is monthly and vice-versa
         *
         * Steps in function
         *
         * 1. The input slug is exploded and a new slug is created without the duns
         *
         * 2. The slug without duns is mapped to a product slug in the product_slug config
         *
         * 3. The getProductDetails config is iterated through and a new array, switchDetailsArray is created with the mthly and yearly versions of a certain
         * product id.
         *
         * 4. The switchDetailsArray is iterated through to find the corelated price id of the input slug
         *
         * 5. With the corelated price id a new slug is created and returned
         *
         * @TODO Use phoenix endpoint to get product detail information by product slug once that has been built
         *
         * @param String $slugParam
         * @return String $slugOutputString Slug corresponding to the new price_id
         * @use /config/products_cos.php
         * @author Rashmi, Aswin
         */
    private function getSlugForAnnualOrMonthlyPriceChange($slugParam)
    {
        $logFactory = new LogFactory();
        $logMessage = array();
        $slugArray = array();
        $slugOutput = array();
        $slugArray = explode("_", $slugParam);
        $slugOutput = explode("_", $slugParam);
        //Remove duns number from array
        array_pop($slugArray);
        //create new slug string without duns
        $newSlugWithoutDuns = implode($slugArray, "_");
        $switchDetailsArray = array();
        $changedSlug = "";

        $productSlugArray = Config::get('product_slug')['response'];
        $productDetailsArray = Config::get('getProductDetailsBySlug')['response'];
        $productSlug = "";
        $corelatedPriceId = 0;
        array_key_exists($newSlugWithoutDuns, $productSlugArray)
            ? $productSlug = $productSlugArray[$newSlugWithoutDuns]
            : $logMessage["ShoppingCart"]["getSlugForAnnualOrMonthlyPriceChange"][] = "The incoming product slug was not found in the product slug config";

        foreach($productDetailsArray as $key => $value) {
            if(isset($value["product_info"])) {
                if(isset($value["product_info"]["product_id"])) {
                    if ($value["product_info"]["product_id"] == $slugOutput[0]) {
                        array_push($switchDetailsArray, $value["product_info"]);
                    }
                } else {
                    $logMessage["ShoppingCart"]["getSlugForAnnualOrMonthlyPriceChange"][] = "Product ID was not found";
                }
            } else {
                $logMessage["ShoppingCart"]["getSlugForAnnualOrMonthlyPriceChange"][] = "Product info was not found";
            }
        }
        foreach($switchDetailsArray as $key => $value) {
            if(isset($value["product_price_key"])) {
                if ($productSlug != $value["product_price_key"]) {
                    if(isset($value["subscription_prices"])
                        && isset($value["subscription_prices"]["USD"])
                        && isset($value["subscription_prices"]["USD"]["price_id"])) {
                        $corelatedPriceId = $value["subscription_prices"]["USD"]["price_id"];
                    } else {
                        $logMessage["ShoppingCart"]["getSlugForAnnualOrMonthlyPriceChange"][] = "Subscription prices, USD, price id was not set";
                    }
                }
            } else {
                $logMessage["ShoppingCart"]["getSlugForAnnualOrMonthlyPriceChange"][] = "Product price key was not found";
            }
        }
        $slugOutput[1] = strval($corelatedPriceId);
        $slugOutputString = implode($slugOutput, "_");
        $logFactory->writeErrorLog($logMessage);
        return $slugOutputString;
    }

    /*
     * Returns the annual product_price_id of the matching product if the current one is monthly and vice-versa
     *
     * @param String $productDetails
     * @param Array $slugParts
     * @return String $changedSlug Slug corresponding to the new price_id
     * @use /config/subscription_frequency_unit_of_measurement.php
     * @author Rashmi, Aswin
     */

    private function getChangedSlug($productDetails, $slugParts)
    {
        $changedSlug = "";
        $subscriptionFrequencyConfig = Config::get("subscription_frequency_unit_of_measurement");

        foreach($subscriptionFrequencyConfig as $key => $value)
        {
            if($slugParts[1] == $key)
            {
                $frequencyUnitOfMeasurement =  $value["frequency_unit_of_measurement"];
                $coRelatedPriceID = $value["corelated_price_id"];
            }
        }

        $subscriptionPrices = $productDetails["response"][0]["subscription_prices"];

        foreach($subscriptionPrices as $key => $value)
        {
            if($value["subscription_frequency_unit_of_measurement"] == $frequencyUnitOfMeasurement)
            {
                $changedSlug = $slugParts[0] ."_" .$coRelatedPriceID ."_" .$slugParts[2];
            }
        }

        return $changedSlug;
    }

    /**
    * Setting the cookie value as json encoded list of product slugs in the Shopping Cart
    * @return mixed $value list of product slugs
    * @author Rashmi
    */
    private function setCookieValue()
     {
         $value = "";

         if(!$this->darryl_shoppingcart_instance->isEmpty())
         {
             $value = array();
             $this->cartItems = $this->darryl_shoppingcart_instance->getContent();

             foreach ($this->cartItems as $item)
             {
                 $value[] = $item->id;
             }
        }

         return (json_encode($value));
     }

    /*
     * Resetting the coupon attribute if its set while deleting the coupon
     * @param Object $item
     * @author Rashmi
     */
    private function resetCouponAttribute()
    {
        $cartProducts = $this->darryl_shoppingcart_instance->getContent();
        foreach($cartProducts as $item)
        {
           if(isset($item->attributes['coupon']))
           {
               //Reset coupon attribute
               $duration = $item->attributes['duration'];
               $subType = $item->attributes['sub_type'];
               $annualPrice = $item->attributes['annual_price'];
               $monthlyPrice = $item->attributes['monthly_price'];
               $billingTypeCode = $item->attributes['product_billing_type_code'];
               $subsFreq = $item->attributes['subscription_frequency_unit_of_measurement'];
               $productColor = $item->attributes['product_color'];
               $productType = $item->attributes['product_type'];
               $savings = $item->attributes['savings'];
               $duns = $item->attributes['duns'];
               $companyName = $item->attributes['company_name'];
               $contentfulProductSlug = isset($item->attributes['contentful_product_slug']) ? $item->attributes['contentful_product_slug'] : "";

               $this->darryl_shoppingcart_instance->update($item->id,
                   array('attributes' => array('sub_type' => $subType, 'duration' => $duration, 'monthly_price' => $monthlyPrice, 'annual_price' => $annualPrice, 'coupon' => null, 'savings' => $savings,
                       'product_color' => $productColor, 'product_billing_type_code' => $billingTypeCode, 'subscription_frequency_unit_of_measurement' => $subsFreq, 'product_type' => $productType, 'duns' => $duns, 'company_name' => $companyName, 'contentful_product_slug' => $contentfulProductSlug)
                   ));
           }
        }
    }

    /*
     * Returns the cart with calculated tax value
     *
     * @param String $tax
     * @param CrossCookieMock $crossCookie for unit testing
     * @return Array $cartFeatures
     * @use /app/Mapper/UserObjectFactory.php
     * @author Kunal
     */
    public function updateCartWithTaxInfo($taxResponse, $crossCookie = null)
    {
        $taxCondition = new \Darryldecode\Cart\CartCondition(array(
            'name' => 'VAT',
            'type' => 'tax',
            'target' => 'subtotal',
            'value' => $taxResponse['TaxRate'].'%',
            'attributes' => array('tax_code' => $taxResponse['TaxCode'])
        ));

        $this->darryl_shoppingcart_instance->condition($taxCondition);
        $cartFeatures = $this->getCartFeaturesForDisplay("ShoppingCart->updateCartWithTaxInfo", $crossCookie);
        return $cartFeatures;
    }

    /*
     * @author Kunal
     */
    public function getTaxCode()
    {
        if(is_object($this->darryl_shoppingcart_instance->getCondition('VAT')))
        {
            $this->tax_code = $this->darryl_shoppingcart_instance->getCondition('VAT')->getAttributes()['tax_code'];
            return $this->tax_code;
        }
        else
        {
            $logMessage['getTaxCode']['taxObject'] = "Tax condition object does not exist. This should never have happened. Please check";
            $this->log_factory->writeErrorLog($logMessage);
            return null;
        }
    }

    /*
     * @author Kunal
     */
    public function getTaxRate()
    {
        if(is_object($this->darryl_shoppingcart_instance->getCondition('VAT')))
        {
            $taxCondition = $this->darryl_shoppingcart_instance->getCondition('VAT');
            $this->tax_rate = $taxCondition->getValue();
            return $this->tax_rate;
        }
        else
        {
            $logMessage['getTaxRate']['taxObject'] = "Tax condition object does not exist. This should never have happened. Please check";
            $this->log_factory->writeErrorLog($logMessage);
            return null;
        }
    }

    public function inputValidation($functionName, $params)
    {
        switch ($functionName)
        {
            case "switchProduct":
            case "removeProduct":
                if (count($params) != 1)
                    $this->log_message['inputValidation'][$functionName] = "Request does not contain just 1 POST parameter";
                $slugParts = explode("_", $params['product_slug']);
                if (count($slugParts) != 3)
                    $this->log_message['inputValidation'][$functionName] = "Product slug did not consist of 3 parts";
                if (!is_numeric($slugParts[0]) and !is_numeric($slugParts[1]) and !is_numeric($slugParts[2]))       //returns a numeric string so is_int won't work
                    $this->log_message['inputValidation'][$functionName] = "Product slug corrupted. ID's fail is_numeric() condition";
                break;
        }
        return $this->log_message;
    }

    /**
     * This function will take Product Id as input and will return Product Details.
     * @input productID
     * @return json : product details
     * TODO Call from CheckoutController@index and remove hard coded value once OWL/PHX Endpoints are completed.
     */
    public function productDetails($productId=array())
    {
        $productMapper =  new ProductMapper();
        $productsObject = $productMapper->getProducts($productId);
        return $productsObject;
    }
    public function companyDetails($dunsNumber='060902413')
    {
        $companyMapper = new CompanyMapper();
        $companyObject = $companyMapper->getCompanyName($dunsNumber);
        return $companyObject;
    }

    /**
     *
     * This function decrypts the incoming data
     * @param $data
     * @return string
     * @author Aswin
     */
    public static function decrypt($data)
    {
        $aes = self::_mcrypt_init();
        $ret = mdecrypt_generic($aes, $data);
        mcrypt_generic_deinit($aes);
        $data = rtrim($ret);
        return $data;
    }
    /**
     * This function decrypts the incoming DUNS number
     * @param $encryptedStr
     * @param bool $urldec
     * @return mixed
     * @author Aswin
     */
    public static function decryptDuns($encryptedStr, $urldec = false)
    {
        if ($urldec) {
            return self::decrypt(base64_decode(urldecode($encryptedStr)));
        }
        else {
            return self::decrypt(base64_decode(urldecode($encryptedStr)));
        }
    }
    /**
     * This function calls a mcrypt module which can be used for encrypting and decrypting.
     * @return resource
     * @author Aswin
     */
    private static function _mcrypt_init()
    {
        $aes = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        (mcrypt_generic_init($aes, self::$ENCRYPTION_KEY, self::$ENCRYPTION_IV) != -1) or die("<b>mcrypt_generic_init failed!</b>");
        return $aes;
    }

    /**This function returns CART total.
     * @return total cart amount
     * @author Kunal
     */

    public function getTotal()
    {
        $cartFeature = $this->getCartFeaturesForDisplay("ShoppingCart->getTotal");
        $totalAmount = $cartFeature['totalD'].'.'.$cartFeature['totalC'];
        return $totalAmount;
    }

    /**This function returns total TAX on CART.
     * @return total tax
     * @author Gaurav, Kunal
     */

    public function getTotalTax($crossCookie = null)
    {
        $totalTaxAmount = 0;
        if(is_object($this->darryl_shoppingcart_instance->getCondition('VAT')))
        {
            $taxCondition = $this->darryl_shoppingcart_instance->getCondition('VAT');
            $updatedTaxObj['TaxCode'] = $taxCondition->getAttributes()['tax_code'];
            $updatedTaxObj['TaxRate'] = $taxCondition->getValue();
            $cartFeature = $this->updateCartWithTaxInfo($updatedTaxObj, $crossCookie);
            $totalTaxAmount = $cartFeature['taxD'].'.'.$cartFeature['taxC'];
        }
        else
        {
            $this->log_message['getTotalTax']['taxObject'] = "Tax condition object does not exist. This should never have happened. Please check";
            $this->log_factory->writeErrorLog($this->log_message);
        }
        return $totalTaxAmount;
    }

    /**This function sets order details for PFS Call.
     * @params array : $userCCInfo
     * @return array $pfsCart
     */

    public function setCartItemsPFS($userCCInfo)
    {
        $getCartItems = $this->getCartItems();

        $getCCAndTransactionType = $this->getCCAndTransactionType($userCCInfo);

        $getTaxAmount['totalTax'] = round((float)$this->getTotalTax(), 2);

        $promotionCode = $this->getPromotionCode();

        $pfsCart = array_merge($getCartItems,$getCCAndTransactionType,$getTaxAmount);
        if($promotionCode != '' || $promotionCode != null)
            $pfsCart = array_merge($pfsCart,['promotionCode' => $promotionCode]);
        return $pfsCart;
    }

    /**This function will get Product Id, Price Id, Tax Details and DUNS for each Product.
     * @return array : $cartItem
     */

    public function getCartItems()
    {
        $cartItems = array();
        $productItems = $this->darryl_shoppingcart_instance->getContent();
        $count = 0;
        $promoCode = "";
        foreach($productItems as $item)
        {
            $productValues = explode("_",$item->id);

            if(is_object($item->attributes) && isset($item->attributes['coupon']))
            {
                $promoCode = $item->attributes['coupon'];
            }

            else
            {
                $logFactory = new LogFactory();
                $logMessage['getCartItems']['message'] = "Coupon attribute doesn't exist in Darryl Cart. This should not have happened.";
                $logFactory->writeErrorLog($logMessage);
            }

            $cartItems['cartItems'][$count]['productId'] = isset($productValues[0])?(int)$productValues[0]:'';
            $cartItems['cartItems'][$count]['priceId'] = isset($productValues[1])?(int)$productValues[1]:'';
            $cartItems['cartItems'][$count]['taxDetail'] =  $this->setTaxDetailsForEachProduct($item->price, $promoCode);

            /**
             * Check for valid DUNS
             */
            if(isset($productValues[2]) && strlen($productValues[2])==9)
                $cartItems['cartItems'][$count]['duns'] = (string)$productValues[2];
            $count++;
        }

        return $cartItems;
    }

    /**
     * This function sets tax at product level.
     * @params $price, $promoCode
     * @return array : $taxDetails
     */

    public function setTaxDetailsForEachProduct($price, $promoCode)
    {
        $taxDetails = array();

        $discountValue = 0;

        //If coupon is applied to the product, apply it to the price before calculating Tax Amount.

        if(strlen($promoCode)!=0)
        {
            $darrylObject = $this->darryl_shoppingcart_instance->getCondition('Discount');
            if(isset($darrylObject->getAttributes()['promo_type']) && isset($darrylObject->getAttributes()['promo_value']))
            {
                $promoType = $darrylObject->getAttributes()['promo_type'];
                $promoValue = $darrylObject->getAttributes()['promo_value'];

                if($promoType=="PCNTAGE")
                {
                    $discountValue = round(($promoValue / 100) * $price, 2);
                }
                else
                {
                    $discountValue = $promoValue;
                }
            }
            else
            {
                $logFactory = new LogFactory();
                $logMessage['setTaxDetailsForEachProduct']['message'] = "Coupon attributes promo_type and promo_value doesn't exist in Darryl Cart. This should not have happened.";
                $logFactory->writeErrorLog($logMessage);
            }
        }

        $discountedAmount = $price - $discountValue;

        $taxRate = str_replace("%","",$this->getTaxRate());

        $taxDetails['taxRate'] = round((float)$taxRate, 2);

        $taxDetails['taxCode'] = $this->getTaxCode();

        $taxDetails['taxAmount'] = round(($taxRate / 100) * $discountedAmount, 2);

        return $taxDetails;
    }

    /**
     * This function will return user Info for PFS.
     * @param array $userCCInfo
     * @return array : $userInfo
     */

    public function getCCAndTransactionType($userCCInfo)
    {
        $cartData = $this->getCartItems();
        $cartData["cartTotal"] = $this->getTotal();
        if(isset($cartData["cartItems"])) {
            $formattedCCInfo = $this->shopping_cart_helper->setCCDetails($userCCInfo['creditCardDetail'], $cartData);

            $userInfo = array();
            $userInfo['creditCardDetail'] = $formattedCCInfo['creditCardDetail'];
            $userInfo['transactionType'] = ((isset($userInfo['creditCardDetail']['paymentTokenIdentifier'])) && (strlen($userInfo['creditCardDetail']['paymentTokenIdentifier']))!=0) ? 'sale' : 'sale,create_payment_token';
            $userInfo['saveCard'] = 'true';
            /** Warning: If this, $userInfo['addressValidated'], variable is set to null the pfs call will error on submitting the sales order. **/
            /** Reference: https://dunandb.jira.com/browse/WAC-561 */
            $userInfo['addressValidated'] = is_null(Session::get('addressValidated')) ? "false" : Session::get('addressValidated');
            return $userInfo;
        }
        else
        {
            $logFactory = new LogFactory();
            $logMessage['getCCAndTransactionType']['message'] = "Cart is empty. This should not have happened.";
            $logFactory->writeErrorLog($logMessage);
        }
    }

    public function getPromotionCode()
    {
        if(is_object($this->darryl_shoppingcart_instance->getCondition('Discount')))
        {
            $promoCode = $this->darryl_shoppingcart_instance->getCondition('Discount')->getAttributes()['couponCode'];
            return $promoCode;
        }
        else
        {
            $this->log_message['getPromotionCode']['promoObject'] = "Promo condition object does not exist. This should never have happened. Please check this promotion code: ";
            $this->log_factory->writeErrorLog($this->log_message);
            return null;
        }
    }

    /**
     * This function sets field required for Signature.
     * @param array $MRI,$cartItems
     * @return array : $userInfo
     */

    public function setSignatureFields($MRI,$cartItems, $userInfo)
    {
        $signatureFields = array();
        $signedDateTime = gmdate("Y-m-d\TH:i:s\Z");
        $signatureFields['unsigned_field_names'] = Config::get('toBeSigned.unsigned_field_names');
        $signatureFields['signed_field_names'] = Config::get('toBeSigned.signed_field_names');
        $signatureFields['access_key'] = env("CYBERSOURCE ACCESS KEY");
        $signatureFields['profile_id'] = env("CYBERSOURCE PROFILE ID");
        $signatureFields['transaction_uuid'] = uniqid();
        $signatureFields['reference_number'] = $MRI;
        $signatureFields['override_custom_receipt_page'] = env("CYBERSOURCE_SUCCESS");
        $signatureFields['orderPage_declineResponseURL'] = env("CYBERSOURCE_DECLINE");
        $signatureFields['transaction_type'] = isset($cartItems['transactionType']) ? $cartItems['transactionType'] : "";
        $signatureFields['bill_to_forename'] = isset($userInfo['creditCardDetail']['first_name_on_card']) ? $userInfo['creditCardDetail']['first_name_on_card'] : "";
        $signatureFields['bill_to_surname'] = isset($userInfo['creditCardDetail']['last_name_on_card']) ? $userInfo['creditCardDetail']['last_name_on_card'] : "";
        $signatureFields['bill_to_email'] = isset($userInfo['creditCardDetail']['email']) ? $userInfo['creditCardDetail']['email'] : "";
        $signatureFields['bill_to_phone'] = isset($userInfo['creditCardDetail']['phone']) ? $userInfo['creditCardDetail']['phone'] : "";
        $signatureFields['bill_to_address_line1'] = isset($userInfo['creditCardDetail']['address']['address_line_1']) ? $userInfo['creditCardDetail']['address']['address_line_1'] : "";
        $signatureFields['bill_to_address_line2'] = isset($userInfo['creditCardDetail']['address']['address_line_2']) ? $userInfo['creditCardDetail']['address']['address_line_2'] : "";
        $signatureFields['bill_to_address_city'] = isset($userInfo['creditCardDetail']['address']['city_name']) ? $userInfo['creditCardDetail']['address']['city_name'] : "";
        $signatureFields['bill_to_address_state'] = isset($userInfo['creditCardDetail']['address']['state_code']) ? $userInfo['creditCardDetail']['address']['state_code'] : "";
        $signatureFields['bill_to_address_postal_code'] = isset($userInfo['creditCardDetail']['address']['zip_code']) ? $userInfo['creditCardDetail']['address']['zip_code'] : "";
        $signatureFields['bill_to_address_country'] = isset($userInfo['creditCardDetail']['address']['country_code']) ? $userInfo['creditCardDetail']['address']['country_code'] : "";

        if(isset($userInfo['creditCardDetail']['payment_token_identifier']))
            $signatureFields['payment_token'] = isset($userInfo['creditCardDetail']['payment_token_identifier']) ? $userInfo['creditCardDetail']['payment_token_identifier'] : "";
        else
            $signatureFields['payment_token'] = "";

        $signatureFields['payment_method'] = Config::get('toBeSigned.payment_method');
        $signatureFields['amount'] = (string) $this->getTotal();
        $signatureFields['currency'] = Config::get('toBeSigned.currency');
        $signatureFields['signed_date_time'] = $signedDateTime;
        $signatureFields['locale'] = Config::get('toBeSigned.locale');
        return $signatureFields;
    }

    /**
     * Applying Coupon to the Shopping Cart
     *
     * This function adds a coupon to the shopping cart. It's specified in the value parameter.
     * A minus(-) sign precedes the value as coupon is applied to get a discount on the total amount.
     * @params $couponResponse
     *
     * @author Rashmi
     */
    public function applyCoupon($couponResponse)
    {
        $promo_level = isset($couponResponse['promotion_applicability_level_code']) ? $couponResponse['promotion_applicability_level_code'] : '';
        $promotionCode = isset($couponResponse['promotion_code']) ? $couponResponse['promotion_code'] : '';
        $promo_type = isset($couponResponse['promotion_subtype_code']) ? $couponResponse['promotion_subtype_code'] : '';
        $promo_value =  isset($couponResponse['promotion_subtype_value']) ? $couponResponse['promotion_subtype_value'] : '';
        $noProductsWithCoupon = 0;
        $totalProductAmt = 0;
        $couponValue = 0;
        $promoProductIds = array();
        $status = false;

        /**
         * Order level, Coupon Dollar Value > Cart Sub Total: Return false.
         */

        if($promo_level == "ORDER" && $promo_type !="PCNTAGE" && $promo_value > $this->darryl_shoppingcart_instance->getSubTotal())
        {
            return false;
        }

        /**
         * Product level: Get all product Ids applicable to Coupon.
         */

        if($promo_level == "PRODUCT" && isset($couponResponse['applicable_product_catalog_ids']))
        {
            $promoProductIds = $couponResponse['applicable_product_catalog_ids'];
            if(count($promoProductIds)==0)
                return false;
        }

        //Apply promo code only if these are set
        if (strlen($promo_level) != 0 and strlen($promo_type) != 0 and strlen($promo_value) != 0)
        {
            //add all the prices of the products and change coupon attribute for the products which promo code has to be applied
            $cartProducts = $this->darryl_shoppingcart_instance->getContent();

            /**
             * This for loop updates the coupon attribute of each product to show whether it is associated with that coupon.
             */

            foreach ($cartProducts as $item)
            {
                $productId = explode("_", $item->id)[0];

                if (in_array($productId, $promoProductIds) || $promo_level == "ORDER")
                {
                    $totalProductAmt += $item->price;
                    $noProductsWithCoupon++;

                    //Update attribute
                    $duration = $item->attributes['duration'];
                    $subType = $item->attributes['sub_type'];
                    $annualPrice = $item->attributes['annual_price'];
                    $monthlyPrice = $item->attributes['monthly_price'];
                    $billingTypeCode = $item->attributes['product_billing_type_code'];
                    $subsFreq = $item->attributes['subscription_frequency_unit_of_measurement'];
                    $productColor = $item->attributes['product_color'];
                    $productType = $item->attributes['product_type'];
                    $savings = $item->attributes['savings'];
                    $duns = $item->attributes['duns'];
                    $companyName = $item->attributes['company_name'];
                    $contentfulProductSlug = isset($item->attributes['contentful_product_slug']) ? $item->attributes['contentful_product_slug'] : "";

                    $this->darryl_shoppingcart_instance->update($item->id,
                        array('attributes' => array('sub_type' => $subType, 'duration' => $duration, 'monthly_price' => $monthlyPrice, 'annual_price' => $annualPrice, 'coupon' => $promotionCode, 'savings' => $savings,
                            'product_color' => $productColor, 'product_billing_type_code' => $billingTypeCode, 'subscription_frequency_unit_of_measurement' => $subsFreq, 'product_type' => $productType, 'duns' => $duns, 'company_name' => $companyName, 'contentful_product_slug' => $contentfulProductSlug)
                        ));
                }
                else
                {
                    //Updating nothing just so that the order does not change in the cart
                    $this->darryl_shoppingcart_instance->update($item->id,
                        array('attributes' => $item->attributes)
                    );
                }
            }

            switch($promo_type)
            {
                case "PCNTAGE":
                    $couponValue = (($promo_value / 100) * $totalProductAmt);
                    break;

                default:
                    if($promo_level == "ORDER")
                        $couponValue = $promo_value;
                    else
                        $couponValue = $promo_value * $noProductsWithCoupon;
                    break;
            }


            $couponCondition = new \Darryldecode\Cart\CartCondition(array(
                'name' => 'Discount',
                'type' => 'coupon',
                'target' => 'subtotal',
                'value' => '-' . $couponValue,
                'attributes' => array
                (
                    'couponCode' => $promotionCode,
                    'promo_value' => $promo_value,
                    'promo_level' => $promo_level,
                    'promo_type'  => $promo_type,
                )
            ));
            $this->darryl_shoppingcart_instance->condition($couponCondition);
            $status = true;
        }

        return $status;
    }

    /*
     * This function validates the entered coupon using OWL end-point
     *
     * This function checks if the coupon entered is valid or not by making a call to the OWL end-point.
     * If OWL returns success, then the coupon is applied. Else, an error message is passed.
     * @author Gaurav
     */
    public function validateCoupon($couponCodeParams)
    {
        $cartData = $this->getCartItems();
        $cartItems = $cartData['cartItems'];
        $owlFactory = new OwlFactory();
        $helper = new CartItemHelper();

        $productIds = [];
        for($i = 0; $i < count($cartItems); $i++)
        {
            $productIds[$i] = $cartItems[$i]['productId'];
        }

        $productIdString = implode(",",$productIds);
        $couponCodeParams['productId'] = $productIdString;
        $couponDetails = $owlFactory->getCouponDetails($couponCodeParams);

        if (isset($couponDetails['meta']))
        {
            switch ($couponDetails['meta']['code'])
            {
                case '200' :

                    $couponDetailsArray = isset($couponDetails['response']) ? $couponDetails['response'] : array();

                    //apply coupon
                    $successStatus = $this->applyCoupon($couponDetailsArray);

                    if($successStatus)
                    {
                        $setErrorResponse = $helper->returnError();
                        $returnCouponResponse = array_merge($setErrorResponse, $couponDetailsArray);

                        return $returnCouponResponse;
                    }
                    else
                    {
                        $setErrorResponse = array();
                        $setErrorResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                        $setErrorResponse['error_code'] = 'invalid_coupon';
                        $setErrorResponse['error_message'] = '';
                        return $setErrorResponse;
                    }

                default :

                    $this->log_message['couponDetails']['owl']['meta_code'] = isset($response['meta']['code']) ? $response['meta']['code'] : '';
                    $this->log_message['couponDetails']['owl']['message'] = isset($response['error'][0]) ? $response['error'][0] : '';
                    $this->log_factory->writeErrorLog($this->log_message);

                    $setErrorResponse = array();
                    $setErrorResponse['status'] = $this->config->get('Enums.Status.FAILURE');
                    $setErrorResponse['error_code'] = 'invalid_coupon';
                    $setErrorResponse['error_message'] = '';
                    return $setErrorResponse;
            }
        }
        else
        {
            $this->log_factory->writeErrorLog("Coupon Call Failure");
        }

        return $couponDetails;
    }

    /**
     * Re-validating coupon when any of the cart contents have been changed
     *
     * When the cart content changes and if the coupon is already applied then this function re-validates the coupon conditions on the cart by using OWL end-point
     * @author Rashmi
     */
    public function reValidateCoupon()
    {
        $promoUpdated = false;
        if(is_object($this->darryl_shoppingcart_instance->getCondition('Discount')))
        {
            $cartProducts = $this->darryl_shoppingcart_instance->getContent();
            foreach ($cartProducts as $item)
            {
                if (isset($item->attributes['coupon']) and $item->attributes['coupon'] != null)
                {
                    $couponResponse = $this->validateCoupon(array('promoCode' => $item->attributes['coupon']));
                    if (isset($couponResponse["status"]) && $couponResponse["status"] == 1)
                    {
                        $promoUpdated = true;
                    }
                    break;
                }
            }
        }

        //To handle the condition when coupon is applied at product level and that product gets deleted, then promo code condition value should be made 0
        if(!$promoUpdated)
        {
            $couponCondition = new \Darryldecode\Cart\CartCondition(array(
                'name' => 'Discount',
                'type' => 'coupon',
                'target' => 'subtotal',
                'value' => '-' . 0,
                'attributes' => array
                (
                    'couponCode' => null,
                    'promo_value' => '',
                    'promo_level' => '',
                    'promo_type'  => '',
                )
            ));
            $this->darryl_shoppingcart_instance->condition($couponCondition);
        }
    }

    /**
     *
     *
     * This function is used to get all the products in the cart.
     * @return $cartProducts
     * @author Aswin
     */

    public function getShoppingCart()
    {
         $cartProducts = $this->darryl_shoppingcart_instance->getContent();
         return $cartProducts;
    }

    /**
     * This function Empty's Cart.
     */

    public function clearEntireCart()
    {
        $this->darryl_shoppingcart_instance->clear();

        //If Shopping cart is empty then remove coupon condition
        $this->darryl_shoppingcart_instance->removeCartCondition('Discount');

    }

    /**
     * This function clears Shopping Cart Cookie and Empty Cart.
     * @param CrossCookieMock $crossCookie for unit testing
     */

    public function clearCartSessionAndCookie($crossCookie = null)
    {
        if(empty($crossCookie))
        $crossCookie = new CrossCookie();

        $crossCookie->removeShoppingCartCookie();

        $this->clearEntireCart();
    }


    /**
     *
     *
     * This function is used to retrieve all darryl shopping cart's cart item and cart condition sessions
     * @return array $darrylSession
     * @author aprakash
     */
    public function checkDarrylCartItemAndConditionSession()
    {
        $darrylSession = array();
        $session = Session::all();
        $cartItemSession = null;
        $cartConditionSession = null;

        foreach($session as $key => $value) {
            if($session[$key] instanceof CartCollection)
                $darrylSession["cart_item_session"] = $session[$key];

            if($session[$key] instanceof CartConditionCollection)
                $darrylSession["cart_condition_session"] = $session[$key];

        }

        return $darrylSession;
    }
}