<?php
if ( ! class_exists( 'WC_UPS_IKB_method' ) ) {
	class WC_UPS_IKB_method extends WC_Shipping_Method {
		/**
		 * Constructor for your shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'ups_ikb';
			$this->method_title       = __( 'UPS International Shipping', 'woocommerce' );
			$this->method_description = __( 'A shipping option which provides a fully landed cost including Taxes and Duty for your international shoppers.' );
			$this->instance_id 		  = absint( $instance_id );
			$this->enabled			  = "no";
			$this->title              = "UPS International Shipping";
			$this->supports  = array(
				'shipping-zones'
			);
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		/**
		 * calculate_shipping function - get cart contents and run quote
		 * @access public
		 * @param mixed $package
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			if(is_cart() || is_checkout()) {

				global $woocommerce;
				$WooComVersion = UPSIKB_get_WooVerNum();
				$UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
				$UPSIKBApiUrl = UPSIKB_get_ApiUrl();

				$items = $woocommerce->cart->get_cart();
				$UPSIKBCountryShippingTo = $package[ 'destination' ][ 'country' ];
				$UPSIKBWooWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
				$WooComMerchantCountryData = get_option( 'woocommerce_default_country' );
				$WooComMerchantCountryState = explode( ":", $WooComMerchantCountryData );
				$WooComMerchantCountry = $WooComMerchantCountryState[0];
				$WooComMerchantState = $WooComMerchantCountryState[1];
				$UPSIKBWooDimensionSetting = esc_attr( get_option('woocommerce_dimension_unit') );


				$packages = $woocommerce->cart->get_shipping_packages();
				foreach( $packages as $package_key => $package ) {
						$session_key  = 'shipping_for_package_'.$package_key;
						$stored_rates = WC()->session->__unset( $session_key );
				}

				//get list of SKUs and Quantities from cart
				foreach($items as $item => $values) {
					$product = wc_get_product( $values['product_id'] );
					$productName = $product->get_title();
					if ( $product->is_type( 'variable' ) ) {
						$product = wc_get_product( $values['variation_id'] );
						$productSKU = $product->get_sku();
						if ($productSKU === '') {
							$productSKU = $values['variation_id'];
						}
					} else {
						$productSKU = $product->get_sku();
						if ($productSKU === '') {
							$productSKU = $productName;
						}
					}
					
					$UPSIKBWooSKUWeight = get_post_meta($values['product_id'], '_weight', true);
					//if($UPSIKBWooWeightSetting === 'oz') {
						//convert oz to lbs
						//$UPSIKBWooSKUWeight = $UPSIKBWooSKUWeight / 16;
					//} else if($UPSIKBWooWeightSetting === 'g') {
						//convert g to kg
						//$UPSIKBWooSKUWeight = $UPSIKBWooSKUWeight / 1000;
					//}
					$UPSIKBProdDescription = str_replace(chr(34), "'", $product->get_description());
					if($UPSIKBProdDescription === '') {
						$UPSIKBProdDescription = $productName;
					}
					$UPSIKBCountryOfOrigin = get_post_meta( $values['product_id'], 'UPSIKBCountryOfOrign', true );
					if( $UPSIKBCountryOfOrigin == '') {
						$UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
					}
					$UPSIKBPartsList[] = array(
						'sku' => $productSKU,
						'name' => $productName,
						'description' => preg_replace("/[\n\r]/", "", $UPSIKBProdDescription),
						'weight' => array('unit' => $UPSIKBWooWeightSetting, 'value' => floatval($UPSIKBWooSKUWeight)),
						'price' => array('unit' => get_woocommerce_currency(), 'value' => strval($values['line_total'] / $values['quantity'])),
						'countryOfOrigin' => strval($UPSIKBCountryOfOrigin),
						'dimensions' => array('unit' => $UPSIKBWooDimensionSetting, 'length' => floatval(get_post_meta($values['product_id'], '_length', true)), 'width' =>  floatval(get_post_meta($values['product_id'], '_width', true)), 'height' => floatval(get_post_meta($values['product_id'], '_height', true))),
						'quantity' => floatval($values['quantity'])
					);
				}

				//front-end jQuery is creating shipping and billing name cookies.  Grab to use in customer->shippingAddress->name
				//check for billing first name cookie - if not set, use "Johny" as first name
				if(!isset($_COOKIE['simpInt_billingFirstName'])) {
					$UPSIKB_billToFirstName = 'Johnny';
				} else {
					$UPSIKB_billToFirstName = $_COOKIE['simpInt_billingFirstName'];
				}
				
				//check for billing last name cookie - if not set, use "Dough" as first name
				if(!isset($_COOKIE['simpInt_billingLastName'])) {
					$UPSIKB_billToLastName = 'Dough';
				} else {
					$UPSIKB_billToLastName = $_COOKIE['simpInt_billingLastName'];
				}
				//check for shipping first name cookie - if not set, use "Johny" as first name - if it is set but empty, use billing first name value
				if(!isset($_COOKIE['simpInt_shippingFirstName'])) {
					$UPSIKB_shipToFirstName = 'Johnny';
				} else if (empty($_COOKIE['simpInt_shippingFirstName'])) {
					$UPSIKB_shipToFirstName = $UPSIKB_billToFirstName;
				} else {
					$UPSIKB_shipToFirstName = $_COOKIE['simpInt_shippingFirstName'];
				}
				//check for shipping last name cookie - if not set, use "Johny" as first name - if it is set but empty, use billing last name value
				if(!isset($_COOKIE['simpInt_shippingLastName'])) {
					$UPSIKB_shipToLastName = 'Dough';
				} else if (empty($_COOKIE['simpInt_shippingLastName'])) {
					$UPSIKB_shipToLastName = $UPSIKB_billToLastName;
				} else {
					$UPSIKB_shipToLastName = $_COOKIE['simpInt_shippingLastName'];
				}
				//build /rate array
				$UPSIKB_RateData = array(
					'auth' => array(
						'accessLicenseNumber' => "",
						'authenticationToken' => get_option('UPS_IKB_AuthorizationToken'),
						'authorization' => get_option('UPS_IKB_Authorization'),
						'username' => get_option('UPS_IKB_UPSUsername'),
						'password' => get_option('UPS_IKB_UPSPassword'),
						'shipperNumber' => get_option('UPS_IKB_UPSAccountNumber'),
						'key' => get_option('UPS_IKB_APIKey')
					),
					'customer' => array(
						'shippingAddress' => array (
							'street' => array(
								'first' => strval($package[ 'destination' ][ 'address' ]),
								'second' => strval($package[ 'destination' ][ 'address_2' ]),
								'third' => ''
							),
							'city' => $package[ 'destination' ][ 'city' ],
							'region' => $package[ 'destination' ][ 'state' ],
							'regionCode' => $package[ 'destination' ][ 'state' ],
							'postalCode' => $package[ 'destination' ][ 'postcode' ],
							'countryCode' => $package[ 'destination' ][ 'country' ],
							'name' => array(
								'first' => $UPSIKB_shipToFirstName,
								'middle' => '',
								'last' => $UPSIKB_shipToLastName
							)
						),
						'billingAddress' => array (
							'street' => array(
								'first' => strval($package[ 'destination' ][ 'address' ]),
								'second' => strval($package[ 'destination' ][ 'address_2' ]),
								'third' => ''
							),
							'city' => $package[ 'destination' ][ 'city' ],
							'region' => $package[ 'destination' ][ 'state' ],
							'regionCode' => $package[ 'destination' ][ 'state' ],
							'postalCode' => $package[ 'destination' ][ 'postcode' ],
							'countryCode' => $package[ 'destination' ][ 'country' ],
							'name' => array(
								'first' => $UPSIKB_billToFirstName,
								'middle' => '',
								'last' => $UPSIKB_billToLastName
							)
						)
					),
					'merchant' => array(
						'shippingAddress' => array (
							'street' => array(
								'first' => strval(get_option( 'woocommerce_store_address' )),
								'second' => strval(get_option( 'woocommerce_store_address_2' )),
								'third' => ''
							),
							'city' => get_option( 'woocommerce_store_city' ),
							'region' => $WooComMerchantState,
							'regionCode' => $WooComMerchantState,
							'postalCode' => get_option( 'woocommerce_store_postcode' ),
							'countryCode' => $WooComMerchantCountry
						)
					),
					'partsList' => $UPSIKBPartsList,
					'meta' => array(
						'platform' => 'WooCommerce',
						"platformVersion" => $WooComVersion,
						"pluginVersion" => $UPSIKBVersion
					)
				);
				$UPSIKBWPrequest = array(
					'body' => json_encode($UPSIKB_RateData),
					'timeout' => '15',
					'redirection' => '5',
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
					'cookies' => array()
				);
			

				$UPSIKBquoteResponse = wp_remote_post($UPSIKBApiUrl.'rate', $UPSIKBWPrequest);
				//log request in .log file in the root of plug-in file
				// echo json_encode($UPSIKB_RateData);
				// exit;
				// error_log('SimpInt - Request to /rate: '.json_encode($UPSIKB_RateData));
				$UPSIKBquoteResponseBody = wp_remote_retrieve_body($UPSIKBquoteResponse);
				//log request in .log file in the root of plug-in file
				// error_log('SimpInt - Response from /rate:'. $UPSIKBquoteResponseBody);
				$UPSIKBjsonQuoteResp = json_decode($UPSIKBquoteResponseBody, TRUE);					
				// Uncomment to see JSON request print on screen above cart and checkot form

				
				//echo('<p><strong>Request:</strong><pre>');
				//print_r($UPSIKBWPrequest);
				// echo('</pre><br/><strong>Response:</strong><pre>');
				// echo '---'.$UPSIKBjsonQuoteResp[0]['rates']['collectVat'];
				// print_r($UPSIKBWPrequest);
				// print_r($UPSIKBjsonQuoteResp);
				// echo('</pre></p>');
				//print_r($package);
				//die();

				
				$UPSVatMessage = 'This order may be subject to VAT collection prior to shipping';
				$UPSIKB_TaxAndDutyLabelVals = array('1' => 'Estimated Duties and Taxes', '2' => 'Estimated Duties & Taxes', '3' => 'Estimated D&T', '4' => 'Est. Duties and Taxes', '5' => 'Est. Duties & Taxes', '6' => 'Est. D&T',);
				//check first record to see if denied party is true - if one returned service is denied, ALL ARE.
				
				$vatin = UPSIKB_get_VatIn($package[ 'destination' ][ 'country' ]);
				
				if(is_checkout()) {
					wc_clear_notices();
					$hasMessage = false;
					if(get_option('UPS_IKB_DPS') === 'on' && $UPSIKBjsonQuoteResp[0]['rates']['deniedParty']) {
						//wc_add_notice($UPSIKBjsonQuoteResp[0]['rates']['deniedPartyMessage'], 'error'); //notice //error// color:#fff;background:#b22222'
						wc_add_notice("<p style='color:#EE0510'>".$UPSIKBjsonQuoteResp[0]['rates']['deniedPartyMessage']."</p>", 'success'); //notice //error
						$hasMessage = true;
					} 
					if(get_option('UPS_IKB_ShowVatMessage') === 'on' && !empty($UPSIKBjsonQuoteResp[0]['rates']['collectVat']) && count($vatin)>0) {
					// if(get_option('UPS_IKB_ShowVatMessage') === 'on') {
						wc_add_notice('<p style="color:#000000;" class="DummyVatNotice" id="DummyVatNotice">'.$UPSVatMessage.'</p>', 'success'); 
						$hasMessage = true;
	 
					}						
					
					if(!$hasMessage) {
						wc_clear_notices();
					}
				}
				
				// if(count($vatin)>0){ $applicableVatOrder =$vatin['vatin_type'].', '.$vatin['vatin']; }else { $applicableVatOrder =''; } 
				
				
				//code end for applicable vat data
				if( !headers_sent() && '' == session_id() ) {
					session_start();
				}
				$_SESSION['shipapiresponse']='';
				if(!empty($UPSIKBjsonQuoteResp)) {
					$_SESSION['shipapiresponse'] =$UPSIKBquoteResponseBody;
					foreach($UPSIKBjsonQuoteResp as $UPSIKBService) {			
						//check settings to send out correct method
						////checkbox inputs will return 'on' else will return '' (empty)
						$UPSIKBServiceLevelID = $UPSIKBService['rates']['serviceCode'];
						//check if service is enabled in config settings.  If not enabled, move to next record
						if(get_option('UPS_IKB_ServiceLevel_'. $UPSIKBServiceLevelID) === 'on') {
							$UPSDeniedParty_label = $UPSIKBService['rates']['deniedPartyMessage'];
							$UPSVatMessage_label = '';
							$applicableVatOrder ='';
							if(get_option('UPS_IKB_ShowVatMessage') === 'on' && !empty($UPSIKBService['rates']['collectVat']) && count($vatin)>0){
						
						// if(get_option('UPS_IKB_ShowVatMessage') === 'on'){
								$UPSVatMessage_label = $UPSVatMessage;
								$applicableVatOrder =$vatin['vatin_type'].', '.$vatin['vatin'];
							}
							
							
							$UPSIKB_ShowTaxAndDuty = get_option("UPS_IKB_ShowTaxAndDuty");
							$UPSIKB_TaxAndDutyLabel = $UPSIKB_TaxAndDutyLabelVals[get_option("UPS_IKB_TaxDutyLabel")] .' ';
							$UPSIKB_ServiceName = esc_attr( get_option('UPS_IKB_ServiceLevel_'. $UPSIKBServiceLevelID .'_label') );
							$UPSIKB_ServiceCost = floatval($UPSIKBService['rates']['charge']['value']);
							//check to see if mark-up and flat rate options are in place
							//// Mark-up first
							if(esc_attr( get_option('UPS_IKB_ShowMarkUpOpts') ) === 'on') {
								$UPSIKB_MarkUps = empty(get_option('UPS_IKB_MarkUps')) ? '{}' : get_option('UPS_IKB_MarkUps');
								$UPSIKB_MarkUpsJSON = json_decode($UPSIKB_MarkUps, true);

								if (array_key_exists($UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID, $UPSIKB_MarkUpsJSON)) {
									//take returned value if found and multiply the returned rate against it
									$UPSIKB_MarkUpValue = $UPSIKB_MarkUpsJSON[$UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID];
									$UPSIKB_MarkUpPerc = $UPSIKB_MarkUpValue / 100;
									$UPSIKB_ServiceCost = $UPSIKB_ServiceCost + (floatval($UPSIKB_ServiceCost) * floatval($UPSIKB_MarkUpPerc));
								}
								else if (array_key_exists('All_'. $UPSIKBServiceLevelID, $UPSIKB_MarkUpsJSON)) {
									//All Countries would override a specific country. So if they have All Countries + UPS Standard, if would override Canada + UPS Standard.
									$UPSIKB_MarkUpValue = $UPSIKB_MarkUpsJSON['All_'. $UPSIKBServiceLevelID];
									$UPSIKB_MarkUpPerc = $UPSIKB_MarkUpValue / 100;
									$UPSIKB_ServiceCost = $UPSIKB_ServiceCost + (floatval($UPSIKB_ServiceCost) * floatval($UPSIKB_MarkUpPerc));
								}
							}
							if(esc_attr( get_option('UPS_IKB_ShowMarkDownOpts') ) === 'on') {
								$UPSIKB_MarkDowns = empty(get_option('UPS_IKB_MarkDowns')) ? '{}' : get_option('UPS_IKB_MarkDowns');
								$UPSIKB_MarkDownsJSON = json_decode($UPSIKB_MarkDowns, true);

								if (array_key_exists($UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID, $UPSIKB_MarkDownsJSON)) {
									//take returned value if found and multiply the returned rate against it
									$UPSIKB_MarkDownValue = $UPSIKB_MarkDownsJSON[$UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID];
									$UPSIKB_MarkDownPerc = $UPSIKB_MarkDownValue / 100;
									$UPSIKB_ServiceCost = $UPSIKB_ServiceCost - (floatval($UPSIKB_ServiceCost) * floatval($UPSIKB_MarkDownPerc));
								}
								else if (array_key_exists('All_'. $UPSIKBServiceLevelID, $UPSIKB_MarkDownsJSON)) {
									//All Countries would override a specific country. So if they have All Countries + UPS Standard, if would override Canada + UPS Standard.
									$UPSIKB_MarkDownValue = $UPSIKB_MarkDownsJSON['All_'. $UPSIKBServiceLevelID];
									$UPSIKB_MarkDownPerc = $UPSIKB_MarkDownValue / 100;
									$UPSIKB_ServiceCost = $UPSIKB_ServiceCost - (floatval($UPSIKB_ServiceCost) * floatval($UPSIKB_MarkDownPerc));
								}
							}
							//// Flat Rate to override all
							if(esc_attr( get_option('UPS_IKB_ShowFlatRateOpts') ) === 'on') {
								$UPSIKB_FlatRates = empty(get_option('UPS_IKB_FlatRates')) ? '{}' : get_option('UPS_IKB_FlatRates'); 
								$UPSIKB_FlatRatesJSON = json_decode($UPSIKB_FlatRates, true);

								if (array_key_exists($UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID, $UPSIKB_FlatRatesJSON)) {
									//take returned value if found and use flat rate as main shipping cost
									$UPSIKB_FlatRateValue = $UPSIKB_FlatRatesJSON[$UPSIKBCountryShippingTo .'_'. $UPSIKBServiceLevelID];
									$UPSIKB_ServiceCost = $UPSIKB_FlatRateValue;
								}
								else if (array_key_exists('All_'. $UPSIKBServiceLevelID, $UPSIKB_FlatRatesJSON)) {
									//All Countries would override a specific country. So if they have All Countries + UPS Standard, if would override Canada + UPS Standard.
									$UPSIKB_FlatRateValue = $UPSIKB_FlatRatesJSON['All_'. $UPSIKBServiceLevelID];
									$UPSIKB_ServiceCost = $UPSIKB_FlatRateValue;
								}
							}
							$UPSIKB_ServiceTax = floatval($UPSIKBService['rates']['estimatedTax']['value']);
							$UPSIKB_ServiceDuty = floatval($UPSIKBService['rates']['estimatedDuty']['value']);
							$UPSIKB_TaxAndDuty = $UPSIKB_ServiceTax + $UPSIKB_ServiceDuty;

							//Shipping Method showing estimated tax and duty in method label
							$zerocostvalue = ($UPSIKB_ServiceCost==0 ? ': $0.00' : '');
							if ($UPSIKB_ShowTaxAndDuty === 'on') {
								if($UPSIKBService['rates']['estimatedTax']['value'] === "-1" || $UPSIKBService['rates']['estimatedDuty']['value'] === "-1") {
									$this->add_rate(array(
										'id' => $this->id . $UPSIKBServiceLevelID,
										'label' => $UPSIKB_ServiceName .' (Unable to estimate duties and taxes for this order ) '.$zerocostvalue,
										'cost' => number_format($UPSIKB_ServiceCost, 2),
										'meta_data' => array('UPS Service ID' => $UPSIKBServiceLevelID, 'Estimated Duty & Tax' => 'Unable to estimate duties and taxes for this order', 'Denied Party' => $UPSDeniedParty_label, 'VAT collection' => $UPSVatMessage_label, 'Applicable VAT ID for this order'=>$applicableVatOrder),
									)) ;
								} else {
									$this->add_rate(array(
										'id' => $this->id . $UPSIKBServiceLevelID,
										'label' => $UPSIKB_ServiceName .' ('. $UPSIKB_TaxAndDutyLabel .'$'. number_format($UPSIKB_TaxAndDuty, 2) .') '.$zerocostvalue,
										'cost' => number_format($UPSIKB_ServiceCost, 2),
										'meta_data' => array('UPS Service ID' => $UPSIKBServiceLevelID, 'Estimated Duty & Tax' => number_format($UPSIKB_TaxAndDuty, 2), 'Denied Party' => $UPSDeniedParty_label, 'VAT collection' => $UPSVatMessage_label, 'Applicable VAT ID for this order'=>$applicableVatOrder),
									)) ;	
								}
							} else {
								$this->add_rate(array(
									'id' => $this->id . $UPSIKBServiceLevelID,
									'label' => $UPSIKB_ServiceName.$zerocostvalue,
									'cost' => number_format($UPSIKB_ServiceCost, 2),
									'meta_data' => array('UPS Service ID' => $UPSIKBServiceLevelID, 'Estimated Duty & Tax' => number_format($UPSIKB_TaxAndDuty, 2), 'Denied Party' => $UPSDeniedParty_label, 'VAT collection' => $UPSVatMessage_label, 'Applicable VAT ID for this order'=>$applicableVatOrder),
								)) ;
							}
						}
					}
				}
			}
		}
		
		
	}
}
?>