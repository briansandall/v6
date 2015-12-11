<?php
/**
 * AWSP UPS Module for CubeCart v6
 * ========================================
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license GPL-3.0 http://opensource.org/licenses/GPL-3.0
 *
 * NOTE: The first div id must match the install directory
 * for the AWSP shipping module; if installing alongside the
 * standard UPS shipping module, change the id to 'Awsp_UPS'.
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 */
?>
<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
	<div id="UPS" class="tab_content">
		<h3><a href="http://www.ups.com" target="_blank">{$TITLE}</a></h3>
		<p>{$LANG.awsp_ups.module_description}</p>

		<fieldset>
			<legend>{$LANG.module.cubecart_settings}</legend>
			<div><label for="status">{$LANG.common.status}</label><span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span></div>
			<div>
				<label for="test_mode">{$LANG.awsp_ups.test_mode}</label>
				<span>
					<input type="hidden" name="module[test_mode]" id="test_mode" class="toggle" value="{$MODULE.test_mode}" />
				</span>
			</div>
			<div>
				<label for="tax">{$LANG.catalogue.tax_type}</label>
				<span>
					<select name="module[tax]" id="tax">
						{foreach from=$TAXES item=tax}<option value="{$tax.id}" {$tax.selected}>{$tax.tax_name}</option>{/foreach}
					</select>
				</span>
			</div>
			<div>
				<label for="pickup_type">{$LANG.awsp_ups.title_pickup_type}</label>
				<span>
					<select name="module[pickup_type]">
						<option value="RDP" {$SELECT_pickup_type_RDP}>{$LANG.awsp_ups.pickup_type_rdp}</option>
						<option value="OCA" {$SELECT_pickup_type_OCA}>{$LANG.awsp_ups.pickup_type_oca}</option>
						<option value="OTP" {$SELECT_pickup_type_OTP}>{$LANG.awsp_ups.pickup_type_otp}</option>
						<option value="LC" {$SELECT_pickup_type_LC}>{$LANG.awsp_ups.pickup_type_lc}</option>
						<option value="CC" {$SELECT_pickup_type_CC}>{$LANG.awsp_ups.pickup_type_cc}</option>
					</select>
				</span>
			</div>
			<div>
				<label for="rate">{$LANG.awsp_ups.title_rate}</label>
				<span>
					<select name="module[rate]">
						<option value="AR" {$SELECT_rate_AR}>{$LANG.awsp_ups.rate_account}</option>
						<option value="DR" {$SELECT_rate_DR}>{$LANG.awsp_ups.rate_daily}</option>
						<option value="RR" {$SELECT_rate_RR}>{$LANG.awsp_ups.rate_retail}</option>
						<option value="SR" {$SELECT_rate_SR}>{$LANG.awsp_ups.rate_standard}</option>
					</select>
				</span>
			</div>
			<div>
				<label for="rescom">{$LANG.awsp_ups.title_address}</label>
				<span>
					<select name="module[rescom]">
						<option value="RES" {$SELECT_rescom_RES}>{$LANG.awsp_ups.address_residential}</option>
						<option value="COM" {$SELECT_rescom_COM}>{$LANG.awsp_ups.address_commercial}</option>
					</select>
				</span>
			</div>
		</fieldset>
		
		<fieldset>
			<legend>{$LANG.awsp_ups.title_store_info}</legend>
			<div><label for="account_phone">{$LANG.awsp_ups.account_phone}</label><span><input name="module[account_phone]" id="account_phone" class="textbox" type="text" value="{$MODULE.account_phone}" /></span></div>
			<div><label for="account_address_1">{$LANG.awsp_ups.account_address_1}</label><span><input name="module[account_address_1]" id="account_address_1" class="textbox" type="text" value="{$MODULE.account_address_1}" /></span></div>
			<div><label for="account_address_2">{$LANG.awsp_ups.account_address_2}</label><span><input name="module[account_address_2]" id="account_address_2" class="textbox" type="text" value="{$MODULE.account_address_2}" /></span></div>
			<div><label for="account_city">{$LANG.awsp_ups.account_city}</label><span><input name="module[account_city]" id="account_city" class="textbox" type="text" value="{$MODULE.account_city}" /></span></div>
		</fieldset>
		
		<fieldset>
			<legend>{$LANG.awsp_ups.title_package_info}</legend>
			<div>
				<label for="container">{$LANG.awsp_ups.package_type}</label>
				<span>
					<select name="module[container]">
						<option value="CP" {$SELECT_container_CP}>{$LANG.awsp_ups.pack_custom}</option>
						<option value="ULE" {$SELECT_container_ULE}>{$LANG.awsp_ups.pack_envelope}</option>
						<option value="UT" {$SELECT_container_UT}>{$LANG.awsp_ups.pack_tube}</option>
						<option value="UEB" {$SELECT_container_UEB}>{$LANG.awsp_ups.pack_box}</option>
						<option value="UW25" {$SELECT_container_UW25}>{$LANG.awsp_ups.pack_25k}</option>
						<option value="UW10" {$SELECT_container_UW10}>{$LANG.awsp_ups.pack_10k}</option>
					</select>
				</span>
			</div>
			<div><label for="packagingWeight" title="{$LANG.awsp_ups.info_package_weight}">{$LANG.awsp_ups.package_weight}</label><span><input name="module[packagingWeight]" id="packagingWeight" class="textbox number" type="text" value="{$MODULE.packagingWeight}" /></span></div>
			<div><label for="handling" title="{$LANG.awsp_ups.info_handling_package}">{$LANG.awsp_ups.title_handling_package}</label><span><input name="module[handling]" id="handling" class="textbox number" type="text" value="{$MODULE.handling}" /></span></div>
			<div><label for="handling_rate" title="{$LANG.awsp_ups.info_handling_rate}">{$LANG.awsp_ups.title_handling_rate}</label><span><input name="module[handling_rate]" id="handling_rate" class="textbox number" type="text" value="{$MODULE.handling_rate}" placeholder="e.g. 0.02"/></span></div>
			<div><label for="defaultPackageLength">{$LANG.awsp_ups.default_package_length}</label><span><input name="module[defaultPackageLength]" id="defaultPackageLength" class="textbox number" type="text" value="{$MODULE.defaultPackageLength}" required="required" /></span></div>
			<div><label for="defaultPackageWidth">{$LANG.awsp_ups.default_package_width}</label><span><input name="module[defaultPackageWidth]" id="defaultPackageWidth" class="textbox number" type="text" value="{$MODULE.defaultPackageWidth}" required="required" /></span></div>
			<div><label for="defaultPackageHeight">{$LANG.awsp_ups.default_package_height}</label><span><input name="module[defaultPackageHeight]" id="defaultPackageHeight" class="textbox number" type="text" value="{$MODULE.defaultPackageHeight}" required="required" /></span></div>
		</fieldset>
		
		<fieldset>
			<legend>{$LANG.awsp_ups.title_account_info}</legend>
			<div><label for="accountKey">{$LANG.awsp_ups.account_key}</label><span><input name="module[accountKey]" id="accountKey" class="textbox" type="text" value="{$MODULE.accountKey}" /></span></div>
			<div><label for="accountUser">{$LANG.awsp_ups.account_user}</label><span><input name="module[accountUser]" id="accountUser" class="textbox" type="text" value="{$MODULE.accountUser}" /></span></div>
			<div><label for="accountPass">{$LANG.awsp_ups.account_pass}</label><span><input name="module[accountPass]" id="accountPass" class="textbox" type="text" value="{$MODULE.accountPass}" /></span></div>
			<div><label for="accountNumber">{$LANG.awsp_ups.account_number}</label><span><input name="module[accountNumber]" id="accountNumber" class="textbox" type="text" value="{$MODULE.accountNumber}" /></span></div>
		</fieldset>
		
		<fieldset>
			<legend>{$LANG.awsp_ups.title_products}</legend>
			<div>
				<label for="product1DM">{$LANG.awsp_ups.service_nextday_am}</label>
				<span>
					<input type="hidden" name="module[product1DM]" id="product1DM" class="toggle" value="{$MODULE.product1DM}" />
				</span>
			</div>
			<div>
				<label for="product1DA">{$LANG.awsp_ups.service_nextday_air}</label>
				<span>
					<input type="hidden" name="module[product1DA]" id="product1DA" class="toggle" value="{$MODULE.product1DA}" />
				</span>
			</div>
			<div>
				<label for="product1DP">{$LANG.awsp_ups.service_nextday_saver}</label>
				<span>
					<input type="hidden" name="module[product1DP]" id="product1DP" class="toggle" value="{$MODULE.product1DP}" />
				</span>
			</div>
			<div>
				<label for="product2DM">{$LANG.awsp_ups.service_day2_am}</label>
				<span>
					<input type="hidden" name="module[product2DM]" id="product2DM" class="toggle" value="{$MODULE.product2DM}" />
				</span>
			</div>
			<div>
				<label for="product2DA">{$LANG.awsp_ups.service_day2_air}</label>
				<span>
					<input type="hidden" name="module[product2DA]" id="product2DA" class="toggle" value="{$MODULE.product2DA}" />
				</span>
			</div>
			<div>
				<label for="product3DS">{$LANG.awsp_ups.service_day3_select}</label>
				<span>
					<input type="hidden" name="module[product3DS]" id="product3DS" class="toggle" value="{$MODULE.product3DS}" />
				</span>
			</div>
			<div>
				<label for="productGND">{$LANG.awsp_ups.service_ground}</label>
				<span>
					<input type="hidden" name="module[productGND]" id="productGND" class="toggle" value="{$MODULE.productGND}" />
				</span>
			</div>
			<div>
				<label for="productSTD">{$LANG.awsp_ups.service_canada_standard}</label>
				<span>
					<input type="hidden" name="module[productSTD]" id="productSTD" class="toggle" value="{$MODULE.productSTD}" />
				</span>
			</div>
			<div>
				<label for="productXPR">{$LANG.awsp_ups.service_worldwide_express}</label>
				<span>
					<input type="hidden" name="module[productXPR]" id="productXPR" class="toggle" value="{$MODULE.productXPR}" />
				</span>
			</div>
			<div>
				<label for="productXDM">{$LANG.awsp_ups.service_worldwide_express_plus}</label>
				<span>
					<input type="hidden" name="module[productXDM]" id="productXDM" class="toggle" value="{$MODULE.productXDM}" />
				</span>
			</div>
			<div>
				<label for="productXPD">{$LANG.awsp_ups.service_worldwide_expedited}</label>
				<span>
					<input type="hidden" name="module[productXPD]" id="productXPD" class="toggle" value="{$MODULE.productXPD}" />
				</span>
			</div>
		</fieldset>
	</div>
	{$MODULE_ZONES}
	<div class="form_control">
		<input type="submit" name="save" value="{$LANG.common.save}" />
	</div>
	<input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>
