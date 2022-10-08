    <?php
defined('PHPFOX') or exit('NO DICE!');

class Feed_Component_Controller_Comments extends Phpfox_Component {

	public function process() {
	    $typeId = $this->request()->get('type', '');
        $itemId = $this->request()->get('id', '');
		$feed = Phpfox::getService('feed')->getForItem($typeId, $itemId);

		$totalComment = 0;
		if($feed) {
            $aFeed = Phpfox::getService('feed')->get(null, $feed['feed_id']);
            if(isset($aFeed[0])) {
                $aFeed = $aFeed[0];
                $totalComment = $aFeed['total_comment'];
            }
            $this->setParam('aFeed', array_merge($aFeed, ['feed_display' => 'view']));
        }
        else {
		    $aComment = db()->select('*')->from(':comment')->where(['type_id' => $typeId, 'item_id' => $itemId, 'feed_table' => 'feed'])->execute('getSlaveRow');
		    $totalComment = (int)db()->select('COUNT(*)')->from(':comment')->where(['type_id' => $typeId, 'item_id' => $itemId, 'feed_table' => 'feed'])->execute('getSlaveField');
            $this->setParam('aFeed', array_merge($aComment, ['feed_display' => 'view', 'total_comment' => $totalComment]));
        }
        
        $iPage = (int)$this->request()->get('page');
		$this->template()->assign('showOnlyComments', true);
		$this->template()->assign('nextIteration', $iPage + 1);
		Phpfox::getBlock('feed.comment');

		$out = "var comment = " . json_encode(['html' => ob_get_contents()]) . "; ";
		$out .= "$('#js_feed_comment_pager_{$typeId}{$itemId}').prepend(comment.html); \$Core.loadInit();";
		$out .= "obj.remove();";

        $iPageLimit = Phpfox::getParam('comment.comment_page_limit');
		if($totalComment < $iPage * $iPageLimit) {
            $out .= "$('#js_feed_comment_pager_{$typeId}{$itemId} .load_more_comments').remove();";
        }
		ob_clean();

		header('Content-type: application/json');
		echo json_encode(['run' => $out]);
		exit;
	}
}