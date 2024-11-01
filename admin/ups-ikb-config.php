<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
//check if requesting API Key
if(isset($_GET['ups-action']) && $_GET['ups-action'] === 'keyrequest') {
    $WooComVersion = UPSIKB_get_WooVerNum();
    $UPSIKBVersion = UPSIKB_get_UPSIKBVerNum();
    $UPSIKBApiUrl = UPSIKB_get_ApiUrl();

    $UPSKeyRequest_json = [
        "merchantUrl" => $_POST['UPS_IKB_KeyRequest_URL'],
        "accessLicenseNumber" => "",
        "authenticationToken" => sanitize_text_field($_POST['UPS_IKB_KeyRequest_Token']),
        "authorization" => sanitize_text_field($_POST['UPS_IKB_KeyRequest_Auth']),
        "username" => sanitize_text_field($_POST['UPS_IKB_KeyRequest_Username']),
        "password" => sanitize_text_field($_POST['UPS_IKB_KeyRequest_Password']),
        "email" => sanitize_email($_POST['UPS_IKB_KeyRequest_Email'])
    ];
    
    $UPSKeyRequest_json["hash"] = hash("sha256", json_encode($UPSKeyRequest_json));
    $UPSKeyRequest_json["shipperNumber"] = sanitize_text_field($_POST['UPS_IKB_UPSAccountNumber']);
    //$UPSKeyRequest_json["email"] = $_POST['UPS_IKB_KeyRequest_Email'];
    $UPSKeyRequest_json["meta"] = [
        "platform" => "WooCommerce",
        "platformVersion" => $WooComVersion,
        "pluginVersion" => $UPSIKBVersion,
        //"custom" => [
        //  "key" => "value"
        //]
    ];
    
    $UPSKeyRequest_json = json_encode($UPSKeyRequest_json);
    $UPS_IKB_KeyRequest = array(
        'body' => $UPSKeyRequest_json,
        'timeout' => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array('Content-Type' => 'application/json;charset=UTF-8'),
        'cookies' => array()
    );
    $UPS_IKB_KeyRequestResponse = wp_remote_post( $UPSIKBApiUrl.'install', $UPS_IKB_KeyRequest );
    $UPS_IKB_KeyRequestResponseBody = wp_remote_retrieve_body($UPS_IKB_KeyRequestResponse);
    $UPS_IKB_KeyRequestResponseData = json_decode($UPS_IKB_KeyRequestResponseBody, TRUE);
    
    if(!empty($UPS_IKB_KeyRequestResponseData) && $UPS_IKB_KeyRequestResponseData['status'] !== 200) {
        //display error message returned from API
        echo('<div class="wrap upsikb">');
        echo('<h2>Request Your UPS International Shipping API Key</h2>');
        echo('<div id="poststuff"><div id="post-body" class="metabox-holder columns-2"><div id="post-body-content">');  
        echo('<div class="postbox"><h3 class="hndle"><span>An error occured</span></h3><div class="inside"><div>');
        echo('<p>Sorry but your key request returned the following error - <b style="color:red">'. $UPS_IKB_KeyRequestResponseData['title'] .' (Error Code: '. $UPS_IKB_KeyRequestResponseData['status'] .')</b>.  Please try again.</p>');
        echo('<p>Full JSON Request: '. $UPSKeyRequest_json .'</p>');
        echo('</div></div></div>');
        echo('</div></div></div>');
        echo('</div>');
    } else {
        //update options based on submitted information
        update_option( 'UPS_IKB_AuthorizationToken', sanitize_text_field($_POST['UPS_IKB_KeyRequest_Token'] ));
        update_option( 'UPS_IKB_Authorization', sanitize_text_field($_POST['UPS_IKB_KeyRequest_Auth'] ));
        update_option( 'UPS_IKB_UPSUsername', sanitize_text_field($_POST['UPS_IKB_KeyRequest_Username'] ));
        update_option( 'UPS_IKB_UPSPassword', sanitize_text_field($_POST['UPS_IKB_KeyRequest_Password'] ));     
        update_option( 'UPS_IKB_UPSAccountNumber', sanitize_text_field($_POST['UPS_IKB_UPSAccountNumber'] ));       
        echo('<div class="wrap upsikb">');
        echo('<h2>Request Your UPS International Shipping API Key</h2>');
        echo('<div id="poststuff"><div id="post-body" class="metabox-holder columns-2"><div id="post-body-content">');
        echo('<div class="postbox"><h3 class="hndle"><span style="color:green;">Your request was successful!</span></h3><div class="inside"><div>');
        echo('<p>You will recieve an email with your key to the email address you provided.  When you get your key, come back into the plug-in and click the "Already Have Your Key?" link to enter your key into the plug-in settings.</p>');
        echo('</div></div></div>');
        echo('</div></div></div>');
        echo('</div>');
    }
} else { ?>
    <?php global $woocommerce; ?>
    <div class="wrap upsikb">
    <h2>UPS International Shipping Configuration</h2>
    <?php if( isset($_GET['settings-updated']) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e('Settings saved.') ?></strong></p>
        </div>
    <?php } ?>
    <style>
     .vatinHead label {display:inline-block;width:30%;font-weight:bold;padding:2px 5px;}
     .vatinRow label {
        display: inline-block;
        width: 30%;
        padding: 2px 5px;
     }
    .postbox .inside .storedVatin .vatinRow span {
    display: inline-block;
    padding: 0px;
    color: red;
    cursor: pointer;
    </style>
        <script>
        jQuery(document).ready(function() {
            var UPS_IKB_TaxDutyLabelVal = "<?php echo esc_attr( get_option('UPS_IKB_TaxDutyLabel') ); ?>";
            // console.log(UPS_IKB_TaxDutyLabelVal);
            jQuery('select#UPS_IKB_TaxDutyLabel').find('option[value="'+ UPS_IKB_TaxDutyLabelVal +'"]').attr('selected','selected');
            
            jQuery('input#UPS_IKB_MerchantPhone').keyup(function() {
                //console.log('phone number populating...');
                this.value = this.value.replace(/[^0-9]/, '');
            });

            jQuery('input#UPS_IKB_ShowTaxAndDuty').change(function() {
                if(this.checked) {
                            jQuery('#UPS_IKB_ShowTaxAndDuty_Disclaimer').show();
                        }
                        else {
                            jQuery('#UPS_IKB_ShowTaxAndDuty_Disclaimer').hide();
                        }
            });

            
            jQuery('form#ups_ikb_form_config').submit(function() {
                console.log('form submitted');
                if(jQuery('.postbox.pluginSettings').css('display') === 'block' && jQuery('form#ups_ikb_form_config #UPS_IKB_MerchantPhone').val() == '') {
                    alert('Need your business phone for international shipments');
                    return false;
                }
                if(jQuery('.postbox.pluginSettings').css('display') === 'block' && jQuery('form#ups_ikb_form_config #UPS_IKB_MerchantPhone').val().length < 10) {
                    var phoneCheck = jQuery('form#ups_ikb_form_config #UPS_IKB_MerchantPhone').val();
                    alert('Please input a valid business phone number that consists of at least 10 digits for international shipments');
                    return false;
                }
            });
            jQuery('#UPS_IKB_ShowVatMessage').click(function() {
                if (jQuery('#UPS_IKB_ShowVatMessage').prop('checked'))
                {
                 jQuery(".vatInBox").show();
                 jQuery("#UPS_IKB_ShowVatMessage_Text").show();
                 
                }else
                {
                    jQuery(".vatInBox").hide();
                    jQuery("#UPS_IKB_ShowVatMessage_Text").hide();
                }
            }); 
            if (jQuery('#UPS_IKB_ShowVatMessage').prop('checked'))
            {
                jQuery("#UPS_IKB_ShowVatMessage_Text").show();
            }
            
        });
        function updateVatin(action,id) {       
            //if request to add rate vatInBox  UPS_IKB_Vatin_Type UPS_IKB_Vatin_Country UPS_IKB_VatinValue
            if(action === 'add') {
                var vatinType = jQuery('.vatInBox select#UPS_IKB_Vatin_Type').val();
                var vatinCountry = jQuery('.vatInBox select#UPS_IKB_Vatin_Country').val();
                var vatinValue = jQuery('.vatInBox input#UPS_IKB_VatinValue').val();
				if(/^[a-zA-Z0-9- ]*$/.test(vatinValue) == false) {
					alert('Special characters are not allowed!');
					return;
				}
                if(vatinType === null || vatinType === '') {
                    alert("VATIN type is required!");
                    return;
                }
                if(vatinCountry === null || vatinCountry === '') {
                    alert("VATIN country/region is required!");
                    return;
                }
                if(vatinValue === null || vatinValue === '') {
                    alert("VATIN is required!");
                    return;
                }
                if(vatinType=='IOSS' &&  vatinValue.length!=12) {
                    alert("This is not a valid "+vatinType+" number!");
                    return;
                }
                if(vatinType=='HMRC' &&  vatinValue.length!=9) {
                    alert("This is not a valid "+vatinType+" number!");
                    return;
                }
                if(vatinType=='VOEC' &&  vatinValue.length!=11) {
                    alert("This is not a valid "+vatinType+" number!");
                    return;
                }
                if(vatinType=='OTHER' &&  vatinValue.length>35) {
                    alert("This is not a valid "+vatinType+" number!");
                    return;
                }
                var vatinValue_FirstTwoLetters = vatinValue.substring(0,2);
                var vatinValue_FirstFourLetters = vatinValue.substring(0,4);
                var vatinValue_ThreeToFiveLetters = vatinValue.substring(2,5);
                var vatinValue_SixToTwelveLetters = vatinValue.substring(5,12);
                var vatinValue_FiveToElevenLetters = vatinValue.substring(4,11);
                var vatinValue_ThreeToNineLetters = vatinValue.substring(2,9);
                var iossEUCode=['AUT','BEL','BGR','HRV','CYP','CZE','DNK','EST','FIN','FRA','DEU','GRC','HUN','IRL','ITA','LVA','LTU','LUX','MLT','NLD','POL','PRT','ROU','SVK','SVN','ESP','SWE'];
                if(vatinType=='IOSS' &&  (vatinValue_FirstTwoLetters!='IM' || jQuery.inArray(vatinValue_ThreeToFiveLetters, iossEUCode )==-1 || !jQuery.isNumeric(vatinValue_SixToTwelveLetters))){
                    alert("This is not a valid "+vatinType+" number!");
                    return; 
                }
                
                if(vatinType=='HMRC' && (vatinValue_FirstTwoLetters!='GB' || !jQuery.isNumeric(vatinValue_ThreeToNineLetters))){
                    alert("This is not a valid "+vatinType+" number!");
                    return; 
                }
                if(vatinType=='VOEC' &&  (vatinValue_FirstFourLetters!='VOEC'|| !jQuery.isNumeric(vatinValue_FiveToElevenLetters))){
                    alert("This is not a valid "+vatinType+" number!");
                    return; 
                }

                var VATinJSON = '<?php echo(get_option('UPS_IKB_VatIn')) ?>';
				if(VATinJSON==''){ VATinJSON = '{}'; }
                VATinARR = jQuery.parseJSON(VATinJSON);
                // console.log(VATinARR);   
                var newkey= vatinType +'_'+ vatinCountry;
                if(newkey in VATinARR){
                    console.log("key exist");   
                    alert("Delete the existing VATIN setting for this type before proceeding!");
                    return;
                }
                console.log(newkey);    
                console.log(VATinJSON);
                if(VATinJSON === '' || VATinJSON === '{}') {
                    var updatedRecord = '{"'+ vatinType +'_'+ vatinCountry +'":"'+ vatinValue +'"}';
                } else {
                    var trimmedJSON = VATinJSON.slice(1,-1);
                    console.log(trimmedJSON);
                    var updatedRecord = '{'+ trimmedJSON +',"'+ vatinType +'_'+ vatinCountry +'":"'+ vatinValue +'"}';
                }
                
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        'action' : 'UPSIKB_updateVatin',
                        'vatinJSON' : updatedRecord 
                    },
                    success: function (response) {
                        if( response === '0') { 
                            jQuery('.vatInBox .AddVATinResponse').html('<p class="success"><b>VATIN Successfully Added!</b></p>');
                            jQuery('.vatInBox .storedVatin .vatinRow').remove();
                            jQuery('.vatInBox .storedVatin .vatinHead').after('<div><b>Updating Override VATIN.  Please wait...</b></div>');
                            jQuery('div#UPSIKB-VATIN').remove();
                            location.reload(true);
                        }
                    }
                });
            }
            //if request is to delete
            if(action === 'delete') {
                var rateToDelete = id;
                var VATinJSON = '<?php echo(get_option('UPS_IKB_VatIn')) ?>';
                var vatinJSONtoArray = JSON.parse(VATinJSON);
                delete vatinJSONtoArray[id];
                var updatedRecord = JSON.stringify(vatinJSONtoArray);
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'post',
                    data: {
                        'action' : 'UPSIKB_updateVatin',
                        'vatinJSON' : updatedRecord 
                    },
                    success: function (response) {
                        if( response === '0') { 
                            jQuery('.vatInBox .AddVATinResponse').html('<p class="success"><b>VATIN Successfully Removed!</b></p>');
                            jQuery('.vatInBox .storedVatin .vatinRow').remove();
                            jQuery('.vatInBox .storedVatin .vatinHead').after('<div><b>Updating Override VATIN.  Please wait...</b></div>');
                            location.reload(true);
                        }
                    }
                });
            }
        
            return false;
        }
        
        function UPSVatinTypeSelect(sel)
        {
            var allCountryData = new Array();
                jQuery('#UPS_IKB_Vatin_Country_dummy option').each(function(){
                        allCountryData.push({val: this.value,  tex:  this.text});
                });
// console.log(allCountryData);
             var value = sel.value;  
             if(value=='HMRC')
             {
                jQuery('#UPS_IKB_Vatin_Country').children().remove().end().append('<option value="GB">United Kingdom (UK)</option>') ;
				jQuery("#UPS_IKB_VatinValue").attr('maxlength','9');
             }
             if(value=='IOSS')
             {
                 jQuery('#UPS_IKB_Vatin_Country').children().remove().end().append('<option value="EU">EU</option>');
				 jQuery("#UPS_IKB_VatinValue").attr('maxlength','12');
             }
              if(value=='VOEC')
             {
                 jQuery('#UPS_IKB_Vatin_Country').children().remove().end().append('<option value="NO">Norway</option>');
				 jQuery("#UPS_IKB_VatinValue").attr('maxlength','11');
             }
             var opt ='';
              if(value=='OTHER')
             {
                 jQuery("#UPS_IKB_Vatin_Country option[value='NO']").remove();
                 jQuery("#UPS_IKB_Vatin_Country option[value='GB']").remove();
                 jQuery("#UPS_IKB_Vatin_Country option[value='EU']").remove();
                 for(var x=0; x < allCountryData.length; x++){
                     opt +='<option value="'+allCountryData[x].val+'">'+allCountryData[x].tex+'</option>';
                    }
                    jQuery('#UPS_IKB_Vatin_Country').html(opt);
                jQuery("#UPS_IKB_Vatin_Country option[value='NO']").remove();
                jQuery("#UPS_IKB_Vatin_Country option[value='GB']").remove();
                jQuery("#UPS_IKB_Vatin_Country option[value='EU']").remove();
                jQuery("#UPS_IKB_VatinValue").attr('maxlength','35');
                
             }
        }
        
        </script>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <?php
                    //check if API key is empty - if true, show form for key request from PSP
                    if(get_option('UPS_IKB_APIKey') === '' && !isset($_GET['ups-action'])) { //show inputs for KEY request ?>
                        <p>In order to fetch UPS Services, you'll need to register for an API Key.  Submit the form below to have your API Key emailed to you.</p>
                        <form name="ups_ikb_key_request" id="ups_ikb_key_request" method="post" action="admin.php?page=ups-ikb-config.php&ups-action=keyrequest">
                            <div class="postbox">
                                <h3 class="hndle"><span>Request Your API Key</span></h3>
                                <div class="inside">
                                    <div>
                                        <?php $siteURL = explode('//', get_site_url()); ?>
                                        <label for="UPS_IKB_KeyRequest_URL">Merchant URL: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">The URL of your website without the "https://"</span></div></label><input name="UPS_IKB_KeyRequest_URL" id="UPS_IKB_KeyRequest_URL" value="<?php echo($siteURL[1]);?>" style="" type="text" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_KeyRequest_Token">Auth Token: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Optional</span></div></label><input name="UPS_IKB_KeyRequest_Token" id="UPS_IKB_KeyRequest_Token" value="" style="" type="text" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_KeyRequest_Auth">Authorization: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Optional</span></div></label><input name="UPS_IKB_KeyRequest_Auth" id="UPS_IKB_KeyRequest_Auth" value="" style="" type="text" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_KeyRequest_Username">Username: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">The username for your UPS account</span></div></label><input name="UPS_IKB_KeyRequest_Username" id="UPS_IKB_KeyRequest_Username" value="<?php echo(get_option('UPS_IKB_UPSUsername')); ?>" style="" type="text" autocomplete="off" required />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_KeyRequest_Password">Password: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">The password for your UPS account</span></div></label><input name="UPS_IKB_KeyRequest_Password" id="UPS_IKB_KeyRequest_Password" value="" style="" type="password" autocomplete="off" required />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_UPSAccountNumber">UPS Account/Shipper Number: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Your UPS Account/Shipper Number</span></div></label><input name="UPS_IKB_UPSAccountNumber" id="UPS_IKB_UPSAccountNumber" required value="<?php echo(get_option('UPS_IKB_UPSAccountNumber')); ?>" style="" type="text" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label for="UPS_IKB_KeyRequest_Email">Email Address: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Enter the email address where we can send your API key.</span></div></label><input name="UPS_IKB_KeyRequest_Email" id="UPS_IKB_KeyRequest_Email" value="" style="" type="text" autocomplete="off" required />
                                    </div>
                                    <div>
                                        <?php submit_button('Request Key', 'primary', '', false); ?>
                                        <a href="admin.php?page=ups-ikb-config.php&ups-action=enterkey" style="padding:6px 0px;display:inline-block;">Already Have Your Key?</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php 
                    } else { //show API key and other settings ?>
                    <form name="ups_ikb_form_config" id="ups_ikb_form_config" method="post" action="options.php">
                    <?php settings_fields( 'ups-ikb-config-group' ); ?>
                    <?php do_settings_sections( 'ups-ikb-config-group' ); ?>
                        <!-- API Key -->
                        <div class="postbox PSPAPIKey">
                            <h3 class="hndle"><span>API Key</span></h3>
                            <div class="inside">
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_APIKey" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">API Key: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Enter your API Key that was emailed to you.</span></div></label><input type="text" name="UPS_IKB_APIKey" id="UPS_IKB_APIKey" value="<?php echo esc_attr( get_option('UPS_IKB_APIKey') ); ?>" autocomplete="off" /> <!--<a href="#" style="display:inline-block;font-size:12px;">Request New Key</a>-->
                                </div>
                            </div>
                        </div>
                        <!-- Add Condition Check to hide all settings if API is empty -->
                        <!-- Service Levels -->
                        <div class="postbox serviceLevels" <?php if (empty(get_option('UPS_IKB_APIKey'))) { echo('style="display:none;"'); } ?>>
                            <h3 class="hndle"><span>International UPS Service Levels </span><div class="upsikbtooltip" style="float:none;"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Select the services you want to show your international shoppers.</span></div></h3>
                            <div class="inside">
                                <div style="padding:0px 0px 10px 0px;">
                                    <div id="UPSIKB-ServiceLevels">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Enabled</th>
                                                    <th>Service Name</th>
                                                    <th>Service Label</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input name="UPS_IKB_ServiceLevel_08" id="UPS_IKB_ServiceLevel_08" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_08') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
                                                    <td class="defaultServiceName" data-service-id="08">WW EXPEDITED/2DA</td>
                                                    <td><input name="UPS_IKB_ServiceLevel_08_label" id="UPS_IKB_ServiceLevel_08_label" autocomplete="off" type="text" placeholder="WW EXPEDITED/2DA" value="<?php echo esc_attr( get_option('UPS_IKB_ServiceLevel_08_label') ); ?>" /></td>
                                                </tr>
                                                <tr>
                                                    <td><input name="UPS_IKB_ServiceLevel_07" id="UPS_IKB_ServiceLevel_07" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_07') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
                                                    <td class="defaultServiceName" data-service-id="07">WW EXPRESS</td>
                                                    <td><input name="UPS_IKB_ServiceLevel_07_label" id="UPS_IKB_ServiceLevel_07_label" autocomplete="off" type="text" placeholder="WW EXPRESS" value="<?php echo esc_attr( get_option('UPS_IKB_ServiceLevel_07_label') ); ?>" /></td>
                                                </tr>
                                                <tr>
                                                    <td><input name="UPS_IKB_ServiceLevel_54" id="UPS_IKB_ServiceLevel_54" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_54') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
                                                    <td class="defaultServiceName" data-service-id="54">WW EXPRESS PLUS</td>
                                                    <td><input name="UPS_IKB_ServiceLevel_54_label" id="UPS_IKB_ServiceLevel_54_label" autocomplete="off" type="text" placeholder="WW EXPRESS PLUS" value="<?php echo esc_attr( get_option('UPS_IKB_ServiceLevel_54_label') ); ?>" /></td>
                                                </tr>
                                                <tr>
                                                    <td><input name="UPS_IKB_ServiceLevel_65" id="UPS_IKB_ServiceLevel_65" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_65') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
                                                    <td class="defaultServiceName" data-service-id="65">WW EXPRESS PM/SAVER</td>
                                                    <td><input name="UPS_IKB_ServiceLevel_65_label" id="UPS_IKB_ServiceLevel_65_label" autocomplete="off" type="text" placeholder="WW EXPRESS PM/SAVER" value="<?php echo esc_attr( get_option('UPS_IKB_ServiceLevel_65_label') ); ?>" /></td>
                                                </tr>
                                                <tr>
                                                    <td><input name="UPS_IKB_ServiceLevel_11" id="UPS_IKB_ServiceLevel_11" type="checkbox" <?php if(esc_attr( get_option('UPS_IKB_ServiceLevel_11') ) === 'on' ) { echo('checked="checked"'); } ?>></td>
                                                    <td class="defaultServiceName" data-service-id="11">WW STANDARD</td>
                                                    <td><input name="UPS_IKB_ServiceLevel_11_label" id="UPS_IKB_ServiceLevel_11_label" autocomplete="off" type="text" placeholder="WW STANDARD" value="<?php echo esc_attr( get_option('UPS_IKB_ServiceLevel_11_label') ); ?>" /></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Settings -->
                        <div class="postbox pluginSettings" <?php if (empty(get_option('UPS_IKB_APIKey'))) { echo('style="display:none;"'); } ?>>
                            <h3 class="hndle"><span>Settings</span></h3>
                            <div class="inside">
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_MerchantPhone" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Merchant Phone: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Phone Number of your business.  Needed for international shipping.</span></div></label><input type="tel" maxlength="12"  name="UPS_IKB_MerchantPhone" id="UPS_IKB_MerchantPhone" placeholder="5555555555" value="<?php echo( get_option('UPS_IKB_MerchantPhone') ); ?>" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowTaxAndDuty" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Show Estimated Tax &amp; Duty: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Show the estimated tax and duty to your shoppers.</span></div></label>
                                    <input name="UPS_IKB_ShowTaxAndDuty" id="UPS_IKB_ShowTaxAndDuty" <?php if(esc_attr( get_option('UPS_IKB_ShowTaxAndDuty') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" />
                                    <select name="UPS_IKB_TaxDutyLabel" id="UPS_IKB_TaxDutyLabel"><option value="1">Estimated Duties and Taxes</option><option value="2">Estimated Duties &amp; Taxes</option><option value="3">Estimated D&amp;T</option><option value="4">Est. Duties and Taxes</option><option value="5">Est. Duties &amp; Taxes</option><option value="6">Est. D&amp;T</option></select>
                                    <div id= "UPS_IKB_ShowTaxAndDuty_Disclaimer" <?php if (esc_attr(get_option('UPS_IKB_ShowTaxAndDuty')) !== 'on') { echo('style="display:none;"'); } ?>>The results provided here do not constitute legal advice to you, the shipper or any other person, and may only be used for your convenient reference. UPS does not guarantee the accuracy of the Services. You understand that applicable laws, rules and regulations, including those related to import and export, are subject to changes in the applicable laws and regulations, which may not be addressed by this plug-in.</div>
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowMarkUpOpts" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Enable Shipping Mark-ups: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Mark-up the shipping costs returned from the API by a percentage per country and per service level.</span></div></label><input name="UPS_IKB_ShowMarkUpOpts" id="UPS_IKB_ShowMarkUpOpts" <?php if(esc_attr( get_option('UPS_IKB_ShowMarkUpOpts') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" onClick="exposeOpts('perc');" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowMarkDownOpts" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Enable Shipping Mark-downs: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Mark-down the shipping costs returned from the API by a percentage per country and per service level.</span></div></label><input name="UPS_IKB_ShowMarkDownOpts" id="UPS_IKB_ShowMarkDownOpts" <?php if(esc_attr( get_option('UPS_IKB_ShowMarkDownOpts') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" onClick="exposeOpts('down');" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowFlatRateOpts" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Enable Flat Rates: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Use a flat-rate shipping cost per country and per service level instead of what the API returns.</span></div></label><input name="UPS_IKB_ShowFlatRateOpts" id="UPS_IKB_ShowFlatRateOpts" <?php if(esc_attr( get_option('UPS_IKB_ShowFlatRateOpts') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" onClick="exposeOpts('flat');" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_DPS" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Enable Denied Party Screenings: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Generates a warning message when a shipment is going to a restricted party.</span></div></label><input name="UPS_IKB_DPS" id="UPS_IKB_DPS" <?php if(esc_attr( get_option('UPS_IKB_DPS') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowTrackingOpts" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;">Enable Tracking Options: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Enable front-end tracking option.</span></div></label><input name="UPS_IKB_ShowTrackingOpts" id="UPS_IKB_ShowTrackingOpts" <?php if(esc_attr( get_option('UPS_IKB_ShowTrackingOpts') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" /><div style="display:inline;">Tracking overlay can be triggered by adding <b style="font-weight:900;">UPSIntlTrackingPopUp();</b> as an onclick attribute to any element OR use the shortcode <b style="font-weight:900;">[UPSIntlTrackingPopUp]</b> to display a clickable link where ever you want in your theme.</div>
                                </div>
                                <div style="padding:0px 0px 10px 0px;">
                                    <label for="UPS_IKB_ShowVatMessage" style="text-align:left;min-width:210px;margin-right:10px;display:inline-block;vertical-align:initial!important;" >Enable VAT collect Message: <div class="upsikbtooltip"><span class="dashicons dashicons-editor-help"></span><span class="upsikbtooltiptext">Generates a message when a merchant has a VATIN and collection should occur up front.</span></div></label><input name="UPS_IKB_ShowVatMessage" id="UPS_IKB_ShowVatMessage" <?php if(esc_attr( get_option('UPS_IKB_ShowVatMessage') ) === 'on' ) { echo('checked="checked"'); } ?> type="checkbox" />
                                </div>
                                <div style="padding:0px 0px 10px 0px;display:none" id="UPS_IKB_ShowVatMessage_Text">Applicable VAT number details must be entered to generate a message to your customers. IOSS, HMRC and VOEC are supported for shipments to Europe.</div>
                            </div>
                        </div>
                        <!-- Save Changes Button -->
                        <!---- VAT MEssage----->
                        <div class="postbox vatInBox" <?php if(esc_attr( get_option('UPS_IKB_ShowVatMessage') ) === 'on' ) { echo('style="display:block;"'); }else{ echo('style="display:none;"');} ?>>
                    <h3 class="hndle"><span>VATIN </span></h3>
                    <div class="inside">
                        <div style="padding:0px 0px 10px 0px;">
                            <div class="AddVATinResponse"></div>
                            <?php
                                //Fetch stored JSONobject of Vatin
                                $noneucountryopt ='<option value="">Select Country/Region</option><option value="EU">EU</option><option value="AF">Afghanistan</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua and Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas (the)</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia (Plurinational State of)</option><option value="BQ">Bonaire, Sint Eustatius and Saba</option><option value="BA">Bosnia and Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory (the)</option><option value="BN">Brunei Darussalam</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="CV">Cabo Verde</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="KY">Cayman Islands (the)</option><option value="CF">Central African Republic (the)</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands (the)</option><option value="CO">Colombia</option><option value="KM">Comoros (the)</option><option value="CD">Congo (the Democratic Republic of the)</option><option value="CG">Congo (the)</option><option value="CK">Cook Islands (the)</option><option value="CR">Costa Rica</option><option value="CU">Cuba</option><option value="CW">Curaçao</option><option value="CI">Côte d`Ivoire</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic (the)</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="SZ">Eswatini</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (the) [Malvinas]</option><option value="FO">Faroe Islands (the)</option><option value="FJ">Fiji</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories (the)</option><option value="GA">Gabon</option><option value="GM">Gambia (the)</option><option value="GE">Georgia</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GG">Guernsey</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island and McDonald Islands</option><option value="VA">Holy See (the)</option><option value="HN">Honduras</option><option value="HK">Hong Kong</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran (Islamic Republic of)</option><option value="IQ">Iraq</option><option value="IM">Isle of Man</option><option value="IL">Israel</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JE">Jersey</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KP">Korea (the Democratic People`s Republic of)</option><option value="KR">Korea (the Republic of)</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People`s Democratic Republic (the)</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="MO">Macao</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MH">Marshall Islands (the)</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia (Federated States of)</option><option value="MD">Moldova (the Republic of)</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger (the)</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NO">Norway</option><option value="NF">Norfolk Island</option><option value="MP">Northern Mariana Islands (the)</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestine, State of</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines (the)</option><option value="PN">Pitcairn</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="MK">Republic of North Macedonia</option><option value="RU">Russian Federation (the)</option><option value="RW">Rwanda</option><option value="RE">Réunion</option><option value="BL">Saint Barthélemy</option><option value="SH">Saint Helena, Ascension and Tristan da Cunha</option><option value="KN">Saint Kitts and Nevis</option><option value="LC">Saint Lucia</option><option value="MF">Saint Martin (French part)</option><option value="PM">Saint Pierre and Miquelon</option><option value="VC">Saint Vincent and the Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome and Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SX">Sint Maarten (Dutch part)</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia and the South Sandwich Islands</option><option value="SS">South Sudan</option><option value="LK">Sri Lanka</option><option value="SD">Sudan (the)</option><option value="SR">Suriname</option><option value="SJ">Svalbard and Jan Mayen</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan (Province of China)</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania, United Republic of</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad and Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks and Caicos Islands (the)</option><option value="TV">Tuvalu</option><option value="GB">United Kingdom</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates (the)</option><option value="UM">United States Minor Outlying Islands (the)</option><option value="US">United States of America (the)</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela (Bolivarian Republic of)</option><option value="VN">Viet Nam</option><option value="VG">Virgin Islands (British)</option><option value="VI">Virgin Islands (U.S.)</option><option value="WF">Wallis and Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option><option value="AX">Åland Islands</option>';
                                $noneucountry = '<select name="UPS_IKB_Vatin_Country_dummy" id="UPS_IKB_Vatin_Country_dummy" class="UpsVatInCountry" title="">'.$noneucountryopt.'</select>';
                                $UPS_IKB_VatInJSON = get_option('UPS_IKB_VatIn');                           
                                $VatInArr = json_decode($UPS_IKB_VatInJSON, true);
                                //create output ofVATIN looping through array
                                if(!empty($UPS_IKB_VatInJSON) && count($VatInArr)>0) {
                                    echo('<div class="storedVatin flat">');
                                    echo('<div class="ratesHead vatinHead"><label>VATIN Type</label><label>Country/Region</label><label>VATIN</label></div>');
                                    foreach ($VatInArr as $key => $value) {
                                        $vatLoopVatin = explode('_', $key);
                                        $vatLoopCountry = explode('_', $key);
                                        $vatLoopValue = $value;
                                        echo('<div class="vatinRow"><label>'. $vatLoopVatin[0] .'</label><label><span class="serviceLevel" style="color:#000;"></span> (<span class="serviceID" style="color:#000;">'. $vatLoopCountry[1] .'</span>)</label><label>'. $vatLoopValue .'</label><span onClick="updateVatin(\'delete\',\''. $vatLoopVatin[0] .'_'. $vatLoopCountry[1] .'\');"><span class="dashicons dashicons-no"></span></a></div>');
                                    }
                                    echo('</div>');
                                }
                            ?>
                            <div id="UPSIKB-VATIN">
                                <div class="UPSIKBlabelRow">
                                    <label>VATIN Type</label><label>Country/Region</label><label>VATIN</label>
                                </div>
                                <div class="UPSIKBrateInputRow"><select name="UPS_IKB_Vatin_Type" id="UPS_IKB_Vatin_Type" OnChange="UPSVatinTypeSelect(this)"><option value="">Select VATIN Type</option><option value="IOSS">IOSS</option><option value="HMRC">HMRC</option><option value="VOEC">VOEC</option><option value="OTHER">Other</option></select><?php echo '<select name="UPS_IKB_Vatin_Country" id="UPS_IKB_Vatin_Country">'.$noneucountryopt.'</select>'; ?><span style="display:none"><?php echo $noneucountry; ?></span><input name="UPS_IKB_VatinValue" id="UPS_IKB_VatinValue" min="0" type="text" /><label class="addRowBtn" onClick="updateVatin('add','<?php echo(hrtime(true)); ?>');"><a href="javascript:void(0)" class="button button-primary" >Save</a></label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end here--->
                    
                        <div>
                            <?php submit_button('Save Changes', 'primary', '', false); ?>
                        </div>
                    </form>
                    <?php } ?>
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
    </div>
    <script>
        // jQuery(document).ready(function() {
    
        // });
        </script>
<?php } ?>