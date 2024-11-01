<?php
// die when the file is called directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
//call delete option and use the vairable inside the quotations
delete_option('UPS_IKB_AuthorizationToken');
delete_option('UPS_IKB_Authorization');
delete_option('UPS_IKB_UPSUsername');
delete_option('UPS_IKB_UPSPassword');
delete_option('UPS_IKB_UPSAccountNumber');
delete_option('UPS_IKB_DPS');
delete_option('UPS_IKB_ShowFlatRateOpts');
delete_option('UPS_IKB_FlatRates');
delete_option('UPS_IKB_ShowMarkUpOpts');
delete_option('UPS_IKB_ShowMarkDownOpts');
delete_option('UPS_IKB_MarkUps');
delete_option('UPS_IKB_MarkDowns');
delete_option('UPS_IKB_ShowTrackingOpts');
delete_option('UPS_IKB_TaxDutyLabel');
delete_option('UPS_IKB_MerchantPhone');
delete_option('UPS_IKB_ShowTaxAndDuty');
delete_option('UPS_IKB_APIKey');
delete_option('UPS_IKB_ShowVatMessage');
delete_option('UPS_IKB_VatIn');
delete_option('UPS_IKB_ServiceLevel_11_label');
delete_option('UPS_IKB_ServiceLevel_11');
delete_option('UPS_IKB_ServiceLevel_65_label');
delete_option('UPS_IKB_ServiceLevel_65');
//delete_option('UPS_IKB_ServiceLevel_17_label');
//delete_option('UPS_IKB_ServiceLevel_17');
delete_option('UPS_IKB_ServiceLevel_54_label');
delete_option('UPS_IKB_ServiceLevel_54');
delete_option('UPS_IKB_ServiceLevel_07_label');
delete_option('UPS_IKB_ServiceLevel_07');
delete_option('UPS_IKB_ServiceLevel_08_label');
delete_option('UPS_IKB_ServiceLevel_08');
//delete_option('UPS_IKB_ServiceLevel_72_label');
//delete_option('UPS_IKB_ServiceLevel_72');
//delete_option('UPS_IKB_ServiceLevel_03_label');
//delete_option('UPS_IKB_ServiceLevel_03');
delete_option('UPSIKB_catalogConfig');
delete_option('UPSIKB_cat_OverrideCountryOfOrigin');
// for site options in Multisite
//delete_site_option($option_name);
?>