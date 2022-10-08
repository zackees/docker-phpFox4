<?php

namespace Core\View;

class Environment extends \Twig_Environment
{
    public function render($name, array $context = [])
    {

        $context['ActiveUser'] = (new \Api\User())->get(\Phpfox::getService('user.auth')->getUserSession());
        $context['isPager'] = isset($_GET['page']);
        $context['Is'] = new \Core\Is();

        return $this->loadTemplate($name)->render($context);
    }
}