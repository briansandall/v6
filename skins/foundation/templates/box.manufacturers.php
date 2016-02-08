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
{if $MANUFACTURERS}
<div class="panel" id="box-manufacturers">
  <h3>{$LANG.catalogue.title_browse_brands}</h3>
  {foreach from=$MANUFACTURERS item=manufacturer}
    <a class="clickable-image" href="{$STORE_URL}/search.html?_a=category&search[keywords]={$manufacturer.name}&search[manufacturer][0]={$manufacturer.id}" title="{$manufacturer.name}">
      <img class="clickable-image" title="View all {$manufacturer.name} products" src="{$STORE_URL}/images/logos/manufacturers/{$manufacturer.image}" alt="{$manufacturer.name}" height="30" width="120"></img>
    </a><br>
  {/foreach}
</div>
{/if}