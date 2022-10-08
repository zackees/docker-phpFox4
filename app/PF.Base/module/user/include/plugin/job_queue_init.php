<?php
defined('PHPFOX') or exit('NO DICE!');

\Core\Queue\Manager::instance()->addHandler('user_inactive_mailing_job', 'User_Job_MailingInactive');
\Core\Queue\Manager::instance()->addHandler('user_import_user', 'User_Job_ImportUser');