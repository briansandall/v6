<?php
/**
 * AWSP UPS Module for CubeCart v6
 * ========================================
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license GPL-3.0 http://opensource.org/licenses/GPL-3.0
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 * ========================================
 */
if(!defined('CC_INI_SET')) die('Access Denied');
$module		= new Module(__FILE__, $_GET['module'], 'admin/index.tpl', true);
$page_content = $module->display();