<?php
/**
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * @copyright Copyright (c) WHMCS Limited 2015
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

require_once __DIR__ . '/../../init.php';

function hook_shopier_invoicecancelled(array $params)
{
	$gatewayModuleName = 'shopier';
	$gatewayParams = getGatewayVariables($gatewayModuleName);
	
	if (!$gatewayParams['type']) {
		return;
	}
    try {
         $invoiceid = $params['invoiceid'];
            $postfields = array(
                'API_key' => $gatewayParams['shopier_api_key'],
                'platform_order_id' => $invoiceid,
            );
            $data = implode('', $postfields);
            $signature = hash_hmac('SHA256', $data, $gatewayParams['shopier_api_secret'], true);
            $signature = base64_encode($signature);
            $postfields['signature'] = $signature;
			
			$url = $gatewayParams['shopier_cancel_url'];
			$response = curlCall($url, $postfields, $options);

            logTransaction($gatewayParams['name'], $postfields, $response);
    } catch (Exception $e) {
		logTransaction($gatewayParams['name'], $args, $e->getMessage());
    }
}

add_hook('InvoiceCancelled', 1, 'hook_shopier_invoicecancelled');
