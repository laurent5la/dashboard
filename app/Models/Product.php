<?php
namespace App\Models;

class Product {

    private $name;
    private $catalog_identifier;
    private $catalog_code;
    private $display_name;
    private $group_code;
    private $type_code;
    private $trade_reference_base_product_item_identifier;
    private $trade_reference_rejected;
    private $trade_reference_ordered;
    private $trade_reference_utilized;
    private $trade_reference_pending;
    private $trade_reference_available;
    private $trade_reference_accepted;
    private $entitlement_identifier;
    private $product_category_code;
    private $entitlement_start_date;
    private $entitlement_end_date;
    private $order_identifier;
    private $sales_order_item_identifier;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCatalogIdentifier()
    {
        return $this->catalog_identifier;
    }

    /**
     * @param mixed $catalog_identifier
     */
    public function setCatalogIdentifier($catalog_identifier)
    {
        $this->catalog_identifier = $catalog_identifier;
    }

    /**
     * @return mixed
     */
    public function getCatalogCode()
    {
        return $this->catalog_code;
    }

    /**
     * @param mixed $catalog_code
     */
    public function setCatalogCode($catalog_code)
    {
        $this->catalog_code = $catalog_code;
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->display_name;
    }

    /**
     * @param mixed $display_name
     */
    public function setDisplayName($display_name)
    {
        $this->display_name = $display_name;
    }

    /**
     * @return mixed
     */
    public function getGroupCode()
    {
        return $this->group_code;
    }

    /**
     * @param mixed $group_code
     */
    public function setGroupCode($group_code)
    {
        $this->group_code = $group_code;
    }

    /**
     * @return mixed
     */
    public function getTypeCode()
    {
        return $this->type_code;
    }

    /**
     * @param mixed $type_code
     */
    public function setTypeCode($type_code)
    {
        $this->type_code = $type_code;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceBaseProductItemIdentifier()
    {
        return $this->trade_reference_base_product_item_identifier;
    }

    /**
     * @param mixed $trade_reference_base_product_item_identifier
     */
    public function setTradeReferenceBaseProductItemIdentifier($trade_reference_base_product_item_identifier)
    {
        $this->trade_reference_base_product_item_identifier = $trade_reference_base_product_item_identifier;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceRejected()
    {
        return $this->trade_reference_rejected;
    }

    /**
     * @param mixed $trade_reference_rejected
     */
    public function setTradeReferenceRejected($trade_reference_rejected)
    {
        $this->trade_reference_rejected = $trade_reference_rejected;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceOrdered()
    {
        return $this->trade_reference_ordered;
    }

    /**
     * @param mixed $trade_reference_ordered
     */
    public function setTradeReferenceOrdered($trade_reference_ordered)
    {
        $this->trade_reference_ordered = $trade_reference_ordered;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceUtilized()
    {
        return $this->trade_reference_utilized;
    }

    /**
     * @param mixed $trade_reference_utilized
     */
    public function setTradeReferenceUtilized($trade_reference_utilized)
    {
        $this->trade_reference_utilized = $trade_reference_utilized;
    }

    /**
     * @return mixed
     */
    public function getTradeReferencePending()
    {
        return $this->trade_reference_pending;
    }

    /**
     * @param mixed $trade_reference_pending
     */
    public function setTradeReferencePending($trade_reference_pending)
    {
        $this->trade_reference_pending = $trade_reference_pending;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceAvailable()
    {
        return $this->trade_reference_available;
    }

    /**
     * @param mixed $trade_reference_available
     */
    public function setTradeReferenceAvailable($trade_reference_available)
    {
        $this->trade_reference_available = $trade_reference_available;
    }

    /**
     * @return mixed
     */
    public function getTradeReferenceAccepted()
    {
        return $this->trade_reference_accepted;
    }

    /**
     * @param mixed $trade_reference_accepted
     */
    public function setTradeReferenceAccepted($trade_reference_accepted)
    {
        $this->trade_reference_accepted = $trade_reference_accepted;
    }

    /**
     * @return mixed
     */
    public function getEntitlementIdentifier()
    {
        return $this->entitlement_identifier;
    }

    /**
     * @param mixed $entitlement_identifier
     */
    public function setEntitlementIdentifier($entitlement_identifier)
    {
        $this->entitlement_identifier = $entitlement_identifier;
    }

    /**
     * @return mixed
     */
    public function getProductCategoryCode()
    {
        return $this->product_category_code;
    }

    /**
     * @param mixed $product_category_code
     */
    public function setProductCategoryCode($product_category_code)
    {
        $this->product_category_code = $product_category_code;
    }

    /**
     * @return mixed
     */
    public function getEntitlementStartDate()
    {
        return $this->entitlement_start_date;
    }

    /**
     * @param mixed $entitlement_start_date
     */
    public function setEntitlementStartDate($entitlement_start_date)
    {
        $this->entitlement_start_date = $entitlement_start_date;
    }

    /**
     * @return mixed
     */
    public function getEntitlementEndDate()
    {
        return $this->entitlement_end_date;
    }

    /**
     * @param mixed $entitlement_end_date
     */
    public function setEntitlementEndDate($entitlement_end_date)
    {
        $this->entitlement_end_date = $entitlement_end_date;
    }

    /**
     * @return mixed
     */
    public function getOrderIdentifier()
    {
        return $this->order_identifier;
    }

    /**
     * @param mixed $order_identifier
     */
    public function setOrderIdentifier($order_identifier)
    {
        $this->order_identifier = $order_identifier;
    }

    /**
     * @return mixed
     */
    public function getSalesOrderItemIdentifier()
    {
        return $this->sales_order_item_identifier;
    }

    /**
     * @param mixed $sales_order_item_identifier
     */
    public function setSalesOrderItemIdentifier($sales_order_item_identifier)
    {
        $this->sales_order_item_identifier = $sales_order_item_identifier;
    }

}