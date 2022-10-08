<?php
$aPromotions = Phpfox::getService('user.promotion')->getPromotionsByUserGroup();
if (count($aPromotions)) {
    $url = url('user/promotion');
    $label = _p('promotions');
    echo '<li role="presentation">
       <a href="'. $url .'">
           <i class="ico ico-target-o"></i>
           ' . $label . '
       </a>
   </li>';
}