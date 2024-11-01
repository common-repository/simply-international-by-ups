<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $woocommerce;
?>
<script>
	function updateRates(action,loc,id) {		
		//if adding a flat rate to settings
		if(loc === 'flat') {
			//if request to add rate
			if(action === 'add') {
				var rateCountry = jQuery('.flatRateBox select#UPS_IKB_FlatRate_Country').val();
				var rateService = jQuery('.flatRateBox select#UPS_IKB_Flat_Rate_ServiceDropdown').val();
				var rateCost = jQuery('.flatRateBox input#UPS_IKB_FlatRateValue').val();
				if(rateCountry === null || rateCountry === '') {
					alert("Country/region is required for flat rate!");
					return;
				}
				if(rateService === null || rateService === '') {
					alert("Service level is required for flat rate!");
					return;
				}
				if(rateCost === null || rateCost === '') {
					alert("Amount is required for flat rate!");
					return;
				}
				if (parseFloat(rateCost) < 0)
				{
					alert("Flat rate cannot be negative!");
					return;
				}
				var WPFlatRateJSON = '<?php echo(get_option('UPS_IKB_FlatRates')) ?>';
				if(WPFlatRateJSON === '' || WPFlatRateJSON === '{}') {
					var updatedRecord = '{"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				} else {
					var trimmedJSON = WPFlatRateJSON.slice(1,-1);
					var updatedRecord = '{'+ trimmedJSON +',"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				}
				
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.flatRateBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Added!</b></p>');
							jQuery('.flatRateBox .storedRates .ratesRow').remove();
							jQuery('.flatRateBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							jQuery('div#UPSIKB-FlatRates').remove();
							location.reload(true);
						}
					}
				});
			}
			//if request is to delete
			if(action === 'delete') {
				var rateToDelete = id;
				var WPFlatRateJSON = '<?php echo(get_option('UPS_IKB_FlatRates')) ?>';
				var rateJSONtoArray = JSON.parse(WPFlatRateJSON);
				delete rateJSONtoArray[id];
				var updatedRecord = JSON.stringify(rateJSONtoArray);
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.flatRateBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Removed!</b></p>');
							jQuery('.flatRateBox .storedRates .ratesRow').remove();
							jQuery('.flatRateBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							location.reload(true);
						}
					}
				});
			}
		}
		if(loc === 'perc') {
			//if request to add rate
			if(action === 'add') {
				var rateCountry = jQuery('.markupBox select#UPS_IKB_MarkUp_Country').val();
				var rateService = jQuery('.markupBox select#UPS_IKB_MarkUp_ServiceDropdown').val();
				var rateCost = jQuery('.markupBox input#UPS_IKB_MarkUpValue').val();
								if(rateCountry === null || rateCountry === '') {
					alert("Country/region is required for Mark-up!");
					return;
				}
				if(rateService === null || rateService === '') {
					alert("Service level is required for Mark-up!");
					return;
				}
				if(rateCost === null || rateCost === '') {
					alert("Amount is required for Mark-up!");
					return;
				}
				if (parseFloat(rateCost) < 0)
				{
					alert("Mark-up amount cannot be negative!");
					return;
				}
				var WPDownRateJSON = '<?php echo(get_option('UPS_IKB_MarkDowns')) ?>';
				if(WPDownRateJSON === '' || WPDownRateJSON === '{}') {
				} else {
					var res = JSON.parse(WPDownRateJSON);
					var existOther = false;
					Object.keys(res).forEach(function (k) {
						if (k === rateCountry +'_'+ rateService) { existOther = true;					
						};
					})
					if (existOther)
					{
						alert("Delete the existing mark-up/mark-down setting before proceeding");
						return; 
					}
				}

				var WPPercRateJSON = '<?php echo(get_option('UPS_IKB_MarkUps')) ?>';
				if(WPPercRateJSON === '' || WPPercRateJSON === '{}') {
					var updatedRecord = '{"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				} else {
					var trimmedJSON = WPPercRateJSON.slice(1,-1);
					var updatedRecord = '{'+ trimmedJSON +',"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				}
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.markupBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Added!</b></p>');
							jQuery('.markupBox .storedRates .ratesRow').remove();
							jQuery('.markupBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							jQuery('div#UPSIKB-MarkUpRates').remove();
							location.reload(true);
						}
					}
				});
			}
			//if request is to delete
			if(action === 'delete') {
				var rateToDelete = id;
				var WPPercRateJSON = '<?php echo(get_option('UPS_IKB_MarkUps')) ?>';
				var rateJSONtoArray = JSON.parse(WPPercRateJSON);
				//remove record based on ID from function				
				delete rateJSONtoArray[id];
				//convert back to JSON
				var updatedRecord = JSON.stringify(rateJSONtoArray);
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.markupBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Removed!</b></p>');
							jQuery('.markupBox .storedRates .ratesRow').remove();
							jQuery('.markupBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							location.reload(true);
						}
					}
				});
			}
		}	
		
		if(loc === 'down') {
			//if request to add rate
			if(action === 'add') {
				var rateCountry = jQuery('.markdownBox select#UPS_IKB_MarkDown_Country').val();
				var rateService = jQuery('.markdownBox select#UPS_IKB_MarkDown_ServiceDropdown').val();
				var rateCost = jQuery('.markdownBox input#UPS_IKB_MarkDownValue').val();
				if(rateCountry === null || rateCountry === '') {
					alert("Country/region is required for Mark-down!");
					return;
				}
				if(rateService === null || rateService === '') {
					alert("Service level is required for Mark-down!");
					return;
				}
				if(rateCost === null || rateCost === '') {
					alert("Amount is required for Mark-down!");
					return;
				}
				if (parseFloat(rateCost) < 0)
				{
					alert("Mark-down amount cannot be negative!");
					return;
				}
				var WPUpRateJSON = '<?php echo(get_option('UPS_IKB_MarkUps')) ?>';
				if(WPUpRateJSON === '' || WPUpRateJSON === '{}') {
				} else {
					var res = JSON.parse(WPUpRateJSON);;
					var existOther = false;
					Object.keys(res).forEach(function (k) {
						if (k === rateCountry +'_'+ rateService) { existOther = true;					
						};
					})
					if (existOther)
					{
						alert("Delete the existing mark-up/mark-down setting before proceeding");
						return; 
					}
				}

				var WPDownRateJSON = '<?php echo(get_option('UPS_IKB_MarkDowns')) ?>';
				if(WPDownRateJSON === '' || WPDownRateJSON === '{}') {
					var updatedRecord = '{"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				} else {
					var trimmedJSON = WPDownRateJSON.slice(1,-1);
					var updatedRecord = '{'+ trimmedJSON +',"'+ rateCountry +'_'+ rateService +'":"'+ rateCost +'"}';
				}
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.markdownBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Added!</b></p>');
							jQuery('.markdownBox .storedRates .ratesRow').remove();
							jQuery('.markdownBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							jQuery('div#UPSIKB-MarkDownRates').remove();
							location.reload(true);
						}
					}
				});
			}
			//if request is to delete
			if(action === 'delete') {
				var rateToDelete = id;
				var WPDownRateJSON = '<?php echo(get_option('UPS_IKB_MarkDowns')) ?>';
				var rateJSONtoArray = JSON.parse(WPDownRateJSON);
				//remove record based on ID from function				
				delete rateJSONtoArray[id];
				//convert back to JSON
				var updatedRecord = JSON.stringify(rateJSONtoArray);
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'post',
					data: {
						'action' : 'UPSIKB_updateRate',
						'rateType' : loc,
						'rateJSON' : updatedRecord 
					},
					success: function (response) {
						if( response === '0') { 
							jQuery('.markdownBox .AddRateResponse').html('<p class="success"><b>Rate Successfully Removed!</b></p>');
							jQuery('.markdownBox .storedRates .ratesRow').remove();
							jQuery('.markdownBox .storedRates .ratesHead').after('<div><b>Updating Override Rates.  Please wait...</b></div>');
							location.reload(true);
						}
					}
				});
			}
		}	
		return false;
	}

	//populate service level dropdown
	function populateServiceLevels(e) {
		var selectedCountry = jQuery(e).attr('id');
		//console.log("selectedCountry: " + selectedCountry);
		var countryElem = jQuery("#" + selectedCountry);
		var serviceElem = jQuery("#" + selectedCountry.replace("_Country", "_ServiceDropdown").replace("_FlatRate_", "_Flat_Rate_"));

		//console.log("serviceElem: " + serviceElem);
		if( !countryElem ) {
			return;
		}
		countryId = countryElem.val();
		if( !countryId ) {
			return;
		}
		var selectedServiceElem = serviceElem.attr('id');

		if (countryId === "All")
		{
			var slvs = [];
			jQuery('table.UPSIKBServiceLevels tr:has(input[type="checkbox"]:checked)').each(function() {
			  var service_id = jQuery(this).find('td.defaultServiceName').attr('data-service-id');
			  slvs.push(service_id);
			});
			//console.log(slvs);
			populateServiceLevel(selectedServiceElem, slvs);
			return;
		}

		var countryState = "<?php echo(get_option('woocommerce_default_country')); ?>";
		var countryStateArray =  countryState.split(':');
		var merchantCountry = countryStateArray[0];
		var merchantState = countryStateArray[1];

		$UPSIKBServiceRequest = {
		auth : {
			AccessLicenseNumber : "",
			AuthenticationToken : "<?php echo(get_option('UPS_IKB_AuthorizationToken')); ?>",
			Authorization : "<?php echo(get_option('UPS_IKB_Authorization')); ?>",
			Username : "<?php echo(get_option('UPS_IKB_UPSUsername')); ?>",
			ShipperNumber : "<?php echo(get_option('UPS_IKB_UPSAccountNumber')); ?>",
			Password : "<?php echo(get_option('UPS_IKB_UPSPassword')); ?>",
			Key : "<?php echo(get_option('UPS_IKB_APIKey')); ?>"
			},
		DestinationCountry: countryId,
		merchant : {
			//"merchantName" => get_bloginfo("name"),
            shippingAddress : {
                street : {
                   first : "<?php echo(get_option('woocommerce_store_address')); ?>",
                   second : "<?php echo(get_option('woocommerce_store_address_2')); ?>",
                   third : ""
                },
                city : "<?php echo(get_option('woocommerce_store_city')); ?>",
                region : merchantState,
                regionCode : merchantState,
                postalCode : "<?php echo(get_option('woocommerce_store_postcode')); ?>",
                countryCode : merchantCountry
            },
			//"phone" => preg_replace('/\D+/', '', <?php echo(get_option('UPS_IKB_MerchantPhone')); ?>)
		},
		meta : {
            platform : "WooCommerce",
          //  "platformVersion" => $WooComVersion,
          //  "pluginVersion" => $UPSIKBVersion
			}
		}; 

		console.log(JSON.stringify($UPSIKBServiceRequest));

		jQuery.ajax({
					url: '/wp-json/tcb-ikb-plugin-woocommerce/v1/services',
					dataType : "json",
					data: JSON.stringify($UPSIKBServiceRequest),
					type : 'POST',
					crossDomain: true,
					contentType : 'application/json',
					success: function (response) {
					//	console.log(response);
		//if(jQuery('#'+ selectedServiceElem +' option').length === 1) {
			//jQuery('#' + + selectedServiceElem).remove().append('<option value="">Select a Service Level</option>');;
			populateServiceLevel(selectedServiceElem, response);
		//}
					},
					error : function(jqxhr) {
						console.log("error" );
					}
		});
						
	}

	function populateServiceLevel(selectedServiceElem, response) {
			jQuery('table.UPSIKBServiceLevels tr:has(input[type="checkbox"]:checked)').each(function() {
			  var service_id = jQuery(this).find('td.defaultServiceName').attr('data-service-id');
				var checkElem = jQuery('#'+ selectedServiceElem + ' option[value="'+ service_id +'"]');
			  if (jQuery.inArray(service_id, response) >= 0)
			  {
				//console.log("found: " + service_id);
				if (checkElem.length === 0)
				{
					jQuery('#'+ selectedServiceElem).append('<option value="'+ service_id +'">'+ jQuery(this).find('td.defaultServiceName').text() +' ('+ jQuery(this).find('td.defaultServiceName').attr('data-service-id') +')</option>');
				}
				else
				{
					//keep
				}
			  }
			  else{
				 if (checkElem.length > 0)	{checkElem.remove()};
			  }
			});
	}
	
	jQuery(document).ready(function() {
		//focus element to populate service levels of select element
		//jQuery('select#UPS_IKB_MarkUp_ServiceDropdown').focus();
		//loop through each rateRow to get ID and pull service level label from select element
		jQuery('div.storedRates .ratesRow').each(function() {
			var UPSIKBServiceLvl = jQuery(this).find('.serviceID').text();
			var service_name = jQuery("[data-service-id=" + UPSIKBServiceLvl + "]").text();
			//console.log("service_name: " + service_name);
			//var UPSIKBServiceLabel = jQuery('select#UPS_IKB_MarkUp_ServiceDropdown option[value="'+ UPSIKBServiceLvl +'"]').text().split(' (')[0];
			jQuery(this).find('.serviceLevel').text(service_name);
		});
		jQuery('#UPS_IKB_MarkUp_Country').on('change', function() { populateServiceLevels(this);});
		jQuery('#UPS_IKB_MarkDown_Country').on('change', function() { populateServiceLevels(this);});
		jQuery('#UPS_IKB_FlatRate_Country').on('change', function() { populateServiceLevels(this);});
		jQuery('#UPS_IKB_MarkUp_Country').children("option").eq(1).before('<option value="All">All Countries</option>');
		jQuery('#UPS_IKB_MarkDown_Country').children("option").eq(1).before('<option value="All">All Countries</option>');
		jQuery('#UPS_IKB_FlatRate_Country').children("option").eq(1).before('<option value="All">All Countries</option>');
	});
