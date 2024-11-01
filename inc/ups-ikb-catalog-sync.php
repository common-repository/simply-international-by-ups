<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//update_option('UPSIKB_CronTally', 0);
//function which executes during scheduled event/cron
/* Commenting out cron job stuff for later review
function UPSIKB_runCatalogSync($syncLoc) {
	global $woocommerce;
	$WooComVersion = UPSIKB_get_WooVerNum();
	$UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
	$UPSIKB_SKUs = array();
	global $wpdb;
	$UPSIKB_synced = 0;
	$UPSIKB_syncOffset = 0;
	$UPSIKB_syncLoadSize = 1;
	$WooCatalogSize = $wpdb->get_results(
		"
		SELECT ID, post_title, post_content, post_name
		FROM $wpdb->posts
		WHERE post_type = 'product' OR post_type = 'product_variation'
		"
	);
	echo('<p>Total Products: '. count($WooCatalogSize) .'</p>');
	
	while ($UPSIKB_synced < count($WooCatalogSize)) {
		//place bulk sync function here...
		$WooProducts = $wpdb->get_results(
		"
		SELECT ID, post_title, post_content, post_name
		FROM $wpdb->posts
		WHERE post_type = 'product' OR post_type = 'product_variation'
		
		ORDER BY ID ASC
		LIMIT ". $UPSIKB_syncOffset .",". $UPSIKB_syncLoadSize
		);
		foreach ( $WooProducts as $WooProduct ) {
			$WooProdID = $WooProduct->ID;
					$UPSIKBProdName = $WooProduct->post_title;
					if(strpos($UPSIKBProdName, 'Variation') !== false) {
						$UPSIKBProdName = explode (' ', $UPSIKBProdName, 4);
						$UPSIKBProdName = $UPSIKBProdName[3];
					}
					$UPSIKBProdDescription = strip_tags($WooProduct->post_content);
					// Get Country of Origin
					$UPSIKBCountryOfOrigin = get_post_meta( $WooProdID, 'UPSIKBCountryOfOrign', true );
					if( $UPSIKBCountryOfOrigin == '') {
						$UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
						//$UPSIKBCountryOfOrigin = $UPSIKBCountryOfOrigin[0]->name;
					}
					//get Weight unit and convert based on selected value
					$UPSIKBMerchantWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
					$UPSIKBSKUWeight = get_post_meta($WooProdID, '_weight', true);
					if($UPSIKBMerchantWeightSetting === 'oz') {
						//convert oz to lbs
						$UPSIKBSKUWeight = $UPSIKBSKUWeight / 16;
					} else if($UPSIKBMerchantWeightSetting === 'g') {
						//convert g to kg
						$UPSIKBSKUWeight = $UPSIKBSKUWeight / 1000;
					}
					$WooSKU = get_post_meta($WooProdID, '_sku', true);
					if($WooSKU == '') {
						$WooSKU = $WooProdID;
					}
					$UPSIKB_SKUs[] = array(
						'sku' => $WooSKU,
						'name' => $UPSIKBProdName,
						'description' => preg_replace("/[\n\r]/", "", $UPSIKBProdDescription),
						'weight' => array(
							'unit' => 'LBS',
							'value' => floatval($UPSIKBSKUWeight)
						),
						'price' => array(
							'unit' => 'USD',
							'value' => get_post_meta($WooProdID, '_price', true)
						),
						'countryOfOrigin' => $UPSIKBCountryOfOrigin,
						'Length' => floatval(get_post_meta($WooProdID, '_length', true)),
						'Width' => floatval(get_post_meta($WooProdID, '_width', true)),
						'Height' => floatval(get_post_meta($WooProdID, '_height', true)),
					);
			
			//build request to sent to PSP
			$UPSIKB_SKUData = array(
				'auth' => array(
					'accessLicenseNumber' => "",
					'authenticationToken' => get_option('UPS_IKB_AuthorizationToken'),
					'authorization' => get_option('UPS_IKB_Authorization'),
					'username' => get_option('UPS_IKB_UPSUsername'),
					'password' => get_option('UPS_IKB_UPSPassword'),
					"shipperNumber" => get_option('UPS_IKB_UPSAccountNumber'),
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
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
				'cookies' => array()
			);
			$UPSIKB_catalogResponse =  wp_remote_request( 'https://epc-uat.azurefd.net/parts', $UPSIKB_WPrequest );
			$UPSIKB_catalogResponseBody = wp_remote_retrieve_body($UPSIKB_catalogResponse);
			$UPSIKB_responseData = json_decode($UPSIKB_catalogResponseBody, TRUE);
			
			//var_dump(json_encode($UPSIKB_SKUData));
			//echo('<p>&nbsp;</p>');
			//var_dump($UPSIKB_catalogResponseBody);
			//echo('<p>&nbsp;</p>');

			if (empty($UPSIKB_catalogResponseBody)) {
				echo('<p>Product ');
				print_r($UPSIKB_SKUs[0]['name']);
				echo('(SKU: ');
				print_r($UPSIKB_SKUs[0]['sku']);
				echo(') has been successfully sent to UPS.</p>');
			}
			else{
				echo('<p>Product ');
				print_r($UPSIKB_SKUs[0]['name']);
				echo('(SKU: ');
				print_r($UPSIKB_SKUs[0]['sku']);
				echo(')  encountered errors when sending to UPS. ERR: ');
				print_r($UPSIKB_catalogResponseBody);
				echo('</p>');
			}
		}
		$UPSIKB_synced = $UPSIKB_synced + $UPSIKB_syncLoadSize;
		$UPSIKB_syncOffset = $UPSIKB_syncOffset + $UPSIKB_syncLoadSize;
		if( $UPSIKB_synced <= count($WooCatalogSize) ) {
			$percent = intval((int)$UPSIKB_syncOffset/count($WooCatalogSize) * 100) ."%";
			echo('<script language="javascript">document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:green;\">&nbsp;</div>"</script>');
			echo str_repeat(' ',1024*64);
			flush();
    		//ob_flush();
			if($UPSIKB_synced === count($WooCatalogSize)) {
				echo('<p><b>All Done!</b><br/><a href="'. admin_url( '/admin.php?page=ups-ikb-catalog-sync.php&sync-status=success' ) .'">Click here</a> to go back to the catalog sync settings.</p>');
			}
			
		} else if($syncLoc === 'cron') {
			//email store owner to inform them their catalog sync has completed
			wp_mail(get_bloginfo('admin_email'), 'Daily Cron Sync Complete', 'thanks');
		}
		unset($UPSIKB_SKUs);	
		$UPSIKB_SKUs = array();
	}
}
*/

