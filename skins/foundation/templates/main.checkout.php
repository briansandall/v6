{*
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2015. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@cubecart.com
 * License:  GPL-3.0 https://www.gnu.org/licenses/quick-guide-gplv3.html
 *}
<!DOCTYPE html>
<html class="no-js" xmlns="http://www.w3.org/1999/xhtml" dir="{$TEXT_DIRECTION}" lang="{$HTML_LANG}">
   <head>
      <title>{$META_TITLE}</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="{$CANONICAL}" rel="canonical">
      <link href="{$STORE_URL}/favicon.ico" rel="shortcut icon" type="image/x-icon">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/normalize.css" rel="stylesheet">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/foundation.css" rel="stylesheet">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/font-awesome/css/font-awesome.min.css" rel="stylesheet">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/cubecart.css" rel="stylesheet">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/cubecart.common.css" rel="stylesheet">
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/cubecart.helpers.css" rel="stylesheet">
      {if !empty($SKIN_SUBSET)}
      <link href="{$STORE_URL}/skins/{$SKIN_FOLDER}/css/cubecart.{$SKIN_SUBSET}.css" rel="stylesheet">
      {/if}
      <link href="//fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
      {foreach from=$CSS key=css_keys item=css_files}
      <link rel="stylesheet" type="text/css" href="{$STORE_URL}/{$css_files}" media="screen">
      {/foreach}
      <meta http-equiv="Content-Type" content="text/html;charset={$CHARACTER_SET}">
      <meta name="description" content="{if isset($META_DESCRIPTION)}{$META_DESCRIPTION}{/if}">
      <meta name="keywords" content="{if isset($META_KEYWORDS)}{$META_KEYWORDS}{/if}">
      <meta name="robots" content="index, follow">
      <meta name="generator" content="cubecart">
      {if $FBOG}
      <meta property="og:image" content="{$PRODUCT.thumbnail}">
      <meta property="og:url" content="{$VAL_SELF}">
      {/if}
      {include file='templates/content.recaptcha.head.php'}
      <script src="{$STORE_URL}/skins/{$SKIN_FOLDER}/js/vendor/modernizr.min.js"></script>
      <script src="{$STORE_URL}/skins/{$SKIN_FOLDER}/js/vendor/jquery.js"></script>
      {include file='templates/element.google_analytics.php'}
      {foreach from=$HEAD_JS item=js}{$js}{/foreach}
   </head>
   <body>
      <div class="off-canvas-wrap" data-offcanvas>
         <div class="inner-wrap">
            {include file='templates/box.off_canvas.left.php'}
            {include file='templates/box.eu_cookie.php'}
            <div class="right text-center show-for-small"><a class="left-off-canvas-toggle button white tiny" href="#"><i class="fa fa-bars fa-2x"></i></a></div>
            <div class="marg-top" id="nav-actions">
               <div class="right text-right show-for-medium-up">{include file='templates/box.session2.php'}</div>
               <div class="small-8 large-9 columns">
                  <div class="show-for-medium-up">
                     <div class="leftcontact2">
                        <div class="left callus">
                           <i class="fa fa-phone"></i><a href="tel:+13606269028" class="top-link">(360)626-9028</a>
                        </div>
                        <div class="left faxus">
                           <i class="fa fa-fax"></i><a href="tel:+12066942723" class="top-link">(206)694-2723</a>
                        </div>
                        <div class="left emailus">
                           <i class="fa fa-envelope"></i><a href="{$STORE_URL}/contact-us.html" class="top-link">info@linemansequipment.com</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="row {$SECTION_NAME}_wrapper">
               <div class="img_container">
                  <a href="{$STORE_URL}" class="main-logo" title="Continue shopping"><img src="{$STORE_URL}/images/logos/logo2.png" alt="{$META_TITLE}"></a>
               </div>
               <div class="small-12 large-12 columns small-collapse">
               	{include file='templates/box.progress.php'}
               </div>
               <div class="small-12 large-12 columns">
                  {include file='templates/box.errors.php'}
                  {$PAGE_CONTENT}
               </div>
            </div>
            <footer>
               <div class="row">
                  <div class="footer medium-7 large-7 columns">
                     {include file='templates/box.documents.php'}
                     <div class="show-for-medium-up">{$COPYRIGHT}<p>eCommerce by <a href="http://www.cubecart.com">CubeCart</a></p></div>
                  </div>
                  <div class="footer medium-5 large-5 columns">
                     {$SOCIAL_LIST}
                     {include file='templates/ccpower.php'}
                     <div class="show-for-small-only">{$COPYRIGHT}</div>
                  </div>
               </div>
            </footer>
            <script src="{$STORE_URL}/skins/{$SKIN_FOLDER}/js/vendor/jquery.rating.min.js" type="text/javascript"></script>
            <script src="{$STORE_URL}/skins/{$SKIN_FOLDER}/js/vendor/jquery.validate.min.js" type="text/javascript"></script>
            <script src="{$STORE_URL}/skins/{$SKIN_FOLDER}/js/vendor/jquery.cookie.min.js" type="text/javascript"></script>
            {foreach from=$BODY_JS item=js}{$js}{/foreach}
            {foreach from=$JS_SCRIPTS key=k item=script}
            <script src="{$STORE_URL}/{$script|replace:'\\':'/'}" type="text/javascript"></script>
            {/foreach}
            <script>
               $(document).foundation();
            </script>
            {$LIVE_HELP}
            {$DEBUG_INFO}
            {$SKIN_SELECT}
            <a class="exit-off-canvas"></a>
         </div>
        <div class="borderbot"></div>
      </div>
   </body>
</html>