</script>
<!-- hidden div of active services -->
<div style="display:none!important;">
<table class="UPSIKBServiceLevels">
	<tbody>
		<tr>
			<td><input name="UPS_IKB_ServiceLevel_08" id="UPS_IKB_ServiceLevel_08" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_08') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
			<td class="defaultServiceName" data-service-id="08">WW EXPEDITED/2DA</td>
		</tr>
		<tr>
			<td><input name="UPS_IKB_ServiceLevel_07" id="UPS_IKB_ServiceLevel_07" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_07') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
			<td class="defaultServiceName" data-service-id="07">WW EXPRESS</td>
		</tr>
		<tr>
			<td><input name="UPS_IKB_ServiceLevel_54" id="UPS_IKB_ServiceLevel_54" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_54') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
			<td class="defaultServiceName" data-service-id="54">WW EXPRESS PLUS</td>
		</tr>
		<tr>
			<td><input name="UPS_IKB_ServiceLevel_65" id="UPS_IKB_ServiceLevel_65" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_65') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
			<td class="defaultServiceName" data-service-id="65">WW EXPRESS PM/SAVER</td>
		</tr>
		<tr>
			<td><input name="UPS_IKB_ServiceLevel_11" id="UPS_IKB_ServiceLevel_11" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_11') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
			<td class="defaultServiceName" data-service-id="11">WW STANDARD</td>
		</tr>
	</tbody>