if(isset($_GET['sync-status']) && $_GET['sync-status'] === 'syncing') { ?>
	<div class="wrap">
		<h2>UPS International Shipping Catalog Sync</h2>
		<div id="progress" style="width:100%;padding:10px;background-color:#FFF;margin-top:15px;border:1px solid #000;"></div>
	</div>
	<?php
		//run the catalog sync
		UPSIKB_runCatalogSync('catSettingsPg');
	} else { ?>
	<script>
		//scripts to select dropdowns with values from DB
		function manualPartsSync() {
			console.log('starting catlaog sync');
			//build catalog request
			<?php
			global $woocommerce;
			$WooComVersion = UPSIKB_get_WooVerNum();
			$UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
			$UPSIKB_SKUs = array();
			global $wpdb;
			$UPSIKB_synced = 0;
			$UPSIKB_syncOffset = 0;
			$UPSIKB_syncLoadSize = 1;
			$WooCatalogSize = $wpdb->get_results(
				"
				SELECT ID, post_title, post_content, post_name
				FROM $wpdb->posts
				WHERE post_type = 'product' OR post_type = 'product_variation'
				"
			);
			?>
			var UPSIKB_synced = <?php echo($UPSIKB_synced); ?>;
			var UPSIKB_syncOffset = <?php echo($UPSIKB_syncOffset); ?>;
			var UPSIKB_syncLoadSize = <?php echo($UPSIKB_syncLoadSize); ?>;
			jQuery('#syncProcess').show();
			jQuery('#syncProcess p').after('<p><strong>Total Number of Products:</strong> '+ <?php echo(count($WooCatalogSize)); ?> +'</p>');
			<?php
				//build catalog array to pass to AJAX
				$WooProducts = $wpdb->get_results( 
				"SELECT ID, post_title, post_content, post_name
				FROM $wpdb->posts
				WHERE post_status = 'publish' AND post_type = 'product' OR post_type = 'product_variation'
				ORDER BY ID ASC"
				);
				foreach ( $WooProducts as $WooProduct ) {
					$WooProdID = $WooProduct->ID;
					// Product Name
					$UPSIKBProdName = $WooProduct->post_title;
					if(strpos($UPSIKBProdName, 'Variation') !== false) {
						$UPSIKBProdName = explode (' ', $UPSIKBProdName, 4);
						$UPSIKBProdName = $UPSIKBProdName[3];
					}
					$UPSIKBProdDescription = str_replace(chr(34), "'", strip_tags($WooProduct->post_content));
					// Get Country of Origin
					$UPSIKBCountryOfOrigin = get_post_meta( $WooProdID, 'UPSIKBCountryOfOrign', true );
					if( $UPSIKBCountryOfOrigin == '') {
						$UPSIKBCountryOfOrigin = get_option("UPSIKB_cat_OverrideCountryOfOrigin");
						//$UPSIKBCountryOfOrigin = $UPSIKBCountryOfOrigin[0]->name;
					}
					//get Weight unit
					$UPSIKBMerchantWeightSetting = esc_attr( get_option('woocommerce_weight_unit') );
					$UPSIKBSKUWeight = get_post_meta($WooProdID, '_weight', true);

					$WooSKU = get_post_meta($WooProdID, '_sku', true);
					if($WooSKU == '') {
						$WooSKU = $UPSIKBProdName;
					}
					$UPSIKB_SKUs[] = array(
						'sku' => $WooSKU,
						'name' => $UPSIKBProdName,
						'description' => preg_replace("/[\n\r]/", "", $UPSIKBProdDescription),
						'weight' => array(
							'unit' => $UPSIKBMerchantWeightSetting,
							'value' => floatval($UPSIKBSKUWeight)
						),
						'price' => array(
							'unit' => 'USD',
							'value' => get_post_meta($WooProdID, '_price', true)
						),
						'countryOfOrigin' => $UPSIKBCountryOfOrigin,
						'Length' => floatval(get_post_meta($WooProdID, '_length', true)),
						'Width' => floatval(get_post_meta($WooProdID, '_width', true)),
						'Height' => floatval(get_post_meta($WooProdID, '_height', true)),
					);
				}
	
				$UPSIKB_SKUs_JSON = json_encode($UPSIKB_SKUs);
			?>

			jQuery.each(<?php echo($UPSIKB_SKUs_JSON); ?>, function() {				
			var JSONPSPPartRequest = '{"auth":{"accessLicenseNumber":"","authenticationToken":"<?php echo(get_option('UPS_IKB_AuthorizationToken')); ?>","authorization":"<?php echo(get_option('UPS_IKB_Authorization')); ?>","username":"<?php echo(get_option('UPS_IKB_UPSUsername')); ?>","password":"<?php echo(get_option('UPS_IKB_UPSPassword')); ?>","shipperNumber":"<?php echo(get_option('UPS_IKB_UPSAccountNumber')); ?>","key":"<?php echo(get_option('UPS_IKB_APIKey')); ?>"},"partsList":[{"sku":"'+ this['sku'] +'","name":"'+ this['name'] +'","description":"'+ this['description'] +'","weight":{"unit":"LBS","value":'+ this['weight']['value'] +'},"price":{"unit":"USD","value":"'+ this['price']['value'] +'"},"countryOfOrigin":"'+ this['countryOfOrigin'] +'", "dimensions": {"unit": "in","Length":'+ this['Length'] +',"Width":'+ this['Width'] +',"Height":'+ this['Height'] +'}}],"meta":{"platform":"WooCommerce","platformVersion":"<?php echo($WooComVersion); ?>","pluginVersion":"<?php echo($UPSIKBVersion); ?>"}}';
				
				jQuery.ajax({
					url : '/wp-json/tcb-ikb-plugin-woocommerce/v1/parts',
					type : 'POST',
					data : JSONPSPPartRequest,
					//crossDomain: true,
					contentType : 'application/json',
					success : function(xhr) {
						var UPSIKBPartsRequest = JSON.parse(JSONPSPPartRequest);
					if (xhr.length == 0) {
						jQuery('#syncProcess #syncLog').append('<p style="color:green;margin:0px;padding:0px;">Product <b>"'+ UPSIKBPartsRequest['partsList'][0]['name'] +'"</b> (SKU: '+ UPSIKBPartsRequest['partsList'][0]['sku'] +') has been successfully sent to UPS.</p>');
					}
					else{
						jQuery('#syncProcess #syncLog').append('<p style="color:red;margin:0px;padding:0px;">Product <b>"'+ UPSIKBPartsRequest['partsList'][0]['name'] +'"</b> (SKU: '+ UPSIKBPartsRequest['partsList'][0]['sku'] +') encountered errors when sending to UPS <u><b>(ERR: "'+ xhr +'")</b></u>.</p>');
					}
						//jQuery('#syncProcess #syncLog').append('<p style="color:green;margin:0px;padding:0px;">Product <b>"'+ UPSIKBPartsRequest['partsList'][0]['name'] +'"</b> (SKU: '+ UPSIKBPartsRequest['partsList'][0]['sku'] +') has been successfully sent to UPS.</p>');
					},
					error : function(jqxhr) {
						var UPSIKBPartsRequest = JSON.parse(JSONPSPPartRequest);
					if (jqxhr.responseText.length == 0) {
						jQuery('#syncProcess #syncLog').append('<p style="color:green;margin:0px;padding:0px;">Product <b>"'+ UPSIKBPartsRequest['partsList'][0]['name'] +'"</b> (SKU: '+ UPSIKBPartsRequest['partsList'][0]['sku'] +') has been successfully sent to UPS.</p>');
					}
					else{
						jQuery('#syncProcess #syncLog').append('<p style="color:red;margin:0px;padding:0px;">Product <b>"'+ UPSIKBPartsRequest['partsList'][0]['name'] +'"</b> (SKU: '+ UPSIKBPartsRequest['partsList'][0]['sku'] +') encountered errors when sending to UPS <u><b>(ERR: "'+ jqxhr.responseText +'")</b></u>.</p>');
					}
					}
				});
				
			});
		}
		
		jQuery(document).ready(function() {
			var UPSIKB_catalogConfiglVal = "<?php echo esc_attr( get_option('UPSIKB_catalogConfig') ); ?>";
			var UPSIKB_cat_CountryOfOriginVal = "<?php echo esc_attr( get_option('UPSIKB_cat_CountryOfOrigin') ); ?>";
			var UPSIKB_cat_OverrideCountryOfOrigin = "<?php echo esc_attr( get_option('UPSIKB_cat_OverrideCountryOfOrigin') ); ?>";
			jQuery('select#UPSIKB_catalogConfig').find('option[value="'+ UPSIKB_catalogConfiglVal +'"]').attr('selected','selected');
			jQuery('select#UPSIKB_cat_CountryOfOrigin').find('option[value="'+ UPSIKB_cat_CountryOfOriginVal +'"]').attr('selected','selected');
			jQuery('select#UPSIKB_cat_OverrideCountryOfOrigin').find('option[value="'+ UPSIKB_cat_OverrideCountryOfOrigin +'"]').attr('selected','selected');
		});
	</script>
	<div class="wrap">
	<h2>UPS International Shipping Catalog Sync</h2>
	<?php
	if( isset($_GET['settings-updated']) ) { ?>
		<div id="message" class="updated">
			<p><strong><?php _e('Settings saved.') ?></strong></p>
		</div>
	<?php } ?>
	<?php
	if(isset($_GET['sync-status']) && $_GET['sync-status'] === 'success' ) { ?>
		<div id="message" class="updated">
			<p><strong>Catalog has been uploaded.</strong></p>
		</div>
	<?php } ?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<form name="UPSIKB_form_catalog" id="UPSIKB_form_catalog" method="post" action="options.php">
				<?php settings_fields( 'ups-ikb-catalog-group' ); ?>
				<?php do_settings_sections( 'ups-ikb-catalog-group' ); ?>
					<!-- Catalog Config -->
					<div class="postbox">
						<h3 class="hndle"><span>Configuration</span></h3>
						<div class="inside">
							<p>You can feed us a catalog directly from your WordPress admin. Configure your feed below, save your changes and submit your catalog to UPS.</p>
							<p>We automatically pull in all of the required fields which include <strong>Product SKU</strong>, <strong>Product Name</strong>, <strong>Product Description</strong>, <strong>Product Price</strong>, <strong>Product Dimensions and Product Weight</strong>.</p>
							<p>Another required field is <strong>Country of Origin</strong>.  You can use the <strong>Default Country of Origin</strong> setting below to apply a country to <strong>ALL</strong> of your products or provide the information at a product level using the custom field called <strong>"Country of Origin"</strong>. If no Country of Origin is found on the product level, the <strong>Default Country of Origin</strong> will apply.</p>
							<hr/>
							<div style="padding:10px 0px 10px 0px;">
								<label for="UPSIKB_catalogConfig" style="text-align:left;min-width:180px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Auto Catalog Updates:</label>
								<select name="UPSIKB_catalogConfig" id="UPSIKB_catalogConfig" style="display:inline-block;"><option value="0">Disabled</option><option value="1">On product save</option></select>
								<!-- removed <option value="2">Daily Catalog Sync</option> due to time constraint -->
							</div>
						   <div style="padding:0px 0px 10px 0px;">
								<label for="UPSIKB_cat_OverrideCountryOfOrigin" style="text-align:left;min-width:180px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Default Country of Origin</label>
								<select name="UPSIKB_cat_OverrideCountryOfOrigin" id="UPSIKB_cat_OverrideCountryOfOrigin" style="display:inline-block;width:180px;margin:0px;">
<option value="">Select Country of Origin</option>
<option value= "AD">AD</option>
<option value= "AE">AE</option>
<option value= "AF">AF</option>
<option value= "AG">AG</option>
<option value= "AI">AI</option>
<option value= "AL">AL</option>
<option value= "AM">AM</option>
<option value= "AO">AO</option>
<option value= "AQ">AQ</option>
<option value= "AR">AR</option>
<option value= "AS">AS</option>
<option value= "AT">AT</option>
<option value= "AU">AU</option>
<option value= "AW">AW</option>
<option value= "AX">AX</option>
<option value= "AZ">AZ</option>
<option value= "BA">BA</option>
<option value= "BB">BB</option>
<option value= "BD">BD</option>
<option value= "BE">BE</option>
<option value= "BF">BF</option>
<option value= "BG">BG</option>
<option value= "BH">BH</option>
<option value= "BI">BI</option>
<option value= "BJ">BJ</option>
<option value= "BL">BL</option>
<option value= "BM">BM</option>
<option value= "BN">BN</option>
<option value= "BO">BO</option>
<option value= "BQ">BQ</option>
<option value= "BR">BR</option>
<option value= "BS">BS</option>
<option value= "BT">BT</option>
<option value= "BV">BV</option>
<option value= "BW">BW</option>
<option value= "BY">BY</option>
<option value= "BZ">BZ</option>
<option value= "CA">CA</option>
<option value= "CC">CC</option>
<option value= "CD">CD</option>
<option value= "CF">CF</option>
<option value= "CG">CG</option>
<option value= "CH">CH</option>
<option value= "CI">CI</option>
<option value= "CK">CK</option>
<option value= "CL">CL</option>
<option value= "CM">CM</option>
<option value= "CN">CN</option>
<option value= "CO">CO</option>
<option value= "CR">CR</option>
<option value= "CU">CU</option>
<option value= "CV">CV</option>
<option value= "CW">CW</option>
<option value= "CX">CX</option>
<option value= "CY">CY</option>
<option value= "CZ">CZ</option>
<option value= "DE">DE</option>
<option value= "DJ">DJ</option>
<option value= "DK">DK</option>
<option value= "DM">DM</option>
<option value= "DO">DO</option>
<option value= "DZ">DZ</option>
<option value= "EC">EC</option>
<option value= "EE">EE</option>
<option value= "EG">EG</option>
<option value= "EH">EH</option>
<option value= "ER">ER</option>
<option value= "ES">ES</option>
<option value= "ET">ET</option>
<option value= "FI">FI</option>
<option value= "FJ">FJ</option>
<option value= "FK">FK</option>
<option value= "FM">FM</option>
<option value= "FO">FO</option>
<option value= "FR">FR</option>
<option value= "GA">GA</option>
<option value= "GB">GB</option>
<option value= "GD">GD</option>
<option value= "GE">GE</option>
<option value= "GF">GF</option>
<option value= "GG">GG</option>
<option value= "GH">GH</option>
<option value= "GI">GI</option>
<option value= "GL">GL</option>
<option value= "GM">GM</option>
<option value= "GN">GN</option>
<option value= "GP">GP</option>
<option value= "GQ">GQ</option>
<option value= "GR">GR</option>
<option value= "GS">GS</option>
<option value= "GT">GT</option>
<option value= "GU">GU</option>
<option value= "GW">GW</option>
<option value= "GY">GY</option>
<option value= "HK">HK</option>
<option value= "HM">HM</option>
<option value= "HN">HN</option>
<option value= "HR">HR</option>
<option value= "HT">HT</option>
<option value= "HU">HU</option>
<option value= "ID">ID</option>
<option value= "IE">IE</option>
<option value= "IL">IL</option>
<option value= "IM">IM</option>
<option value= "IN">IN</option>
<option value= "IO">IO</option>
<option value= "IQ">IQ</option>
<option value= "IR">IR</option>
<option value= "IS">IS</option>
<option value= "IT">IT</option>
<option value= "JE">JE</option>
<option value= "JM">JM</option>
<option value= "JO">JO</option>
<option value= "JP">JP</option>
<option value= "KE">KE</option>
<option value= "KG">KG</option>
<option value= "KH">KH</option>
<option value= "KI">KI</option>
<option value= "KM">KM</option>
<option value= "KN">KN</option>
<option value= "KP">KP</option>
<option value= "KR">KR</option>
<option value= "KW">KW</option>
<option value= "KY">KY</option>
<option value= "KZ">KZ</option>
<option value= "LA">LA</option>
<option value= "LB">LB</option>
<option value= "LC">LC</option>
<option value= "LI">LI</option>
<option value= "LK">LK</option>
<option value= "LR">LR</option>
<option value= "LS">LS</option>
<option value= "LT">LT</option>
<option value= "LU">LU</option>
<option value= "LV">LV</option>
<option value= "LY">LY</option>
<option value= "MA">MA</option>
<option value= "MC">MC</option>
<option value= "MD">MD</option>
<option value= "ME">ME</option>
<option value= "MF">MF</option>
<option value= "MG">MG</option>
<option value= "MH">MH</option>
<option value= "MK">MK</option>
<option value= "ML">ML</option>
<option value= "MM">MM</option>
<option value= "MN">MN</option>
<option value= "MO">MO</option>
<option value= "MP">MP</option>
<option value= "MQ">MQ</option>
<option value= "MR">MR</option>
<option value= "MS">MS</option>
<option value= "MT">MT</option>
<option value= "MU">MU</option>
<option value= "MV">MV</option>
<option value= "MW">MW</option>
<option value= "MX">MX</option>
<option value= "MY">MY</option>
<option value= "MZ">MZ</option>
<option value= "NA">NA</option>
<option value= "NC">NC</option>
<option value= "NE">NE</option>
<option value= "NF">NF</option>
<option value= "NG">NG</option>
<option value= "NI">NI</option>
<option value= "NL">NL</option>
<option value= "NO">NO</option>
<option value= "NP">NP</option>
<option value= "NR">NR</option>
<option value= "NU">NU</option>
<option value= "NZ">NZ</option>
<option value= "OM">OM</option>
<option value= "PA">PA</option>
<option value= "PE">PE</option>
<option value= "PF">PF</option>
<option value= "PG">PG</option>
<option value= "PH">PH</option>
<option value= "PK">PK</option>
<option value= "PL">PL</option>
<option value= "PM">PM</option>
<option value= "PN">PN</option>
<option value= "PR">PR</option>
<option value= "PS">PS</option>
<option value= "PT">PT</option>
<option value= "PW">PW</option>
<option value= "PY">PY</option>
<option value= "QA">QA</option>
<option value= "RE">RE</option>
<option value= "RO">RO</option>
<option value= "RS">RS</option>
<option value= "RU">RU</option>
<option value= "RW">RW</option>
<option value= "SA">SA</option>
<option value= "SB">SB</option>
<option value= "SC">SC</option>
<option value= "SD">SD</option>
<option value= "SE">SE</option>
<option value= "SG">SG</option>
<option value= "SH">SH</option>
<option value= "SI">SI</option>
<option value= "SJ">SJ</option>
<option value= "SK">SK</option>
<option value= "SL">SL</option>
<option value= "SM">SM</option>
<option value= "SN">SN</option>
<option value= "SO">SO</option>
<option value= "SR">SR</option>
<option value= "SS">SS</option>
<option value= "ST">ST</option>
<option value= "SV">SV</option>
<option value= "SX">SX</option>
<option value= "SY">SY</option>
<option value= "SZ">SZ</option>
<option value= "TC">TC</option>
<option value= "TD">TD</option>
<option value= "TF">TF</option>
<option value= "TG">TG</option>
<option value= "TH">TH</option>
<option value= "TJ">TJ</option>
<option value= "TK">TK</option>
<option value= "TL">TL</option>
<option value= "TM">TM</option>
<option value= "TN">TN</option>
<option value= "TO">TO</option>
<option value= "TR">TR</option>
<option value= "TT">TT</option>
<option value= "TV">TV</option>
<option value= "TW">TW</option>
<option value= "TZ">TZ</option>
<option value= "UA">UA</option>
<option value= "UG">UG</option>
<option value= "UM">UM</option>
<option value= "US">US</option>
<option value= "UY">UY</option>
<option value= "UZ">UZ</option>
<option value= "VA">VA</option>
<option value= "VC">VC</option>
<option value= "VE">VE</option>
<option value= "VG">VG</option>
<option value= "VI">VI</option>
<option value= "VN">VN</option>
<option value= "VU">VU</option>
<option value= "WF">WF</option>
<option value= "WS">WS</option>
<option value= "YE">YE</option>
<option value= "YT">YT</option>
<option value= "ZA">ZA</option>
<option value= "ZM">ZM</option>
<option value= "ZW">ZW</option>
								</select>

								<!-- <input type="text" maxlength="2" value="<?php echo esc_attr( get_option('UPSIKB_cat_OverrideCountryOfOrigin') ); ?>" name="UPSIKB_cat_OverrideCountryOfOrigin" id="UPSIKB_cat_OverrideCountryOfOrigin" style="display:inline-block;width:100px;margin:0px;" /> -->
							</div>
						</div>
					</div>
					<!-- Save Changes Button -->
					<div>
						<?php submit_button('Save Changes', 'primary', '', false); ?>
						<div onclick="manualPartsSync();" class="button" style="display:inline-block;margin-left:10px;">Upload Catalog</div>
					</div>
					<div>
						<p>To verify that your parts upload was successful, please login into our manage parts system by clicking <a href="https://www.ups.com/lasso/login?returnto=https%3a//www.ups.com/manageparts&reasonCode=-1" target="_blank">here</a>. Please note that this process may take up to 1 hour to update or appear in our system.</p>
					</div>
					<div id="syncProcess" style="display:none;">
						<div id="syncLog" style="width:100%;height:200px;overflow-y:scroll;padding:10px;background-color:#FFF;margin-top:15px;border:1px solid #000;"></div>
					</div>
				</form>
			</div>
			<div id="postbox-container-1">
				<div class="postbox">
					<h3 class="hndle"><span>Need Help?</span></h3>
					<div class="inside">
						<p>If you have any questions about the plug-in, please refer to the UPS Help and Support Center found <a href="https://www.ups.com/us/en/help-center/contact.page" target="_blank">here</a>.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>