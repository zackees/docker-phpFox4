<?php
defined('PHPFOX') or exit('NO DICE!');
?>

{if ($sPrivacy != 'rss.can_subscribe_profile') || !Phpfox::getParam('core.friends_only_community')}
    <div class="form-group">
        <label>{$aProfile.phrase}</label>
        <div class="dropdown profile-privacy-dropdown">
            <input type="hidden" id="{$sPrivacy}" name="val[privacy][{$sPrivacy}]" value="{$aProfile.default}" />
            <a data-toggle="dropdown" class="privacy_setting_active btn btn-default btn-icon btn-sm">
                <i class="fa fa-user-privacy fa-user-privacy-{$aSelectedPrivacyControl.value}"></i>
                <span class="txt-label">
                    {if (int)$aProfile.default == 0 }
                        {_p var='anyone'}
                    {elseif (int)$aProfile.default == 1}
                        {_p var='community'}
                    {elseif (int)$aProfile.default == 2}
                        {_p var='friends_only'}
                    {elseif (int)$aProfile.default == 3}
                        {_p var='friends_of_friends'}
                    {elseif (int)$aProfile.default == 4}
                        {_p var='no_one'}
                    {/if}
                </span>
                <span class="txt-label js_hover_info">
                    {if (int)$aProfile.default == 0 }
                        {_p var='anyone'}
                    {elseif (int)$aProfile.default == 1}
                        {_p var='community'}
                    {elseif (int)$aProfile.default == 2}
                        {_p var='friends_only'}
                    {elseif (int)$aProfile.default == 3}
                        {_p var='friends_of_friends'}
                    {elseif (int)$aProfile.default == 4}
                        {_p var='no_one'}
                    {/if}
                </span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-checkmark dropdown-menu-right">
                {if !isset($aProfile.anyone) && !Phpfox::getParam('core.friends_only_community')}
                    <li role="presentation">
                        <a  data-toggle="user_privacy" rel="0" class="{if 0 == (int)$aProfile.default}is_active_image{/if}">{_p var='anyone'}</a>
                    </li>
                {/if}
                {if !isset($aProfile.no_user)}
                    {if !isset($aProfile.friend_only) && (!Phpfox::getParam('core.friends_only_community') || !empty($aProfile.ignore_friend_only))}
                        <li role="presentation">
                            <a  data-toggle="user_privacy" rel="1" class="{if 1 == (int)$aProfile.default}is_active_image{/if}">{_p var='community'}</a>
                        </li>
                    {/if}

                    {if Phpfox::isModule('friend')}
                        {if (!isset($aProfile.friend) || $aProfile.friend)}
                            <li role="presentation">
                                <a  data-toggle="user_privacy" rel="2" class="{if 2 == (int)$aProfile.default}is_active_image{/if}">{_p var='friends_only'}</a>
                            </li>
                        {/if}
                        {if !empty($aProfile.friend_of_friend)}
                            <li role="presentation">
                                <a  data-toggle="user_privacy" rel="3" class="{if 3 == (int)$aProfile.default}is_active_image{/if}">{_p var='friends_of_friends'}</a>
                            </li>
                        {/if}
                    {/if}
                {/if}
                <li role="presentation">
                    <a  data-toggle="user_privacy" rel="4" class="{if 4 == (int)$aProfile.default}is_active_image{/if}">{_p var='no_one'}</a>
                </li>
            </ul>
        </div>
    </div>
{/if}