</table>
</div>
<div class="wrap upsikb">
	<h2>UPS International Shipping Rate Settings</h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<?php settings_fields( 'ups-ikb-markup-group' ); ?>
				<?php do_settings_sections( 'ups-ikb-markup-group' ); ?>
				<?php
					if(esc_attr( get_option('UPS_IKB_ShowFlatRateOpts') ) !== 'on' && esc_attr( get_option('UPS_IKB_ShowMarkUpOpts') ) !== 'on' && esc_attr( get_option('UPS_IKB_ShowMarkDownOpts') ) !== 'on') {
						echo('<p>Enable <b>Enable Shipping Mark-ups/Mark-downs</b> and/or <b>Enable Flat Rates</b> to display these settings.</p>');
					}
				?>
<div>
Rules: 
<li>When "All" countries is selected, specific country options that have also been added will take priority</li>
<li>Flat rate overrides mark-up and mark-down values</li>
</div>
				<!-- Mark-up Options -->
				<div class="postbox markupBox" <?php if(esc_attr( get_option('UPS_IKB_ShowMarkUpOpts') ) === 'on' ) { echo('style="display:block;"'); } ?>>
					<h3 class="hndle"><span>Mark-up Options <div class="upsikbtooltip" style="float:none;"><span class="dashicons dashicons-editor-help"></span><span style="width:400px;bottom:25%;" class="upsikbtooltiptext">You have the ability to mark-up the UPS shipping cost based on country and shipping level</span></div></span></h3>
					<div class="inside">
						<div style="padding:0px 0px 10px 0px;">
							<div class="AddRateResponse"></div>
							<?php
								//Fetch stored JSONobject of Flat Rates
								$UPS_IKB_MarkUpJSON = get_option('UPS_IKB_MarkUps');
								//decode JSON into array for PHP to loop through
								$MarkUpRatesArr = json_decode($UPS_IKB_MarkUpJSON, true);
								//create output of Flat Rates looping through array
								if(!empty($UPS_IKB_MarkUpJSON)) {
									echo('<div class="storedRates markup">');
									echo('<div class="ratesHead"><label>Country/Region</label><label>Service Level</label><label>Mark-up Percentage</label></div>');
									foreach ($MarkUpRatesArr as $key => $value) {
										$rateLoopCountry = explode('_', $key);
										$rateLoopService = explode('_', $key);
										$rateLoopValue = $value;
										echo('<div class="ratesRow"><label>'. $rateLoopCountry[0] .'</label><label><span class="serviceLevel" style="color:#000;"></span> (<span class="serviceID" style="color:#000;">'. $rateLoopService[1] .'</span>)</label><label>'. $rateLoopValue .'</label><span onClick="updateRates(\'delete\',\'perc\',\''. $rateLoopCountry[0] .'_'. $rateLoopService[1] .'\');"><span class="dashicons dashicons-no"></span></a></div>');
									}
									echo('</div>');
								}
								//encode JSON for WP storage
								$UPS_IKB_MarkUpJSON = wp_json_encode($MarkUpRatesArr);
							?>
							<div id="UPSIKB-MarkUpRates">
								<div class="UPSIKBlabelRow">
									<label>Country/Region</label><label>Service Level</label><label>Mark-up Percentage</label>
								</div>
								<div class="UPSIKBrateInputRow"><?php woocommerce_form_field( 'UPS_IKB_MarkUp_Country', array( 'type' => 'country' ) ); ?><select name="UPS_IKB_MarkUp_ServiceDropdown" id="UPS_IKB_MarkUp_ServiceDropdown"><option value="">Select a Service Level</option></select><input name="UPS_IKB_MarkUpValue" id="UPS_IKB_MarkUpValue" min="0" type="number" /><label class="addRowBtn" onClick="updateRates('add','perc','<?php echo(hrtime(true)); ?>');"><a href="javascript:void(0)" class="button button-primary" >Save</a></label></div>
							</div>
						</div>
					</div>
				</div>
				<!-- Mark-down Options -->
				<div class="postbox markdownBox" <?php if(esc_attr( get_option('UPS_IKB_ShowMarkDownOpts') ) === 'on' ) { echo('style="display:block;"'); } ?>>
					<h3 class="hndle"><span>Mark-down Options <div class="upsikbtooltip" style="float:none;"><span class="dashicons dashicons-editor-help"></span><span style="width:400px;bottom:25%;" class="upsikbtooltiptext">You have the ability to mark-down the UPS shipping cost based on country and shipping level</span></div></span></h3>
					<div class="inside">
						<div style="padding:0px 0px 10px 0px;">
							<div class="AddRateResponse"></div>
							<?php
								//Fetch stored JSONobject of Flat Rates
								$UPS_IKB_MarkDownJSON = get_option('UPS_IKB_MarkDowns');
								//decode JSON into array for PHP to loop through
								$MarkDownRatesArr = json_decode($UPS_IKB_MarkDownJSON, true);
								//create output of Flat Rates looping through array
								if(!empty($UPS_IKB_MarkDownJSON)) {
									echo('<div class="storedRates markup">');
									echo('<div class="ratesHead"><label>Country/Region</label><label>Service Level</label><label>Mark-down Percentage</label></div>');
									foreach ($MarkDownRatesArr as $key => $value) {
										$rateLoopCountry = explode('_', $key);
										$rateLoopService = explode('_', $key);
										$rateLoopValue = $value;
										echo('<div class="ratesRow"><label>'. $rateLoopCountry[0] .'</label><label><span class="serviceLevel" style="color:#000;"></span> (<span class="serviceID" style="color:#000;">'. $rateLoopService[1] .'</span>)</label><label>'. $rateLoopValue .'</label><span onClick="updateRates(\'delete\',\'down\',\''. $rateLoopCountry[0] .'_'. $rateLoopService[1] .'\');"><span class="dashicons dashicons-no"></span></a></div>');
									}
									echo('</div>');
								}
								//encode JSON for WP storage
								$UPS_IKB_MarkDownJSON = wp_json_encode($MarkDownRatesArr);
							?>
							<div id="UPSIKB-MarkDownRates">
								<div class="UPSIKBlabelRow">
									<label>Country/Region</label><label>Service Level</label><label>Mark-down Percentage</label>
								</div>
								<div class="UPSIKBrateInputRow"><?php woocommerce_form_field( 'UPS_IKB_MarkDown_Country', array( 'type' => 'country' ) ); ?><select name="UPS_IKB_MarkDown_ServiceDropdown" id="UPS_IKB_MarkDown_ServiceDropdown"><option value="">Select a Service Level</option></select><input name="UPS_IKB_MarkDownValue" id="UPS_IKB_MarkDownValue" min="0" type="number" /><label class="addRowBtn" onClick="updateRates('add','down','<?php echo(hrtime(true)); ?>');"><a href="javascript:void(0)" class="button button-primary" >Save</a></label></div>
							</div>
						</div>
					</div>
				</div>
				<!-- Flat Rate -->
				<div class="postbox flatRateBox" <?php if(esc_attr( get_option('UPS_IKB_ShowFlatRateOpts') ) === 'on' ) { echo('style="display:block;"'); } ?>>
					<h3 class="hndle"><span>Flat Rate Options <div class="upsikbtooltip" style="float:none;"><span class="dashicons dashicons-editor-help"></span><span style="width:400px;" class="upsikbtooltiptext">You have the ability to assign a flat rate for the UPS shipping cost based on country and shipping level</span></div></span></h3>
					<div class="inside">
						<div style="padding:0px 0px 10px 0px;">
							<div class="AddRateResponse"></div>
							<?php
								//Fetch stored JSONobject of Flat Rates
								$UPS_IKB_FlatRateJSON = get_option('UPS_IKB_FlatRates');							
								$FlatRatesArr = json_decode($UPS_IKB_FlatRateJSON, true);
								//create output of Flat Rates looping through array
								if(!empty($UPS_IKB_FlatRateJSON)) {
									echo('<div class="storedRates flat">');
									echo('<div class="ratesHead"><label>Country/Region</label><label>Service Level</label><label>Flat Rate</label></div>');
									foreach ($FlatRatesArr as $key => $value) {
										$rateLoopCountry = explode('_', $key);
										$rateLoopService = explode('_', $key);
										$rateLoopValue = $value;
										echo('<div class="ratesRow"><label>'. $rateLoopCountry[0] .'</label><label><span class="serviceLevel" style="color:#000;"></span> (<span class="serviceID" style="color:#000;">'. $rateLoopService[1] .'</span>)</label><label>'. $rateLoopValue .'</label><span onClick="updateRates(\'delete\',\'flat\',\''. $rateLoopCountry[0] .'_'. $rateLoopService[1] .'\');"><span class="dashicons dashicons-no"></span></a></div>');
									}
									echo('</div>');
								}
							?>
							<div id="UPSIKB-FlatRates">
								<div class="UPSIKBlabelRow">
									<label>Country/Region</label><label>Service Level</label><label>Flat Rate</label>
								</div>
								<div class="UPSIKBrateInputRow"><?php woocommerce_form_field( 'UPS_IKB_FlatRate_Country', array( 'type' => 'country' ) ); ?><select name="UPS_IKB_Flat_Rate_ServiceDropdown" id="UPS_IKB_Flat_Rate_ServiceDropdown"><option value="">Select a Service Level</option></select><input name="UPS_IKB_FlatRateValue" id="UPS_IKB_FlatRateValue" min="0" type="number" /><label class="addRowBtn" onClick="updateRates('add','flat','<?php echo(hrtime(true)); ?>');"><a href="javascript:void(0)" class="button button-primary" >Save</a></label></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>        
	</div>
</div>			