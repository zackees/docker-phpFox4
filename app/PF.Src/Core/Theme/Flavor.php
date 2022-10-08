<?php

namespace Core\Theme;

class Flavor extends \Core\Model
{
    private $_theme;

    public function __construct(\Core\Theme\Objects $Theme)
    {
        parent::__construct();

        $this->_theme = $Theme;
    }

    public function getDefault()
    {
        $flavor = $this->db->select('*')
            ->from(':theme_style')
            ->where(['theme_id' => $this->_theme->theme_id])
            ->get();

        return new Flavor\Objects($this->_theme, $flavor);
    }

    public function make($val)
    {
        $id = $this->db->insert(':theme_style', [
            'theme_id'   => $this->_theme->theme_id,
            'is_active'  => 1,
            'name'       => $val['name'],
            'folder'     => '_',
            'created'    => PHPFOX_TIME,
            'is_default' => 0,
        ]);
        $this->db->update(':theme_style', ['folder' => $id], ['style_id' => $id]);

        $path = PHPFOX_DIR_SITE . 'themes/' . $this->_theme->folder . '/flavor/';
        copy($path . $val['clone'] . '.less', $path . $id . '.less');
        copy($path . $val['clone'] . '.css', $path . $id . '.css');

        return $id;
    }
}