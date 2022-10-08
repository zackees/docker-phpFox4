<div id="admincp_stat" class="block">
    <div class="content stats-me">
        {foreach from=$aItems item=aItem}
        <div class="stat-item clearfix">
            <div class="item-outer">
                <div class="item-icon">
                    <span class="{$aItem.icon}"></span>
                </div>
                <div class="item-info">
                    <div class="item-number">{$aItem.value|number_format}</div>
                    <div class="item-text">{$aItem.phrase}</div>
                </div>
            </div>
        </div>
        {/foreach}
        {if count($aRemainItems)}
            <div class="stat-item clearfix js_admincp_stat_more">
                <div class="item-outer">
                    <div class="item-icon">
                        <span class="ico ico-angle-double-down"></span>
                    </div>
                    <div class="item-info">
                        <div class="item-text">{_p var='more'}</div>
                    </div>
                </div>
            </div>
        {/if}

        {foreach from=$aRemainItems item=aItem}
        <div class="stat-item clearfix hide">
            <div class="item-outer">
                <div class="item-icon">
                    <span class="{$aItem.icon}"></span>
                </div>
                <div class="item-info">
                    <div class="item-number">{$aItem.value|number_format}</div>
                    <div class="item-text">{$aItem.phrase}</div>
                </div>
            </div>
        </div>
        {/foreach}
        <div class="stat-item clearfix hide js_admincp_stat_less">
            <div class="item-outer">
                <div class="item-icon">
                    <span class="ico ico-angle-double-up"></span>
                </div>
                <div class="item-info">
                    <div class="item-text">{_p var='less'}</div>
                </div>
            </div>
        </div>

    </div>
</div>