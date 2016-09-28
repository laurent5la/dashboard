<?php

//@TODO endpoint for purchase history, subscription products, add payment method, update payment method, delete payment method

return array(
    'access_token'          => '/v1/oauth/token',
    'user_token'            => '/v1/user/token',
    'valid_user_token'      => '/v1/user/token/status/',
    'user_logout'           => '/v1/user/logout',
    'user_register'         => '/v1/user/register',
    'user_personal_update'  => '/v1/user/',
    'user_password_reset'   => '/v1.1/user/password/reset/',
    'user_password_change'  => '/v1/user/password/change/',
    'user_billing_info'     => '/v1/cart/user/billing_info/',
    'user_detail'          => '/v2/user/',
    'company_details'       => '/v1/business/search/',
    'user_entitlements'     => '/v1.1/user/entitlement',
    'pfs'                   => '/v1/cart/user/sales_order_pfs/',
    'signature'             => '/v1/cart/user/cybersource_signature',

);