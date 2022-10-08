<?php
defined('PHPFOX') or exit('NO DICE!');

class Admincp_Component_Controller_Stat_Dashboard extends Phpfox_Component
{
    public function process()
    {
        $aItems = [];
        $aRemainItems = [];
        $stats = Phpfox::getService('core.stat')->getSiteStatsForAdmin(0, time());

        usort($stats, function ($a, $b) {
            return ($a['total'] > $b['total']) ? -1 : 1;
        });

        $aIcons = [
            'user.status_updates' => 'paperplane-alt-o',
            'blog.blogs' => 'compose-alt',
            'groups' => 'user-man-three-o',
            'marketplace.marketplace' => 'store-o',
            'music.songs' => 'music-note-o',
            'music.music_albums' => 'music-album',
            'pages.pages' => 'flag-waving-o',
            'photo.photos' => 'photos-alt-o',
            'photo.photo_albums' => 'photos-alt-o',
            'poll.polls' => 'bar-chart2',
            'quiz.quizzes' => 'question-circle-o',
            'activity-statistics' => 'info-circle-alt-o',
            'event.events' => 'calendar-star-o',
            'forum.forum_threads' => 'comments-square',
            'forum.forum_posts' => 'comments-o',
            'videos' => 'video',
            'feed.comments_on_profiles' => 'comment-square-o',
            'comment.comment_on_items' => 'comment-square-o',
            'user.users' => 'user-man-three-o'
        ];

        $aUser = [];
        foreach ($stats as $stat) {
            $key = $stat['phrase'];
            if ($key == 'user.users') {
                $aUser = [
                    'phrase' => _p($key),
                    'value' => $stat['total'],
                    'icon' => !empty($stat['icon']) ? $stat['icon'] : (empty($aIcons[$key]) ? 'ico ico-box-o' : 'ico ico-' . $aIcons[$key])
                ];
                continue;
            }

            $aItem = [
                'phrase' => _p($key),
                'value' => $stat['total'],
                'icon' => !empty($stat['icon']) ? $stat['icon'] : (empty($aIcons[$key]) ? 'ico ico-box-o' : 'ico ico-' . $aIcons[$key])
            ];
            if(count($aItems) < 3) {
                $aItems[] = $aItem;
            }
            else {
                $aRemainItems[] = $aItem;
            }
        }

        if ($aUser) {
            array_unshift($aItems , $aUser);
        }
        elseif(count($aRemainItems)) {
            $aItems[] = $aRemainItems[0];
            unset($aRemainItems[0]);
        }

        echo $this->template()
            ->assign(array(
                'aItems' => $aItems,
                'aRemainItems' => $aRemainItems
            ))
            ->getTemplate('admincp.controller.stat.dashboard', true);
        exit;
    }
}