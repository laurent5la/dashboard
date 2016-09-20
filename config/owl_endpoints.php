<?php

/**
 * @TODO : Add all OWL Endpoints for validation.
 */

return array(
    'access_token'          => '/v1/oauth/token',
    'user_token'            => '/v1/user/token',
    'valid_user_token'      => '/v1/user/token/status/',
    'pfs'                   => '/v1/cart/user/sales_order_pfs/',
    'signature'             => '/v1/cart/user/cybersource_signature',
    'user_logout'           => '/v1/user/logout',
    'user_register'         => '/v1/user/register',
    'user_detail'          => '/v2/user/',
    'user_password_reset'   => '/v1.1/user/password/reset/',
    'user_password_change'  => '/v1/user/password/change/',
    'user_billing_info'     => '/v1/cart/user/billing_info/',
    'product_details'       => '/v1/cart/product/price',
    'coupon_details'        => '/v1/cart/product/promotion/',
    'company_details'       => '/v1/business/search/',
    'user_entitlements'     => '/v1.1/user/entitlement'
);