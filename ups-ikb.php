<?php
/**
* Plugin Name:       UPS International Shipping
* Plugin URI:        http://www.ups.com/
* Description:       A plug-in to extend your WooCommerce system for International Shipments.
* Version:           2.0.0
* Author:            UPS
* Author URI:        https://www.ups.com/
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt\*/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    //add_action('admin_notices', 'ups_ikb_admin_requirements');
    add_action('admin_head', 'UPSIKB_css');
    function UPSIKB_css() {
        echo '<style>
        .upsikb {}
        .upsikb .postbox {}
        .upsikb .postbox.markupBox {display:none;}
        .upsikb .postbox.markdownBox {display:none;}
        .upsikb .postbox.flatRateBox {display:none;}
        .upsikb .postbox.serviceLevels {}
        .upsikb .postbox.serviceLevels table {width:100%;}
        .upsikb .postbox.serviceLevels table td {text-align:center;}
        .upsikb .postbox.serviceLevels table td input[type="text"] {width:100%;max-width:500px;margin-bottom:0px;padding:3px 6px;}
        .upsikb .postbox .inside {}
        .upsikb .postbox .inside label {text-align:left;min-width:225px;margin-right:10px;display:inline-block;vertical-align:initial!important;}
        .upsikb .postbox .inside input[type="text"], .upsikb .postbox .inside input[type="password"] {width:100%;max-width:500px;margin-bottom:10px;padding:3px 6px;}
        .upsikb .postbox .inside input[type="number"] {width:50px;margin-bottom:10px;padding:3px 0px 3px 6px;}
        .upsikb .postbox .inside .storedRates {margin:15px 0px;}
        .upsikb .postbox .inside .storedRates .ratesHead {}
        .upsikb .postbox .inside .storedRates .ratesHead label {display:inline-block;width:30%;font-weight:bold;padding:2px 5px;}
        .upsikb .postbox .inside .storedRates .ratesRow {}
        .upsikb .postbox .inside .storedRates .ratesRow:hover {background:#CCC;}
        .upsikb .postbox .inside .storedRates .ratesRow label {display:inline-block;width:30%;padding:2px 5px;}
        .upsikb .postbox .inside .storedRates .ratesRow span {display:inline-block;padding:0px;color:red;cursor:pointer;}
        .upsikb .postbox .inside .UPSIKBlabelRow {width:100%;display:block;margin-bottom:15px;}
        .upsikb .postbox .inside .UPSIKBlabelRow label {width:29%;font-weight:bold;margin:0px;text-align:center;}
        .upsikb .postbox .inside .UPSIKBrateInputRow {width:100%;display:block;}
        .upsikb .postbox .inside .UPSIKBrateInputRow p {display:inline;}
        .upsikb .postbox .inside .UPSIKBrateInputRow select {width:30%;margin:0px 2px;font-size:16px;vertical-align:top;display:inline-block;max-width:initial;}
        .upsikb .postbox .inside .UPSIKBrateInputRow input {width:30%;margin:0px 2px;font-size:16px;padding:0px 5px;vertical-align:top;}
        .upsikb .postbox .inside .UPSIKBrateInputRow label.addRowBtn {display:none;min-width:initial;padding:5px 0px 5px 5px;min-width:initial;color:green;}
        .upsikb .postbox .inside .UPSIKBrateInputRow:last-of-type label.addRowBtn {display:inline-block;}
        .upsikbtooltip {position:relative;display:inline-block;float:right;}
        .upsikbtooltip .upsikbtooltiptext {visibility:hidden;width:200px;background-color:#555;color:#fff;text-align:center;border-radius:6px;padding:5px;position:absolute;z-index:1;bottom:125%;left:50%;margin-left:-105px;opacity:0;transition:opacity 0.3s;font-size:11px;}
        .upsikbtooltip .upsikbtooltiptext::after {content:"";position:absolute;top:100%;left:50%;margin-left:-5px;border-width:5px;border-style:solid;border-color:#555 transparent transparent transparent;}
        .upsikbtooltip:hover .upsikbtooltiptext {visibility:visible;opacity:1;}
        </style>';
    }
    function UPSIKB_get_WooVerNum() {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];
        } else {
            return NULL;
        }
    }
    function UPSIKB_get_UPSIKBVerNum() {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        // Create the plugins folder and file variables
        $UPSIKBPluginFoder = UPSIKB_get_PluginFolder();

        $plugin_folder = get_plugins( '/' . $UPSIKBPluginFoder );
        $plugin_file = 'ups-ikb.php';
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];
        } else {
            return NULL;
        }
    }

    function UPSIKB_get_ApiUrl() {
        return 'https://epc-prd.azurefd.net/';
    }
    function UPSIKB_get_PluginFolder() {
        $UPSIKBApiUrl = UPSIKB_get_ApiUrl();
        if(strpos($UPSIKBApiUrl, 'epc-uat') !== false) {
            return 'tcb-ikb-plugin-woocommerce';
        } else {
            return 'simply-international-by-ups';
        }
    }
	function UPSIKB_get_VatIn($destination) {
		$noneucountry = array('AF','AL','DZ','AS','AD','AO','AI','AQ','AG','AR','AM','AW','AU','AZ','BS','BH','BD','BB','BY','BZ','BJ','BM','BT','BO','BQ','BA','BW','BV','BR','IO','BN','BF','BI','CV','KH','CM','CA','KY','CF','TD','CL','CN','CX','CC','CO','KM','CD','CG','CK','CR','CU','CW','CI','DJ','DM','DO','EC','EG','SV','GQ','ER','SZ','ET','FK','FO','FJ','GF','PF','TF','GA','GM','GE','GH','GI','GL','GD','GP','GU','GT','GG','GN','GW','GY','HT','HM','VA','HN','HK','IS','IN','ID','IR','IQ','IM','IL','JM','JP','JE','JO','KZ','KE','KI','KP','KR','KW','KG','LA','LB','LS','LR','LY','LI','MO','MG','MW','MY','MV','ML','MH','MQ','MR','MU','YT','MX','FM','MD','MC','MN','ME','MS','MA','MZ','MM','NA','NR','NP','NC','NZ','NI','NE','NG','NU','NF','MP','OM','PK','PW','PS','PA','PG','PY','PE','PH','PN','PR','QA','MK','RU','RW','RE','BL','SH','KN','LC','MF','PM','VC','WS','SM','ST','SA','SN','RS','SC','SL','SG','SX','SB','SO','ZA','GS','SS','LK','SD','SR','SJ','CH','SY','TW','TJ','TZ','TH','TL','TG','TK','TO','TT','TN','TR','TM','TC','TV','UG','UA','AE','UM','US','UY','UZ','VU','VE','VN','VG','VI','WF','EH','YE','ZM','ZW','AX');
				if (in_array($destination, $noneucountry))
				{
					$selectedShippingCountry = $destination;
				}elseif($destination=='NO')
				{
					$selectedShippingCountry ='NO';
				}elseif($destination=='GB')
				{
					$selectedShippingCountry ='GB';
				}else
				{
					$selectedShippingCountry ='EU';
				}
				$UPS_IKB_VatInJSON = get_option('UPS_IKB_VatIn');							
				$VatInArr = json_decode($UPS_IKB_VatInJSON, true);
				$vatreturn =array();
				if(!empty($UPS_IKB_VatInJSON) && count($VatInArr)>0) {
					foreach ($VatInArr as $key => $value) {
						$vatLoopVatin = explode('_', $key);
						$vatintype = $vatLoopVatin[0];
						$vatincountry = $vatLoopVatin[1];
						$vatLoopValue = $value;
						if($selectedShippingCountry==$vatincountry)
						{
							$vatreturn['vatin_type'] =$vatintype;
							$vatreturn['vatin_country'] =$vatincountry;
							$vatreturn['vatin'] =$vatLoopValue;
							break;
						}
					}
				}
				return $vatreturn;
	}
    function UPSIKB_config() { include('admin/ups-ikb-config.php'); }
    function UPSIKB_catalogSync() { include('inc/ups-ikb-catalog-sync.php'); }
    function UPSIKB_rateOverrides() { include('inc/ups-ikb-rate-overrides.php'); }
    add_action( 'wp_ajax_nopriv_UPSIKB_updateRate', 'UPSIKB_updateRate' );
    add_action( 'wp_ajax_UPSIKB_updateRate', 'UPSIKB_updateRate' );
    function UPSIKB_updateRate() {
        $rateType = sanitize_text_field($_POST['rateType']);
        if($rateType === 'flat') {
            $rateType = 'UPS_IKB_FlatRates';
        }
        else if($rateType === 'perc') {
            $rateType = 'UPS_IKB_MarkUps';
        }else {
            $rateType = 'UPS_IKB_MarkDowns';
        }
        $rateJSON = sanitize_text_field($_POST['rateJSON']);
        update_option( $rateType, stripslashes($rateJSON) ); 
    }
	
	add_action( 'wp_ajax_nopriv_UPSIKB_updateVatin', 'UPSIKB_updateVatin' );
    add_action( 'wp_ajax_UPSIKB_updateVatin', 'UPSIKB_updateVatin' );
    function UPSIKB_updateVatin() {
        $vatinJSON = sanitize_text_field($_POST['vatinJSON']);
        update_option( 'UPS_IKB_VatIn', stripslashes($vatinJSON) ); 
    }

    if(!function_exists('wp_get_current_user')) { include(ABSPATH . "wp-includes/pluggable.php"); }
    add_action( 'rest_api_init', function () {
        register_rest_route( 'tcb-ikb-plugin-woocommerce/v1', '/parts/', array(
        'methods' => 'POST',
        'callback' => 'proxy_catalogSync',
        'permission_callback' => 'proxy_ikb_security',
        ) );
    } );

    add_action( 'rest_api_init', function () {
        register_rest_route( 'tcb-ikb-plugin-woocommerce/v1', '/services/', array(
        'methods' => 'POST',
        'callback' => 'proxy_getService',
        'permission_callback' => 'proxy_ikb_security',
        ) );
    } );
	add_action( 'rest_api_init', function () {
        register_rest_route( 'tcb-ikb-plugin-woocommerce/v1', '/shippingmethod/', array(
        'methods' => 'POST',
        'callback' => 'proxy_getShippingmethod',
        'permission_callback' => 'proxy_ikb_security',
        ) );
    } );


    function proxy_ikb_security() {
	    // Should only be used by logged-in users capable of using the editor.
	   // return current_user_can( 'edit_page' );
        return true;
    }
    function proxy_getService( $data) {
          
            $UPSIKBServiceRequest = $data->get_json_params();
            $UPSIKBApiUrl = UPSIKB_get_ApiUrl();
			$UPSIKB_WPrequest = array(
				'method' => 'PUT',
				'body' => json_encode($UPSIKBServiceRequest),
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
				'cookies' => array()
			);
			$UPSIKB_response =  wp_remote_request( $UPSIKBApiUrl.'GetServices', $UPSIKB_WPrequest );
			$UPSIKB_responseBody = wp_remote_retrieve_body($UPSIKB_response);
			$UPSIKB_responseData = json_decode($UPSIKB_responseBody, TRUE);
            return new WP_REST_Response( $UPSIKB_responseData);
    }
    function proxy_catalogSync( $data) {

            $UPSIKB_SKUData = $data->get_json_params();
            $UPSIKBApiUrl = UPSIKB_get_ApiUrl();
			$UPSIKB_WPrequest = array(
				'method' => 'PUT',
				'body' => json_encode($UPSIKB_SKUData),
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
				'cookies' => array()
			);
			$UPSIKB_catalogResponse =  wp_remote_request( $UPSIKBApiUrl.'parts', $UPSIKB_WPrequest );
			$UPSIKB_catalogResponseBody = wp_remote_retrieve_body($UPSIKB_catalogResponse);
			$UPSIKB_responseData = json_decode($UPSIKB_catalogResponseBody, TRUE);
            return new WP_REST_Response( $UPSIKB_responseData);
          /* print_r(current_user_can( 'manage_options' ));
           $user_data = get_userdata( get_current_user_id() );
           if ( is_object( $user_data) ) {
    $current_user_caps = $user_data->allcaps;
    
    // print it to the screen
    echo '<pre>' . print_r( $current_user_caps, true ) . '</pre>';
}
        //   return new WP_REST_Response( json_decode($user_data));*/
    }
	
	function proxy_getShippingmethod( $data) {
		global $wpdb;
			$UPSIKBServiceID = $data->get_json_params();
            if(!session_id()) {
				session_start();
			}
			
			if(isset($_SESSION['shipapiresponse'] ))
			{
				$UPSIKB_response = $_SESSION['shipapiresponse'];
				$UPSIKB_responseData = json_decode($UPSIKB_response, TRUE);
			}else
			{
				return false;
			}
			
			// if(isset($_COOKIE['shipapiresponse'])) {
				// $UPSIKB_response = $_COOKIE['shipapiresponse'];
				// $UPSIKB_responseData = json_decode($UPSIKB_response, TRUE);
			// }else
			// {
				// return false;
			// }
			
			if(!empty($UPSIKB_responseData)) {
				foreach($UPSIKB_responseData as $UPSIKBService) {
					$UPSIKBServiceLevelID = $UPSIKBService['rates']['serviceCode'];
					if($UPSIKBServiceLevelID == $_POST['ServiceCode'])
					{
						$UPSIKB_responseData = $UPSIKBService['rates'];
						if(count($UPSIKB_responseData['shipmentItems'])>0)
						{
							for($ic=0; $ic< count($UPSIKB_responseData['shipmentItems']); $ic++)
							{
								// $productdata = wc_get_product(wc_get_product_id_by_sku($UPSIKB_responseData['shipmentItems'][$ic]['sku']));
								// $productid = wc_get_product_id_by_sku($UPSIKB_responseData['shipmentItems'][$ic]['sku']);
								$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $UPSIKB_responseData['shipmentItems'][$ic]['sku'] ) );

								if($product_id)
								{
									$productdata = wc_get_product($product_id);
									$UPSIKB_responseData['shipmentItems'][$ic]['productName']=$productdata->get_title();
								}else{
									$UPSIKB_responseData['shipmentItems'][$ic]['productName']=$UPSIKB_responseData['shipmentItems'][$ic]['sku'];
								}
								
							}
						}
						// print_r($_POST);
						// print_r($UPSIKB_responseData);
						// exit;
						break;
					}
				}
			}
			
			$UPSIKB_responsesend = json_encode($UPSIKB_responseData);
            return $UPSIKB_responsesend;
    }

    //Add Menu Items to WP-Admin
    add_action('admin_menu', 'WPAdminMenu');
    function WPAdminMenu() {
        $UPSIKBPluginFoder = UPSIKB_get_PluginFolder();
        //add_menu_page( 'UPS', 'International', 'manage_options', 'ups-ikb-config.php', 'UPSIKB_config', plugin_dir_url( __DIR__ ).'simply-international-by-ups/assets/ups-icon.png' );
        add_menu_page( 'UPS', 'International', 'manage_options', 'ups-ikb-config.php', 'UPSIKB_config', plugin_dir_url( __DIR__ ).$UPSIKBPluginFoder.'/assets/ups-icon.png' );
        add_submenu_page( 'ups-ikb-config.php', 'Settings', 'Settings', 'manage_options', 'ups-ikb-config.php', 'UPSIKB_config' );
		add_submenu_page( 'ups-ikb-config.php', 'Catalog Sync', 'Catalog Sync', 'manage_options', 'ups-ikb-catalog-sync.php', 'UPSIKB_catalogSync' );
        add_submenu_page( 'ups-ikb-config.php', 'Adjustments', 'Adjustments', 'manage_options', 'ups-ikb-rate-overrides.php', 'UPSIKB_rateOverrides' );
        //add_submenu_page( 'ups-ikb-config.php', 'Shipping Options', 'Shipping Options', 'manage_options', 'admin.php?page=wc-settings&tab=shipping', '' );
    }
	
	//Create Options
	add_option( 'UPS_IKB_AuthorizationToken', '' );
	add_option( 'UPS_IKB_Authorization', '' );
	add_option( 'UPS_IKB_UPSUsername', '' );
	add_option( 'UPS_IKB_UPSPassword', '' );
	add_option( 'UPS_IKB_UPSAccountNumber', '' );
	add_option( 'UPS_IKB_APIKey', '');
	add_option( 'UPS_IKB_ShowTaxAndDuty', 'on');
	add_option( 'UPS_IKB_TaxDutyLabel', 'Estimated Tax and Duty');
	add_option( 'UPS_IKB_MerchantPhone', '');
	add_option( 'UPS_IKB_DPS', 'off');
	add_option( 'UPS_IKB_ShowTrackingOpts', 'off');
	add_option( 'UPS_IKB_ShowMarkUpOpts', 'off');
	add_option( 'UPS_IKB_ShowMarkDownOpts', 'off');
    add_option( 'UPS_IKB_ShowFlatRateOpts', 'off');
	add_option( 'UPS_IKB_ShowVatMessage', 'off');
	add_option( 'UPS_IKB_ServiceLevel_08', 'on' );
    add_option( 'UPS_IKB_ServiceLevel_08_label', 'WW EXPEDITED/2DA' );
	add_option( 'UPS_IKB_ServiceLevel_07', 'on' );
    add_option( 'UPS_IKB_ServiceLevel_07_label', 'WW EXPRESS' );
	add_option( 'UPS_IKB_ServiceLevel_54', 'on' );
    add_option( 'UPS_IKB_ServiceLevel_54_label', 'WW EXPRESS PLUS' );
	add_option( 'UPS_IKB_ServiceLevel_65', 'on' );
    add_option( 'UPS_IKB_ServiceLevel_65_label', 'WW EXPRESS PM/SAVER' );
	add_option( 'UPS_IKB_ServiceLevel_11', 'on' );
    add_option( 'UPS_IKB_ServiceLevel_11_label', 'WW STANDARD' );
	
	
    //Register options to save Config settings
    add_action( 'admin_init', 'register_config_settings' );
    function register_config_settings() {
        //API key from PSP - emailed to user/merchant
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_APIKey' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowTaxAndDuty');
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_TaxDutyLabel');
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_DPS' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowTrackingOpts' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowMarkUpOpts' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowMarkDownOpts' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowFlatRateOpts' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_MerchantPhone' );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ShowVatMessage' );
		register_setting( 'ups-ikb-markup-group', 'UPS_IKB_VatIn', array('type' => 'Object') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_08', array('default' => 'on') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_08_label', array('default' => 'WW EXPEDITED/2DA') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_07', array('default' => 'on') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_07_label', array('default' => 'WW EXPRESS') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_54', array('default' => 'on') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_54_label', array('default' => 'WW EXPRESS PLUS') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_65', array('default' => 'on') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_65_label', array('default' => 'WW EXPRESS PM/SAVER') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_11', array('default' => 'on') );
        register_setting( 'ups-ikb-config-group', 'UPS_IKB_ServiceLevel_11_label', array('default' => 'WW STANDARD') );
    }
	
    //Register options to save Catalog settings
    add_action( 'admin_init', 'register_catalog_settings' );
    function register_catalog_settings() {
        register_setting( 'ups-ikb-catalog-group', 'UPSIKB_catalogConfig' );
        register_setting( 'ups-ikb-catalog-group', 'UPSIKB_cat_OverrideCountryOfOrigin' );
    }
    //Register options to save perc. mark-ups and flat rates
    add_action( 'admin_init', 'register_markup_settings' );
    function register_markup_settings() {
        register_setting( 'ups-ikb-markup-group', 'UPS_IKB_MarkUps', array('type' => 'Object') );
        register_setting( 'ups-ikb-markup-group', 'UPS_IKB_MarkDowns', array('type' => 'Object') );
        register_setting( 'ups-ikb-markup-group', 'UPS_IKB_FlatRates', array('type' => 'Object') );
    }
    //Create shipping method require shipping include file
    add_filter('woocommerce_shipping_methods', 'add_ups_ikb_method');
    function add_ups_ikb_method( $methods ) {
        $methods['ups_ikb'] = 'WC_UPS_IKB_method';
        return $methods;
    }
	/**
        * Clear Shipping Rates Cache
    */
    add_filter('woocommerce_checkout_update_order_review', 'clear_wc_shipping_rates_cache');
    add_filter('woocommerce_cart_updated', 'clear_wc_shipping_rates_cache');
    function clear_wc_shipping_rates_cache(){
        $packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $key => $value) {
            $shipping_session = "shipping_for_package_$key";
            unset(WC()->session->$shipping_session);
        }
    }
    add_action( 'woocommerce_shipping_init', 'ups_ikb_method' );
    function ups_ikb_method() {
        require_once 'inc/ups-ikb-shipping.php';
    }
    //Create shipment after successful payment - status from pending to processing
    add_action('woocommerce_order_status_pending_to_processing', 'ups_ikb_create_shipment', 10, 1);
    //Create shipment after successful payment - status from on hold to processing
    add_action('woocommerce_order_status_on-hold_to_processing', 'ups_ikb_create_shipment', 10, 1);
    function ups_ikb_create_shipment($order_id){
        require_once 'inc/ups-ikb-shipment.php';
    }
    //create country of origin input for woocommerce product
    add_action('woocommerce_product_options_advanced', 'ups_ikb_custom_wc_options');
    function ups_ikb_custom_wc_options() {
        echo '<div class="options_group">';
        woocommerce_wp_text_input(
            array( 
                'id'          => 'UPSIKBCountryOfOrign', 
                'label'       => __( 'Country of Origin<br/>(Required for estimated duties & taxes)', 'woocommerce' ), 
                'placeholder' => '',
                'desc_tip'    => 'true',
                'description' => __( 'Enter the two character country country code where this product was manufactured', 'woocommerce' ) 
            )
        );
        echo '</div>';
    }
    //save cutom woocommerce product inputs
    add_action('woocommerce_process_product_meta', 'ups_ikb_custom_wc_options_save');
    function ups_ikb_custom_wc_options_save($post_id) {
        $woocomUPSIKBCountryOfOrign = sanitize_text_field($_POST['UPSIKBCountryOfOrign']);
	    update_post_meta( $post_id, 'UPSIKBCountryOfOrign', esc_attr( $woocomUPSIKBCountryOfOrign ) );
    }
    //setup auto sync options using wp-cron
    $UPSIKBSyncCheck = esc_attr(get_option('UPSIKB_catalogConfig'));
    if($UPSIKBSyncCheck == 2) {
        if ( ! wp_next_scheduled( 'UPSIKB_catalog_sync_cron' ) ) {
            wp_schedule_event( time(), 'hourly', 'UPSIKB_catalog_sync_cron' );
        }
    } else {
        $UPSIKBCronTimestamp = wp_next_scheduled( 'UPSIKB_catalog_sync_cron' );
        wp_unschedule_event( $UPSIKBCronTimestamp, 'UPSIKB_catalog_sync_cron' );
    }
    
    function UPSIKB_catalog_sync_cron_func() {
		//run catalog sync daily
		UPSIKB_catalogSync();
		UPSIKB_runCatalogSync('cron');
    }
    add_action( 'UPSIKB_catalog_sync_cron',  'UPSIKB_catalog_sync_cron_func' );
 
    function UPSIKB_catalog_sync_on_save($post_id) {
		global $post;
        $WooComVersion = UPSIKB_get_WooVerNum();
        $UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
        $UPSIKBApiUrl = UPSIKB_get_ApiUrl();

        //$UPSIKBSyncCheck = esc_attr();
        if(get_option('UPSIKB_catalogConfig') == 1) {
            //global $post;
            $slug = 'product';
            $UPSIKBPostType = get_post_type($post_id);
            if ( $slug != $UPSIKBPostType ) {
                return;
            }
            // Check if product is variable and send variant SKU data
            $UPSIKBProduct = new WC_Product_Variable($post_id);
            $UPSIKBProdVars = $UPSIKBProduct->get_available_variations();			
            if(!empty($UPSIKBProdVars)) {
                // get IDs of main product and variants
                foreach ( $UPSIKBProdVars as $UPSIKBProdVar ) {
                    
                    // Product Name
                    $UPSIKBProdName = get_the_title($UPSIKBProdVar['variation_id']);
                    if(strpos($UPSIKBProdName, 'Variation') !== false) {
                        $UPSIKBProdName = explode (' ', $UPSIKBProdName, 4);
                        $UPSIKBProdName = $UPSIKBProdName[3];
                    }		
					
					$WooSKU = get_post_meta($UPSIKBProdVar['variation_id'], '_sku', true);
                    if($WooSKU == '') {
                        $WooSKU = $UPSIKBProdName;
                    }
					
		            $UPSIKBProdDescription = str_replace(chr(34), "'", strip_tags($UPSIKBProdVar['variation_description']));
                    // Get Country of Origin
                    $UPSIKBCountryOfOrigin = get_post_meta($post_id, 'UPSIKBCountryOfOrign', true);
					if( $UPSIKBCountryOfOrigin == '') {
						$UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
					}
                    //get Weight unit and convert based on selected value
                    $UPSIKBMerchantWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
                    $UPSIKBSKUWeight = get_post_meta($UPSIKBProdVar['variation_id'], '_weight', true);

                    $UPSIKB_SKUs[] = array(
                        'sku' => $WooSKU,
						'name' => $UPSIKBProdName,
						'description' => $UPSIKBProdDescription,
                            'weight' => array(
                            'unit' => $UPSIKBMerchantWeightSetting,
                            'value' => floatval($UPSIKBSKUWeight)
                            ),
                        'price' => array(
                            'unit' => 'USD',
                            'value' => get_post_meta($UPSIKBProdVar['variation_id'], '_price', true)
                        ),
                        'countryOfOrigin' => $UPSIKBCountryOfOrigin,
						'length' => floatval(get_post_meta($post_id, '_length', true)),
						'width' =>  floatval(get_post_meta($post_id, '_width', true)),
						'height' => floatval(get_post_meta($post_id, '_height', true))
                    );
                }
            }
            //Send original product data
            
			$UPSIKBProdName = $post->post_title;
			$WooSKU = get_post_meta($post->ID, '_sku', true);
            if($WooSKU == '') {
                $WooSKU = $UPSIKBProdName;
            }
			$UPSIKB_SyncProduct = wc_get_product($post_id);
			$UPSIKBProdDescription = strip_tags($UPSIKB_SyncProduct->description);
            // Get Country of Origin
            $UPSIKBCountryOfOrigin = get_post_meta($post_id, 'UPSIKBCountryOfOrign', true);
            if( $UPSIKBCountryOfOrigin == '') {
                $UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
            }
            //get Weight unit and convert based on selected value
            $UPSIKBMerchantWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
            $UPSIKBSKUWeight = $post->_weight;
            /*
			if($UPSIKBMerchantWeightSetting === 'oz') {
                //convert oz to lbs
                $UPSIKBSKUWeight = $UPSIKBSKUWeight / 16;
            } else if($UPSIKBMerchantWeightSetting === 'g') {
                //convert g to kg
                $UPSIKBSKUWeight = $UPSIKBSKUWeight / 1000;
            }
			*/
            $UPSIKB_SKUs[] = array(
                'sku' => $WooSKU,
                'name' => $UPSIKBProdName,
                'description' => $UPSIKBProdDescription,
                'weight' => array(
                    'unit' => $UPSIKBMerchantWeightSetting,
                    'value' => floatval($UPSIKBSKUWeight)
                ),
				'dimensions' => array(
					'unit' => 'in',
					'length' => floatval(get_post_meta($post_id, '_length', true)),
					'width' =>  floatval(get_post_meta($post_id, '_width', true)),
					'height' => floatval(get_post_meta($post_id, '_height', true))
				),
                'price' => array(
                    'unit' => 'usd',
                    'value' => get_post_meta($post_id, '_price', true)
                ),
                'countryOfOrigin' => $UPSIKBCountryOfOrigin
            );
            // Put data in array
            $UPSIKB_SKUData = array(
                'auth' => array(
					'accessLicenseNumber' => "",
					'authenticationToken' => get_option('UPS_IKB_AuthorizationToken'),
					'authorization' => get_option('UPS_IKB_Authorization'),
					'username' => get_option('UPS_IKB_UPSUsername'),
					'password' => get_option('UPS_IKB_UPSPassword'),
					'shipperNumber' => get_option('UPS_IKB_UPSAccountNumber'),
                    'key' => get_option('UPS_IKB_APIKey')
                ),
                'partsList' => $UPSIKB_SKUs,
                'meta' => array(
                    'platform' => 'WooCommerce',
                    'platformVersion' => $WooComVersion,
                    'pluginVersion' => $UPSIKBVersion
                )
            );
            $UPSIKB_WPrequest = array(
                'method' => 'PUT',
                'body' => json_encode($UPSIKB_SKUData),
                //'timeout' => '5',
                //'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
                'cookies' => array()
            );
            $UPSIKB_catalogResponse =  wp_remote_request( $UPSIKBApiUrl.'parts', $UPSIKB_WPrequest );
			error_log('SimpInt - Single Product Request to /parts: '. json_encode($UPSIKB_SKUData));
            $UPSIKB_catalogResponseBody = wp_remote_retrieve_body($UPSIKB_catalogResponse);
            error_log('SimpInt - Single Product Response from /parts: '. $UPSIKB_catalogResponseBody);
			$UPSIKB_responseData = json_decode($UPSIKB_catalogResponseBody, TRUE);
        }
	}
	
    add_action('woocommerce_update_product', 'UPSIKB_catalog_sync_on_save', 10, 1);
    //add_action('woocommerce_new_product', 'UPSIKB_catalog_sync_on_save', 10, 1);
	
	//create jQuery function on front-end if "Enable Tracking Options" is on
	if(get_option('UPS_IKB_ShowTrackingOpts') === 'on') {
		function UPSIKBTracking() { ?>
			<script>  	
				function UPSIntlTrackingPopUp() {
					//console.log('Show Tracking Pop-up');
					if(jQuery('#UPSSimplyInternationalTrackingPopUp').hasClass('show')) {
						jQuery('#UPSSimplyInternationalTrackingPopUp').removeClass('show');	
					} else {
						jQuery('#UPSSimplyInternationalTrackingPopUp').addClass('show');	
					}
				}
			</script>
			<style>
				#UPSSimplyInternationalTrackingPopUp {display:none;position:fixed;top:0px;left:0px;right:0px;bottom:0px;background-color:rgba(0,0,0,.50);width:100%;height:100%;z-index:9999;}
				#UPSSimplyInternationalTrackingPopUp.show {display:block;}
				#UPSSimplyInternationalTrackingPopUp .closeUPSSimplyIntlTrackingPopup {display:block;position:absolute;top:5px;right:10px;font-size:14px;cursor:pointer;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent {display:block;position:absolute;top:10%;left:0px;right:0px;width:100%;max-width:800px;height:auto;margin:auto;padding:30px;background:#FFF;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent form {display:block;position:relative;margin-bottom: 30px;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent form input#UPSTrackingNumber {display:block;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent form input#UPSTrack {display:block;position:absolute;margin:0px;padding: 17px;top: 0px;right: 0px;line-height: 1;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent .trackingResults {display:none;width:100%:}
				#UPSSimplyInternationalTrackingPopUp .trackingContent .trackingResults h2 {text-align:center;margin:0px;padding:0px;}
				#UPSSimplyInternationalTrackingPopUp .trackingContent .trackingResults table {width:100%;margin:15px 0px;font-size:14px;}
			</style>
			<div id="UPSSimplyInternationalTrackingPopUp">
				<div class="trackingContent">
					<div class="closeUPSSimplyIntlTrackingPopup" onclick="UPSIntlTrackingPopUp();">close</div>
					<!-- //function to execute AJAX request to tracking - onsubmit="return UPSSimplyIntlTrack(event)" -->
					<form action="http://wwwapps.ups.com/WebTracking/track?track=yes" target="_blank" method="GET">
						<input type="text" name="trackNums" id="trackNums" placeholder="Your UPS Tracking Number" />
						<input type="submit" name="UPSTrack" id="UPSTrack" value="Track" />
					</form>
					<!--<div class="trackingResults"></div>-->
				</div>
			</div>
		<?php }
		add_action('wp_head','UPSIKBTracking');
		
		//Create shortcode for "UPSIntlTrackingPopUp"
		function UPSSimplyIntlTrackingPopUpFunct() {	
			$UPSIKBTrackingHTML = '<a href="#" onclick="UPSIntlTrackingPopUp(); return false;">Track Your UPS Package</a>';
			return $UPSIKBTrackingHTML;
		}
		add_shortcode('UPSIntlTrackingPopUp', 'UPSSimplyIntlTrackingPopUpFunct');
	}
    else{
    	//Remove shortcode for "UPSIntlTrackingPopUp"
		function UPSSimplyIntlTrackingPopUpRemove() {	
			return '';
		}
		add_shortcode('UPSIntlTrackingPopUp', 'UPSSimplyIntlTrackingPopUpRemove');
    }
	
	//function to insert jQuery cookie plug-in to create firstname and lastname cookies on checkout page
	//needed as WooCom doesn't log first or last name until order is created and we need it for /rate to determine DPS
	function UPSIKBCheckoutCookies() {
		if(is_checkout()) { ?>
		<script>  	
			//console.log('in checkout page - watch inputs to create cookies');
			//check inputs to create cookies if name is stored in session/account info
			function simpIntUpdateCookie(elm, value) {
				Cookies.set(elm, value);
			}
			jQuery(document).ready(function() {
				Cookies.set('simpInt_billingFirstName', jQuery('.woocommerce-billing-fields #billing_first_name').val());
				Cookies.set('simpInt_billingLastName', jQuery('.woocommerce-billing-fields #billing_last_name').val());
				Cookies.set('simpInt_shippingFirstName', jQuery('.woocommerce-shipping-fields #shipping_first_name').val());
				Cookies.set('simpInt_shippingLastName', jQuery('.woocommerce-shipping-fields #shipping_last_name').val());
				
				jQuery('#billing_first_name').focusout(function() {
					simpIntUpdateCookie('simpInt_billingFirstName', jQuery(this).val());
				});
				jQuery('#billing_last_name').focusout(function() {
					simpIntUpdateCookie('simpInt_billingLastName', jQuery(this).val());
				});
				jQuery('#shipping_first_name').focusout(function() {
					simpIntUpdateCookie('simpInt_shippingFirstName', jQuery(this).val());
				});
				jQuery('#shipping_last_name').focusout(function() {
					simpIntUpdateCookie('simpInt_shippingLastName', jQuery(this).val());
				});
			});
		</script>
	<?php }
	}
	add_action('wp_head','UPSIKBCheckoutCookies');
	
	//timeout override for UPS international shipping - don't feel good about this - commented out to use default WC timeout
	//function UPSSimplyIntlRequestTimeout() {
	//	return 10;
	//}
	//add_filter('http_request_timeout', 'UPSSimplyIntlRequestTimeout');
	
	
	//add text after shipping rate
	/*
	add_action( 'woocommerce_after_shipping_rate', 'action_after_shipping_rate', 20, 2 );
	function action_after_shipping_rate ( $method, $index ) {
		// Targeting checkout page only:
		if( is_cart() ) return; // Exit on cart page
		// if( 'ups_ikb54' === $method->id ) {
			// echo __("<p>Arriving on your chosen date between 9am - 1pm Perfect for business addresses & special occasions</p>");
		// }
		// if( 'flat_rate:2' === $method->id ) {
			// echo __("<p>Arriving on your chosen date between 9am - 7pm Perfect for residential addresses</p>");
		// }
		 echo __('<p>Arriving on your chosen date between 9am - 7pm Perfect for residential addresses <a href="google.com">google</a></p>');
	}
	
	 
	add_action( 'woocommerce_review_order_before_submit', 'bbloomer_notice_shippings' );
	 
	function bbloomer_notice_shippings() {
	echo '<p class="allow">00000Please allow 5-10 business days for delivery after orderss processing.ssss</p>';
	}
	
	add_action( 'woocommerce_review_order_before_payment', 'bbloomer_notice_shippings_payment' );
	 
	function bbloomer_notice_shippings_payment() {
	echo '<p class="allow">Hello Payment</p>';
	}
	
	
	
	add_action( 'woocommerce_review_order_before_order_shipping_total', 'bbloomer_notice_shippings_payment_a_tot' );
	 
	function bbloomer_notice_shippings_payment_a_tot() {
	echo '<p class="allow">Hello before total</p>';
	}
	
	add_action( 'woocommerce_review_order_before_payment', 'add_vat_notice_shippings_page' );
	 
	function add_vat_notice_shippings_page() {
	echo '<p class="OrgVatNotice woocommerce-message" id="OrgVatNotice">This order may be subject to VAT collection prior to shipping</p>';
	}
	
	function add_vat_notice_shippings_page() {
			echo '<p class="OrgVatNotice woocommerce-message" id="OrgVatNotice">This order may be subject to VAT collection prior to shipping</p>';
	}*/
	add_action( 'wp_footer', 'add_vat_notice_script' );
	function add_vat_notice_script(){
	?>
		<script> jQuery(function($) { //alert( 'Hi Roy' );
		// $('.order-total').after('<tr><td id="OrgMessage">This order may be subject to VAT collection prior to shipping</td></tr>'); 
		 setInterval(function () {
                            if ($('#DummyVatNotice').length)
                            {
								if ($('#OrgMessage').length)
								{
									$("#OrgMessage").html($("#DummyVatNotice").text());
								}else
								{
									$('.order-total').after('<tr><td>&nbsp;</td><td class="woocommerce-message" id="OrgMessage">'+$("#DummyVatNotice").text()+'</td></tr>');
								}
                                $("#OrgMessage").show();
								// $("#OrgMessage" ).focus();
								 // $('html, body').animate({
									// scrollTop: $("#OrgMessage").offset().top
								// }, 2000);
								$("#DummyVatNotice").closest('div').hide();
                            }else
                            {
                                $("#OrgMessage").hide();
								$("#DummyVatNotice").closest('div').hide();
                            }
                         }, 1000);
		});
		
		</script> 
  <?php
	}
	if(get_option("UPS_IKB_ShowTaxAndDuty"))
	{
		add_action( 'woocommerce_review_order_before_payment', 'bbloomer_notice_shippings_payment' );
		 //
		function bbloomer_notice_shippings_payment() {
			echo '<a class="UpsCustomVatLink" id="UpsCustomVatLink" href="javascript:void(0)" data-upsselectedval="" style="display:none">See estimated duties & taxes details</a>';
		}
	}
	
	add_action( 'wp_footer', 'add_custom_ups_script', 1000,1000 );
	function add_custom_ups_script(){
	// Only on checkout page
	global $wp_session; 
     // if( !is_checkout() ) return;
	?>
	<style>
	.upsrTable {width: 100% !important;overflow-y: auto;}
	.upsrTableHeading, .upsrTableBody, .upsrTableFoot{clear: both;}
	.upsrTableRow{clear: both;width: 100%;}	
	.upsrTableHead, .upsrTableFoot{background-color: #DDD;font-weight: bold;}
	.upsrTableCell, .upsrTableHead {border: 1px solid #999999;float: left;height: 30px;overflow: hidden;padding: 3px 3.8%;width: 75%;}             
	.upsrTableCellFirst, .upsrTableHeadFirst {border: 1px solid #999999;float: left;height: 30px;overflow: hidden;padding: 3px 3.8%;width: 10%;}             
	.upsrTableCellLast, .upsrTableHeadLast {border: 1px solid #999999;float: left;height: 30px;overflow: hidden;padding: 3px 3.8%;width: 25%;}             
	.upsrTable:after {visibility: hidden;display: block;font-size: 0;content: " ";clear: both;height: 0;}
	#UPSInternationalVATPopUp {display:none;position:fixed;top:0px;left:0px;right:0px;bottom:0px;background-color:rgba(0,0,0,.50);width:100%;height:100%;z-index:9999;}
	#UPSInternationalVATPopUp.show {display:block;}
	#UPSInternationalVATPopUp .closeUPSIntlTrackingPopup {display:block;position:absolute;top:5px;right:10px;font-size:14px;cursor:pointer;}
	#UPSInternationalVATPopUp .VATtrackingContent {display:block;position:absolute;top:10%;left:0px;right:0px;max-width:650px;height:auto;margin:auto;padding:30px;background:#FFF;}
	.upsrTableCellText{margin-left: 10%;}
	</style>
<div class="upsrTable" id="UPSInternationalVATPopUp"><div class="VATtrackingContent">
<div class="closeUPSIntlTrackingPopup" onclick="javascript:UPSIntlVATPopUp();">&#10060;</div>
<span id="VatinAllData"><div class="upsrTableRow"><div class="upsrTableHead"><strong>Total Estimate of Cost for this shipment:</strong></div><div class="upsrTableHeadLast" id="grandTotal">0 USD</div></div>
<div class="upsrTableRow"><div class="upsrTableCell"><span class="upsrTableCellText">Total VAT:</span></div><div class="upsrTableCellLast" id="totalVAT">0 USD</div></div>
<div class="upsrTableRow"> <div class="upsrTableCell"><span class="upsrTableCellText">Total Duties:</span></div> <div class="upsrTableCellLast" id="totalDuties">0 USD</div> </div>
<div class="upsrTableRow"><div class="upsrTableCell"><span class="upsrTableCellText">Product Level Other Fees & Taxes:</span></div> <div class="upsrTableCellLast" id="totalCommodityLevelTaxesAndFees">0 USD</div> </div>
<div class="upsrTableRow"> <div class="upsrTableCell"><span class="upsrTableCellText">Shipment Level Other Fees & Taxes:</span></div> <div class="upsrTableCellLast" id="totalShipmentLevelTaxesAndFees">0 USD</div> </div>
<div class="upsrTableRow">  <div class="upsrTableCell"><span class="upsrTableCellText" >Brokerage Fees:</span></div> <div class="upsrTableCellLast" id="totalBrokerageFees">0 USD</div> </div>
<div class="upsrTableRow"><div class="upsrTableCell">&nbsp;</div> <div class="upsrTableCellLast">&nbsp;</div> </div>
<div class="upsrTableRow"> <div class="upsrTableCell"><strong>Product Details:</strong></div> <div class="upsrTableCellLast">&nbsp;</div> </div></span>
<span id="AllshipmentItems"></span>


</div> </div>
		<script> jQuery( function($){ //alert( 'Hi Roy' );
		// this code for vat message show
		 setInterval(function () {
                            if ($('#DummyVatNotice').length)
                            {
								if ($('#OrgMessage').length)
								{
									$("#OrgMessage").html($("#DummyVatNotice").text());
								}else
								{
									$('.order-total').after('<tr><td>&nbsp;</td><td class="woocommerce-message" id="OrgMessage">'+$("#DummyVatNotice").text()+'</td></tr>');
								}
                                $("#OrgMessage").show();
								$("#DummyVatNotice").closest('div').hide();
                            }else
                            {
                                $("#OrgMessage").hide();
								$("#DummyVatNotice").closest('div').hide();
                            }
                         }, 2000);
			// this code shipping data		
		
			$('#UpsCustomVatLink').click(function() {
				// var upsselectedval = $(this).attr('data-upsselectedval');
				 var overlay_a = '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table';
				
				var shipMethod = 'input[name^="shipping_method"]';
				var upsselectedval ='';
				 $(shipMethod).each( function (){
							if($(this).prop('checked') == true){
								upsselectedval = $(this).val();
							}
				 });
				 // alert(upsselectedval);
			  if(upsselectedval.indexOf('ups_ikb') != -1){
				$(overlay_a).block({
					message: null,
					overlayCSS: {
						background: "#fff",
						opacity: .6
					}
				});
				 var retstr = upsselectedval.replace('ups_ikb','');
				 $.ajax({
					url: '/wp-json/tcb-ikb-plugin-woocommerce/v1/shippingmethod',
					dataType : "json",
					data: {'ServiceCode':retstr},
					type : 'POST',
					crossDomain: true,
					// contentType : 'application/json',
					success: function (response) {
						console.log(response);
						// VatinAllData
						var response = JSON.parse(response);
						$("#grandTotal").html(response.grandTotal+' '+response.charge.unit);
						$("#totalVAT").html(response.totalVAT+' '+response.charge.unit);
						$("#totalDuties").html(response.totalDuties+' '+response.charge.unit);
						$("#totalCommodityLevelTaxesAndFees").html(response.totalCommodityLevelTaxesAndFees+' '+response.charge.unit);
						$("#totalShipmentLevelTaxesAndFees").html(response.totalShipmentLevelTaxesAndFees+' '+response.charge.unit);
						$("#totalBrokerageFees").html(response.totalBrokerageFees+' '+response.charge.unit);
						var productResponse='';    
						$.each(response.shipmentItems, function(i, object) {
							// alert(object.sku);
							if(object.productName==object.sku)
							{
								productResponse +='<div class="upsrTableRow"> <div class="upsrTableCell">'+object.productName+'</div> <div class="upsrTableCellLast">&nbsp;</div> </div>';
							}else
							{
								productResponse +='<div class="upsrTableRow"> <div class="upsrTableCell">'+object.productName+' ('+object.sku+')</div> <div class="upsrTableCellLast">&nbsp;</div> </div>';
							}
							
							productResponse +='<div class="upsrTableRow"><div class="upsrTableCell"><span class="upsrTableCellText">VAT:</span></div> <div class="upsrTableCellLast">'+object.commodityVAT+' '+response.charge.unit+'</div> </div><div class="upsrTableRow"> <div class="upsrTableCell"><span class="upsrTableCellText">Duties:</span></div> <div class="upsrTableCellLast">'+object.commodityDuty+' '+response.charge.unit+'</div> </div><div class="upsrTableRow"> <div class="upsrTableCell"><span class="upsrTableCellText">Other Fees & Taxes:</span></div> <div class="upsrTableCellLast">'+object.totalCommodityTaxesAndFees+' '+response.charge.unit+'</div> </div>'
						});
						
						$("#AllshipmentItems").html(productResponse);
						$(overlay_a).unblock();
						UPSIntlVATPopUp();
					},
					error : function(jqxhr) {
						console.log("error" );
					}
					});
			  }
					
					
					
			});
			
			
			
		});
		function UPSIntlVATPopUp() {
                    //console.log('Show Tracking Pop-up');
                    if(jQuery('#UPSInternationalVATPopUp').hasClass('show')) {
                        jQuery('#UPSInternationalVATPopUp').removeClass('show');
                    } else {
                        jQuery('#UPSInternationalVATPopUp').addClass('show');
                    }
		}
		jQuery( document.body ).on( 'updated_checkout ', function(){
			  var shipMethodNew = 'input[name^="shipping_method"]';
				var upsselectedvalnew ='';
				 jQuery(shipMethodNew).each( function (){
					 upsselectedvalnew =jQuery(this).val();
							if(upsselectedvalnew.indexOf('ups_ikb') != -1){
							jQuery(".UpsCustomVatLink").show();
						  }else
						  {
							  jQuery(".UpsCustomVatLink").hide();
						  }
				 });
				
			});
		</script> 
  <?php
	}
}
?>