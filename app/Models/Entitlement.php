<?php
namespace App\Models;

class Entitlement {

    private $product;
    private $business;


    public function _construct(Business $businessObj, Product $productObj)
    {
        $this->product = $productObj;
        $this->business = $businessObj;
    }

}