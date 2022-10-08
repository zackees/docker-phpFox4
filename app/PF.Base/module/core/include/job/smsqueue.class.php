<?php

class  Core_Job_SmsQueue extends \Core\Queue\JobAbstract
{
    public function perform()
    {
        Phpfox::getLib('mail')->cronSend($this->getParams(), true);
        $this->delete();
    }
}