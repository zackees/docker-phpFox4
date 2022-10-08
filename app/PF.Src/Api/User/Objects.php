<?php

namespace Api\User;

class Objects extends \Core\Objectify
{
    public $id;
    public $url;
    public $name;
    public $email;
    public $name_link;
    public $photo;
    public $photo_link;
    public $location;
    public $gender;
    public $dob;
    public $group;
    public $language_id;

    public function perm($perm)
    {
        return \Phpfox::getUserParam($perm);
    }

    public function isAdmin()
    {
        return \Phpfox::isAdmin();
    }

    public function get()
    {
        return $this;
    }
}