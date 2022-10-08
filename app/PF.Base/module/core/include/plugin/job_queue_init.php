<?php
\Core\Queue\Manager::instance()
    ->addHandler('core_email_queue', 'Core_Job_MailQueue')
    ->addHandler('core_phone_queue', 'Core_Job_SmsQueue')
    ->addHandler('core_schedule_queue', 'Core_Job_ScheduleQueue');