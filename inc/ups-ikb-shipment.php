<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$WooComVersion = UPSIKB_get_WooVerNum();
$UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
$UPSIKBApiUrl = UPSIKB_get_ApiUrl();

$WooComOrder = new WC_Order( $order_id );
$WooComShipping = $WooComOrder->get_items( 'shipping' );
$UPSserviceLevelOrderID = array_keys($WooComShipping);
$UPSServiceLevelID = wc_get_order_item_meta($UPSserviceLevelOrderID[0], 'UPS Service ID', $single);
$WooComMerchantCountryData = get_option( 'woocommerce_default_country' );
$WooComMerchantCountryState = explode( ":", $WooComMerchantCountryData );
$WooComMerchantCountry = $WooComMerchantCountryState[0];
$WooComMerchantState = $WooComMerchantCountryState[1];
$UPSIKBWooWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
$UPSIKBWooDimensionSetting = esc_attr( get_option('woocommerce_dimension_unit') );

$vatin = UPSIKB_get_VatIn($WooComOrder_meta["_shipping_country"][0]);

//if the selected order uses Simply International UPS Shipping method
if($UPSServiceLevelID[0] != '') {    
    //get shoppers shipping/billing information
    $WooComOrder_meta = get_post_meta($order_id);
    $items = $WooComOrder->get_items();
	$UPSIKBPartsList = array();
    foreach($items as $item => $values) {
        $product = wc_get_product( $values['product_id'] );
        if ( $product->is_type( 'variable' ) ) {
            $product = wc_get_product( $values['variation_id'] );
            $productSKU = $product->get_sku();
            if ($productSKU === '') {
                $productSKU = $values['variation_id'];
            }
        } else {
            $productSKU = $product->get_sku();
            if ($productSKU === '') {
                $productSKU = $values['product_id'];
            }
        }
        
		$productName = $product->get_title();
		
        $UPSIKBWooSKUWeight = get_post_meta($values['product_id'], '_weight', true);
        /*
		if($UPSIKBWooWeightSetting === 'oz') {
            //convert oz to lbs
            $UPSIKBWooSKUWeight = $UPSIKBWooSKUWeight / 16;
        } else if($UPSIKBWooWeightSetting === 'g') {
            //convert g to kg
            $UPSIKBWooSKUWeight = $UPSIKBWooSKUWeight / 1000;
        }
		*/
		$UPSIKBProdDescription = str_replace(chr(34), "'", $product->get_description());
		if($UPSIKBProdDescription === '') {
			$UPSIKBProdDescription = $productName;
		}

		$UPSIKBCountryOfOrigin = get_post_meta( $values['product_id'], 'UPSIKBCountryOfOrign', true );
		if( $UPSIKBCountryOfOrigin == '') {
			$UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
		}
        $UPSIKBPart = array(
            'sku' => $productSKU,
            'name' => $productName,
			'description' => preg_replace("/[\n\r]/", "", $UPSIKBProdDescription),
            'weight' => array('unit' => $UPSIKBWooWeightSetting, 'value' => floatval($UPSIKBWooSKUWeight)),
            'price' => array('unit' => get_woocommerce_currency(), 'value' => strval($values['line_total'] / $values['quantity'])),
            'countryOfOrigin' => $UPSIKBCountryOfOrigin,
            'dimensions' => array('unit' => $UPSIKBWooDimensionSetting, 'length' => floatval(get_post_meta($values['product_id'], '_length', true)), 'width' =>  floatval(get_post_meta($values['product_id'], '_width', true)), 'height' => floatval(get_post_meta($values['product_id'], '_height', true))),
			'quantity' => floatval($values['quantity'])
        );
		
		array_push($UPSIKBPartsList, $UPSIKBPart);
    }
		
	$UPSIKBCreateShipment = array(
		"auth" => array(
			"AccessLicenseNumber" => "",
			"AuthenticationToken" => get_option('UPS_IKB_AuthorizationToken'),
			"Authorization" => get_option('UPS_IKB_Authorization'),
			"Username" => get_option('UPS_IKB_UPSUsername'),
			"ShipperNumber" => get_option('UPS_IKB_UPSAccountNumber'),
			"Password" => get_option('UPS_IKB_UPSPassword'),
			"Key" => get_option('UPS_IKB_APIKey')
		),
		"serviceCode" => $UPSServiceLevelID[0],
		"customer" => array(
			"shippingAddress" => array(
				"street" => array(
					"first" => strval($WooComOrder_meta["_shipping_address_1"][0]),
					"second" => strval($WooComOrder_meta["_shipping_address_2"][0]),
					"third" => ""
				),
				"city" => $WooComOrder_meta["_shipping_city"][0],
				"region" => $WooComOrder_meta["_shipping_state"][0],
				"regionCode" => $WooComOrder_meta["_shipping_state"][0],
				"postalCode" => $WooComOrder_meta["_shipping_postcode"][0],
				"countryCode" => $WooComOrder_meta["_shipping_country"][0],
				"name" => array(
					"first" => $WooComOrder_meta["_shipping_first_name"][0],
					"middle" => "",
					"last" => $WooComOrder_meta["_shipping_last_name"][0]
				)
			),		
			"billingAddress" => array(
				"street" => array(
					"first" => strval($WooComOrder_meta["_billing_address_1"][0]),
					"second" => strval($WooComOrder_meta["_billing_address_2"][0]),
					"third" => ""
				),
				"city" => $WooComOrder_meta["_billing_city"][0],
				"region" => $WooComOrder_meta["_billing_state"][0],
				"regionCode" => $WooComOrder_meta["_billing_state"][0],
				"postalCode" => $WooComOrder_meta["_billing_postcode"][0],
				"countryCode" => $WooComOrder_meta["_billing_country"][0],
				"name" => array(
					"first" => $WooComOrder_meta["_billing_first_name"][0],
					"middle" => "",
					"last" => $WooComOrder_meta["_billing_last_name"][0]
				)
			),
			"phone" => preg_replace('/\D+/', '', $WooComOrder_meta["_billing_phone"][0]),
		),
		"vci"=> array(
                "type"=> count($vatin)>0 ? $vatin['vatin_type'] : '',
                "region"=> count($vatin)>0 ? $vatin['vatin_country'] : '',
                "number"=> count($vatin)>0 ? $vatin['vatin'] : ''
            ),
		"merchant" => array(
			"merchantName" => get_bloginfo("name"),
            "shippingAddress" => array (
                "street" => array(
                    "first" => strval(get_option("woocommerce_store_address")),
                    "second" => strval(get_option("woocommerce_store_address_2")),
                    "third" => ""
                ),
                "city" => get_option("woocommerce_store_city"),
                "region" => $WooComMerchantState,
                "regionCode" => $WooComMerchantState,
                "postalCode" => get_option("woocommerce_store_postcode"),
                "countryCode" => $WooComMerchantCountry,
            ),
			"phone" => preg_replace('/\D+/', '', get_option("UPS_IKB_MerchantPhone"))
		),
		"partsList" => $UPSIKBPartsList,
		"meta" => array(
            "platform" => "WooCommerce",
            "platformVersion" => $WooComVersion,
            "pluginVersion" => $UPSIKBVersion,
			"custom" => array(
			  "WooComOrderNum" => strval($order_id)
			)
        )
	);
	
	$UPSIKBCreateShipmentRequest = array(
        'body' => json_encode($UPSIKBCreateShipment),
        'timeout' => '15',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
        'cookies' => array()
    );
    $UPSIKBshipmentResponse = wp_remote_post( $UPSIKBApiUrl.'shipment', $UPSIKBCreateShipmentRequest );
	//error_log('SimpInt - Request to /shipment: '. json_encode($UPSIKBCreateShipment));
    $UPSIKBshipmentResponseBody = wp_remote_retrieve_body($UPSIKBshipmentResponse);
	//error_log('SimpInt - Response from /shipment: '. $UPSIKBshipmentResponseBody);
    $UPSIKBshipmentResp = json_decode($UPSIKBshipmentResponseBody, TRUE);
    $UPSIKBTrackingNum = $UPSIKBshipmentResp['trackingNumber'];
	$EncodedUPS_IKB_APIKey = urlencode(get_option('UPS_IKB_APIKey'));
    $WooComOrder->add_order_note('Shipment has been created in UPS. Tracking Number: <a href="https://www.ups.com/track?loc=null&tracknum='. $UPSIKBTrackingNum .'" target="_blank">'. $UPSIKBTrackingNum .'</a><br/><br/><br/><a href="'.$UPSIKBApiUrl.'getlabel?tracknum='. $UPSIKBTrackingNum .'&key='. $EncodedUPS_IKB_APIKey .'" target="_blank">Click Here</a> to get your shipping label.');
	/*if(!empty($vatin))
	{
		$WooComOrder->add_order_note('Applicable VAT ID for this order:'.$vatin_type.' '.$vatin);
	}*/
	//remove comment to see JSON Payment as order note in WooCommerce Order Details
	//$WooComOrder->add_order_note(json_encode($UPSIKBCreateShipment));
	//delete first name and last name cookies set during checkout - not needed after we create shipment
	setcookie("simpInt_shippingLastName", "", time() - 3600);
	setcookie("simpInt_shippingFirstName", "", time() - 3600);
	setcookie("simpInt_billingLastName", "", time() - 3600);
	setcookie("simpInt_billingFirstName", "", time() - 3600);
	
}
?>