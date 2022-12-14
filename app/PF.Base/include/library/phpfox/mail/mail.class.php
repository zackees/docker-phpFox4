<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * Email Driver Layer
 * Our email loads a 3rd party email class that usually has support for both sendmail and smtp.
 *
 * Example:
 * <code>
 * Phpfox::getLib('mail')->to('foo@bar.com')
 *        ->subject('Test Subject')
 *        ->message('Test Message')
 *        ->send();
 * </code>
 *
 * @copyright        [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package        Phpfox
 * @version        $Id: mail.class.php 7079 2014-01-29 17:27:22Z Fern $
 */
class Phpfox_Mail
{
    /**
     * Object of the 3rd party library we are using to send the actual image.
     *
     * @var object
     */
    private $_oMail;

    /**
     * STRING or ARRAY of emails or users to send the email to.
     *
     * @var mixed
     */
    private $_mTo = null;

    /**
     * ARRAY if loading a phrase or STRING if we are passing a subject.
     *
     * @var mixed
     */
    private $_aSubject = null;

    /**
     * The name of the person sending the email.
     *
     * @var string
     */
    private $_sFromName = null;

    /**
     * The email of the person sending the email.
     *
     * @var string
     */
    private $_sFromEmail = null;

    /**
     * Notification ID to be used to check if a user has privacy settings on receiving an email.
     *
     * @var string
     */
    private $_sNotification = null;

    /**
     * ARRAY of users to email and their information.
     *
     * @var array
     */
    private $_aUsers = null;

    /**
     * ARRAY of loading a phrase or STRING of we are passing the message directly.
     *
     * @var mixed
     */
    private $_aMessagePlain = null;

    /**
     * TRUE to send the message to ourself and FALSE to not.
     *
     * @var bool
     */
    private $_bSendToSelf = false;

    /**
     * Controls if we should include the default header in the message. Default is TRUE.
     *
     * @var bool
     */
    private $_bMessageHeader = true;

    /**
     * ARRAY of loading a phrase or STRING of we are passing the message directly.
     *
     * @var mixed
     */
    private $_aMessage = null;

    /**
     * Used for global replacements like site_name and site_email
     * @var String
     */
    private $_sArray;

    /**
     * Used for set flag to translation
     * @var boolean
     */
    private $_bTranslated = false;


    /**
     * Used for skip send sms to users
     * @var bool
     */
    private $_bSkipSms = false;
    /**
     * Class constructor that loads a specific method of sending emails (sendmail or smtp)
     *
     * @param string $sMethod Method to send an email (sendmail or smtp)
     */
    public function __construct($sMethod = null)
    {
        $this->_oMail = Phpfox::getLib('mail.driver.phpmailer.' . ($sMethod === null ? Phpfox::getParam('core.method') : $sMethod));
        $this->_sArray = 'array("site_name" => "' . str_replace('"', '&quot;', Phpfox::getParam('core.site_title')) . '","site_email" => "' . Phpfox::getParam('core.email_from_email') . '")';
    }

    /**
     * Assign default value for all variables
     *
     * @return $this
     */
    public function reset()
    {
        $this->_mTo = null;
        $this->_aSubject = null;
        $this->_sFromName = null;
        $this->_sFromEmail = null;
        $this->_sNotification = null;
        $this->_aUsers = null;
        $this->_aMessagePlain = null;
        $this->_bSendToSelf = false;
        $this->_bMessageHeader = true;
        $this->_aMessage = null;
        $this->_bTranslated = false;
        $this->_bSkipSms = false;
        return $this;
    }

    /**
     * @return Phpfox_Mail
     */
    public static function instance()
    {
        return Phpfox::getLib('mail');
    }

    /**
     * Run a test if we are able to send out an email using the default method being loaded.
     *
     * @param array $aVals ARRAY of values to test.
     * @return Phpfox_Mail
     */
    public function test($aVals)
    {
        $this->_oMail->test($aVals);

        return $this;
    }

    /**
     * Identify who this email will be sent to. Can be an actual email or a user ID or an array of
     * emails or user IDs.
     *
     * @param mixed $mTo ARRAY of emails/users or STRING of email/user
     * @return Phpfox_Mail
     */
    public function to($mTo)
    {
        $this->_mTo = $mTo;

        return $this;
    }

    /**
     * Subject of the email.
     *
     * @param mixed $aSubject ARRAY if loading a phrase or STRING if we are passing a subject.
     * @return Phpfox_Mail
     */
    public function subject($aSubject)
    {
        $this->_aSubject = $aSubject;

        return $this;
    }

    /**
     * The name of the person sending out the email.
     *
     * @param string $sFromName Persons name.
     * @return Phpfox_Mail
     */
    public function fromName($sFromName)
    {
        $this->_sFromName = $sFromName;

        return $this;
    }

    /**
     * Send a copy to our own email.
     *
     * @param bool $bSendToSelf TRUE will send a copy and FALSE will not.
     * @return Phpfox_Mail
     */
    public function sendToSelf($bSendToSelf)
    {
        $this->_bSendToSelf = $bSendToSelf;

        return $this;
    }

    /**
     * Email of the person sending out this email.
     *
     * @param string $sFromEmail Email.
     * @return Phpfox_Mail
     */
    public function fromEmail($sFromEmail)
    {
        $this->_sFromEmail = $sFromEmail;

        return $this;
    }

    /**
     * Notification param for this specific email to check a users privacy settings.
     *
     * @param string $sNotification Param of the notification.
     * @return Phpfox_Mail
     */
    public function notification($sNotification)
    {
        $this->_sNotification = $sNotification;

        return $this;
    }

    /**
     * Message of the email.
     *
     * @param mixed $aMessage ARRAY of loading a phrase or STRING of we are passing the message directly.
     * @return Phpfox_Mail
     */
    public function message($aMessage)
    {
        if (is_array($aMessage)) {
            if (!isset($aMessage[1]['site_name'])) {
                $aMessage[1]['site_name'] = Phpfox::getParam('core.site_title');
            }
            if (!isset($aMessage[1]['site_url'])) {
                $aMessage[1]['site_url'] = Phpfox_Url::instance()->getDomain();
            }
        }
        $this->_aMessage = $aMessage;

        return $this;
    }

    /**
     * Identify if we should load the message header we include by default.
     *
     * @param bool $bMessageHeader Controlls if we should include the default header in the message. Default is TRUE.
     * @return Phpfox_Mail
     */
    public function messageHeader($bMessageHeader)
    {
        $this->_bMessageHeader = $bMessageHeader;

        return $this;
    }

    /**
     * Message of the email (Plain Text).
     *
     * @param mixed $aMessage ARRAY of loading a phrase or STRING of we are passing the message directly.
     * @return Phpfox_Mail
     */
    public function messagePlain($aMessage)
    {
        $this->_aMessagePlain = $aMessage;

        return $this;
    }

    /**
     * We load users information in our send() method, however you can also load users by passing
     * an array of their information with this method.
     *
     * @param array $aUser ARRAY of users information.
     * @return Phpfox_Mail
     */
    public function aUser($aUser)
    {
        $this->_aUsers[] = ($aUser);

        return $this;
    }

    /**
     * Email address validator based on http://www.linuxjournal.com/article/9585 and RFC 2821
     * Uses recursion for arrays
     *
     * @param mixed $mEmail array|string
     * @return boolean true if all valid
     */
    public function checkEmail($mEmail)
    {
        if (is_array($mEmail)) {
            foreach ($mEmail as $sEmail) {
                if (!$this->_checkEmail($sEmail)) {
                    // return here before keep going
                    return false;
                }
            }
            return true;
        }
        return $this->_checkEmail($mEmail);
    }

    /**
     * Use for set message and object have been translated
     * @param boolean $bTranslated
     * @return object
     */
    public function translated($bTranslated = true)
    {
        $this->_bTranslated = $bTranslated;
        return $this;
    }

    /**
     * @param false $bSkip
     * @return $this
     */
    public function skipSms($bSkip = false)
    {
        $this->_bSkipSms = $bSkip;
        return $this;
    }

    /**
     * Method to send out the email.
     * Checks:
     *        (message || to) === null -> return false;
     *        (sFromName || sFromEmail) === null -> getParam(core.
     *        (Notification) assumes to is an array of integers, otherwise return false
     *
     * @example Phpfox::getLib('mail')->to('user@email.com')->subject('Test Subject')->message('This is a test message')->send();
     * @example Phpfox::getLib('mail')->to(array('user1@email.com', 'user2@email.com', 'user3@email.com')->subject('Test Subject')->message('This is a test message')->send()
     *
     * @param bool $bDoCheck
     * @param bool $bSendImmediately
     *
     * @return boolean
     */
    public function send($bDoCheck = false, $bSendImmediately = false)
    {
        if (defined('PHPFOX_SKIP_MAIL')) {
            return true;
        }

        // turn into an array
        if (!is_array($this->_mTo)) {
            $this->_mTo = [$this->_mTo];
        }

        // check if the mail(s) are valid
        if ($bDoCheck && $this->checkEmail($this->_mTo) == false) {
            return false;
        }

        if ($this->_aMessage === null || $this->_mTo === null) {
            return false;
        }

        if ($this->_sFromName === null) {
            $this->_sFromName = Phpfox::getParam('core.mail_from_name');
        }

        if ($this->_sFromEmail === null) {
            $this->_sFromEmail = Phpfox::getParam('core.email_from_email');
        }

        $this->_sFromName = html_entity_decode($this->_sFromName, null, 'UTF-8');

        $sIds = '';
        $sEmails = '';
        //in some case variable $aUser is not defined
        $aUser = null;
        if (!empty($this->_aUsers)) {
            foreach ($this->_aUsers as $aUser) {
                if (isset($aUser['user_id']) && !empty($aUser['user_id'])) {
                    $sIds .= (int)$aUser['user_id'] . ',';
                }
            }
        } else {
            foreach ($this->_mTo as $mTo) {
                if (strpos($mTo, '@')) {
                    $sEmails .= $mTo . ',';
                } else {
                    $sIds .= (int)$mTo . ',';
                }
            }
        }
        $sIds = rtrim($sIds, ',');
        $sEmails = rtrim($sEmails, ',');
        $bIsSent = true;
        if (!empty($sIds)) {
            (($sPlugin = Phpfox_Plugin::get('mail_send_query')) ? eval($sPlugin) : false);

            if ($this->_aUsers === null) {
                if ($this->_sNotification !== null) {
                    Phpfox_Database::instance()->select('un.user_notification, ')->leftJoin(Phpfox::getT('user_notification'), 'un', "un.notification_type = 'email' AND un.user_id = u.user_id AND (un.user_notification = '" . Phpfox_Database::instance()->escape($this->_sNotification) . "' OR un.user_notification = 'core.enable_notifications')");
                }
                $aUsers = Phpfox_Database::instance()->select('u.user_id, u.email, u.language_id, u.full_name, u.user_group_id, u.full_phone_number')
                    ->from(Phpfox::getT('user'), 'u')
                    ->where('u.user_id IN(' . $sIds . ')')
                    ->execute('getSlaveRows');
            } else {
                $aUsers = $this->_aUsers;
            }
            if (!empty($aUsers) && count($aUsers) > 0) {
                foreach ($aUsers as $aUser) {
                    // User is banned, lets not send them any emails
                    if (isset($aUser['user_group_id']) && Phpfox::getService('user.group.setting')->getGroupParam($aUser['user_group_id'], 'core.user_is_banned')) {
                        continue;
                    }
                    // Lets not send out an email to myself
                    if ($this->_bSendToSelf === false && $aUser['user_id'] == Phpfox::getUserId()) {
                        continue;
                    }

                    $bCanSend = true;
                    if ($this->_sNotification !== null && isset($aUser['user_notification']) && $aUser['user_notification']) {
                        $bCanSend = false;
                    }
                    if ($bCanSend === true) {
                        // load the messages in their language
                        $aUser['language_id'] = ($aUser['language_id'] == null || empty($aUser['language_id'])) ? Phpfox::getParam('core.default_lang_id') : $aUser['language_id'];

                        if (is_array($this->_aMessage)) {
                            $sMessage = _p($this->_aMessage[0], isset($this->_aMessage[1]) ? array_merge($aUser, $this->_aMessage[1]) : $aUser, $aUser['language_id']);
                        } else {
                            if ($this->_bTranslated) {
                                $sMessage = $this->_aMessage;
                            } elseif (is_string($this->_aMessage) && Core\Lib::phrase()->isPhrase($this->_aMessage)) {
                                $sMessage = _p($this->_aMessage, [], $aUser['language_id']);
                            } else {
                                $sMessage = $this->_aMessage;
                            }

                        }

                        if (is_array($this->_aMessagePlain)) {
                            $sMessagePlain = _p($this->_aMessagePlain[0], isset($this->_aMessagePlain[1]) ? array_merge($aUser, $this->_aMessagePlain[1]) : $aUser, $aUser['language_id']);
                        } else {
                            $sMessagePlain = _p($this->_aMessagePlain, [], $aUser['language_id']);
                        }

                        if (is_array($this->_aSubject)) {
                            $sSubject = _p($this->_aSubject[0], isset($this->_aSubject[1]) ? array_merge($aUser, $this->_aSubject[1]) : $aUser, $aUser['language_id']);
                        } else {
                            if ($this->_bTranslated || !Core\Lib::phrase()->isPhrase($this->_aSubject)) {
                                $sSubject = $this->_aSubject;
                            } else {
                                $sSubject = _p($this->_aSubject, [], $aUser['language_id']);
                            }
                        }

                        $sMessage = $this->_getTranslatePhrase($sMessage, $aUser['language_id']);
                        $sMessagePlain = $this->_getTranslatePhrase($sMessagePlain, $aUser['language_id']);
                        $sSubject = $this->_getTranslatePhrase($sSubject, $aUser['language_id']);
                        $sSubject = html_entity_decode($sSubject, null, 'UTF-8');
                        $sSubject = str_replace(['&#039;', '&#0039;'], "'", $sSubject);
                        $sEmailSig = $this->_getSignature($aUser);
                        $sMessageHello = _p('hello_name_comma', ['name' => $aUser['full_name']], $aUser['language_id']);

                        // Load plain text template
                        $sTextPlain = Phpfox_Template::instance()->assign([
                            'sName' => $aUser['full_name'],
                            'bHtml' => false,
                            'sMessage' => $this->_aMessagePlain !== null ? $sMessagePlain : $sMessage,
                            'sEmailSig' => $sEmailSig,
                            'bMessageHeader' => $this->_bMessageHeader,
                            'sMessageHello' => $sMessageHello
                        ])->getLayout('email', true);
                        $sTextPlain = strip_tags($sTextPlain);

                        // Load HTML text template
                        $sTextHtml = Phpfox_Template::instance()->assign([
                                'sName' => $aUser['full_name'],
                                'bHtml' => true,
                                'sMessage' => str_replace("\n", "<br />", $sMessage),
                                'sEmailSig' => str_replace("\n", "<br />", $sEmailSig),
                                'bMessageHeader' => $this->_bMessageHeader,
                                'sMessageHello' => $sMessageHello
                            ]
                        )->getLayout('email', true);

                        if (defined('PHPFOX_DEFAULT_OUT_EMAIL')) {
                            $aUser['email'] = PHPFOX_DEFAULT_OUT_EMAIL;
                        }
                        (($sPlugin = Phpfox_Plugin::get('mail_send_call')) ? eval($sPlugin) : false);
                        if (empty($aUser['email'])) {
                            continue;
                        }

                        if (!isset($bSkipMailSend)) {
                            if (defined('PHPFOX_CACHE_MAIL')) {
                                $bIsSent = $this->_cache($aUser['email'], $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                            } else {
                                $bSendImmediately = $bSendImmediately || (defined('PHPFOX_CRON') && PHPFOX_CRON) || (!Phpfox::getParam('core.mail_queue'));
                                if ($bSendImmediately) {
                                    $bIsSent = $this->_oMail->send($aUser['email'], $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                                } else {
                                    $bIsSent = $this->_queue($aUser['email'], $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                                }
                            }
                        }
                    }
                }
            }

            if (Phpfox::getParam('core.enable_register_with_phone_number') && !$this->_bSkipSms) {
                $bIsSent = $this->sendSmsNotifications($sIds, $bSendImmediately);
            }
        }


        if ($sPlugin = Phpfox_Plugin::get('mail_send_call_2')) {
            eval($sPlugin);
        }

        if (!empty($sEmails)) {
            $aEmails = explode(',', $sEmails);
            foreach ($aEmails as $sEmail) {
                $sEmail = trim($sEmail);

                if (is_array($this->_aMessage)) {
                    $sMessage = _p($this->_aMessage[0], $this->_aMessage[1], Phpfox::getParam('core.default_lang_id'));
                } else {
                    $sMessage = $this->_aMessage;
                }
                if (is_array($this->_aMessagePlain)) {
                    $sMessagePlain = _p($this->_aMessagePlain[0], $this->_aMessagePlain[1], Phpfox::getParam('core.default_lang_id'));
                } else {
                    $sMessagePlain = $this->_aMessagePlain;
                }
                if (is_array($this->_aSubject)) {
                    $sSubject = _p($this->_aSubject[0], $this->_aSubject[1], Phpfox::getParam('core.default_lang_id'));
                } else {
                    $sSubject = $this->_aSubject;
                }
                if (isset($aUser)) {
                    $sEmailSig = $this->_getSignature($aUser);
                } else {
                    $sEmailSig = $this->_getSignature();
                }
                $sEmailSig = $this->_getTranslatePhrase($sEmailSig, $aUser['language_id']);
                $sMessagePlain = $this->_getTranslatePhrase($sMessagePlain, $aUser['language_id']);
                $sMessage = $this->_getTranslatePhrase($sMessage, $aUser['language_id']);
                $sSubject = $this->_getTranslatePhrase($sSubject, $aUser['language_id']);
                $sSubject = html_entity_decode($sSubject, null, 'UTF-8');
                $sMessageHello = _p('hello_comma');

                // Load plain text template
                $sTextPlain = Phpfox_Template::instance()->assign([
                        'bHtml' => false,
                        'sMessage' => $this->_aMessagePlain !== null ? $sMessagePlain : $sMessage,
                        'sEmailSig' => $sEmailSig,
                        'bMessageHeader' => $this->_bMessageHeader,
                        'sMessageHello' => $sMessageHello
                    ]
                )->getLayout('email', true);

                // Load HTML text template
                $sTextHtml = Phpfox_Template::instance()->assign([
                        'bHtml' => true,
                        'sMessage' => str_replace("\n", "<br />", $sMessage),
                        'sEmailSig' => str_replace("\n", "<br />", $sEmailSig),
                        'bMessageHeader' => $this->_bMessageHeader,
                        'sMessageHello' => $sMessageHello
                    ]
                )->getLayout('email', true);

                if ($sPlugin = Phpfox_Plugin::get('mail_send_call_3')) {
                    eval($sPlugin);
                }

                if (empty($sEmail)) {
                    continue;
                }

                if (defined('PHPFOX_CACHE_MAIL')) {
                    $bIsSent = $this->_cache($sEmail, $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                } else {
                    $bSendImmediately = $bSendImmediately || (defined('PHPFOX_CRON') && PHPFOX_CRON) || (!Phpfox::getParam('core.mail_queue'));
                    if ($bSendImmediately) {
                        $bIsSent = $this->_oMail->send($sEmail, $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                    } else {
                        $bIsSent = $this->_queue($sEmail, $sSubject, $sTextPlain, $sTextHtml, $this->_sFromName, $this->_sFromEmail);
                    }
                }
            }
        }

        if ($sPlugin = Phpfox_Plugin::get('mail_send_call_4')) {
            eval($sPlugin);
        }
        $this->_aUsers = null;
        return $bIsSent;
    }

    private function _cache($sEmail, $sSubject, $sTexPlain, $sTextHtml, $sFromName, $sFromEmail)
    {
        Phpfox_File::instance()->write(PHPFOX_DIR_FILE . 'log' . PHPFOX_DS . 'email_' . md5(str_replace(' ', '_', $sSubject) . PHPFOX_TIME . uniqid()) . '.html', "<b>Email:</b> {$sEmail}<br />\n<b>Subject:</b> {$sSubject}\n<br /><b>Text Plan:</b>{$sTexPlain}\n<br /><b>Text HTML:</b> {$sTextHtml}\n<br /><b>From Name:</b> {$sFromName}\n<br /><b>From Email:</b> {$sFromEmail}");

        return true;
    }

    private function _queue($sEmail, $sSubject, $sTexPlain, $sTextHtml, $sFromName, $sFromEmail)
    {
        $aParams = [
            'email' => $sEmail,
            'subject' => $sSubject,
            'text_plain' => $sTexPlain,
            'text_html' => $sTextHtml,
            'from_name' => $sFromName,
            'from_email' => $sFromEmail
        ];
        Phpfox_Queue::instance()->addJob('core_email_queue', $aParams);

        return true;
    }

    private function _cacheSMS($sPhone, $sTextPlain)
    {
        Phpfox_File::instance()->write(PHPFOX_DIR_FILE . 'log' . PHPFOX_DS . 'sms_' . md5($sPhone . PHPFOX_TIME . uniqid()) . '.html', "<b>Phone:</b> {$sPhone}<br />\n<b>Content: </b>{$sTextPlain}\n<br />");

        return true;
    }
    private function _queueSMS($sPhone, $sTexPlain)
    {
        $aParams = [
            'phone' => $sPhone,
            'content' => $sTexPlain,
        ];
        Phpfox_Queue::instance()->addJob('core_phone_queue', $aParams);

        return true;
    }

    public function cronSend($aParams, $bIsSms = false)
    {
        if ($bIsSms) {
            Phpfox::getLib('phpfox.verify')->sendSMS($aParams['phone'], html_entity_decode($aParams['content'], ENT_QUOTES));
        } else {
            $this->_oMail->send($aParams['email'], $aParams['subject'], $aParams['text_plan'], $aParams['text_html'], $aParams['from_name'], $aParams['from_email']);
        }
    }

    public function sendSmsNotifications($sIds, $bSendImmediately = false)
    {
        $bIsSent = true;
        if ($this->_aUsers === null) {
            if ($this->_sNotification !== null) {
                Phpfox_Database::instance()->select('un.user_notification, ')->leftJoin(Phpfox::getT('user_notification'), 'un', "un.notification_type = 'sms' AND un.user_id = u.user_id AND (un.user_notification = '" . Phpfox_Database::instance()->escape($this->_sNotification) . "' OR un.user_notification = 'core.enable_notifications')");
            }
            $aUsers = Phpfox_Database::instance()->select('u.user_id, u.email, u.language_id, u.full_name, u.user_group_id, u.full_phone_number')
                ->from(Phpfox::getT('user'), 'u')
                ->where('u.user_id IN(' . $sIds . ')')
                ->execute('getSlaveRows');
        } else {
            $aUsers = $this->_aUsers;
        }
        if (!empty($aUsers) && count($aUsers) > 0) {
            foreach ($aUsers as $aUser) {
                // User is banned, lets not send them any sms
                if (isset($aUser['user_group_id']) && Phpfox::getService('user.group.setting')->getGroupParam($aUser['user_group_id'], 'core.user_is_banned')) {
                    continue;
                }
                // Lets not send out an sms to myself
                if ($this->_bSendToSelf === false && $aUser['user_id'] == Phpfox::getUserId()) {
                    continue;
                }

                $bCanSend = true;
                if ($this->_sNotification !== null && isset($aUser['user_notification']) && $aUser['user_notification']) {
                    $bCanSend = false;
                }
                if ($bCanSend === true) {
                    // load the messages in their language
                    $aUser['language_id'] = ($aUser['language_id'] == null || empty($aUser['language_id'])) ? Phpfox::getParam('core.default_lang_id') : $aUser['language_id'];

                    if (is_array($this->_aMessage)) {
                        $sMessage = _p($this->_aMessage[0], isset($this->_aMessage[1]) ? array_merge($aUser, $this->_aMessage[1]) : $aUser, $aUser['language_id']);
                    } else {
                        if ($this->_bTranslated) {
                            $sMessage = $this->_aMessage;
                        } elseif (is_string($this->_aMessage) && Core\Lib::phrase()->isPhrase($this->_aMessage)) {
                            $sMessage = _p($this->_aMessage, [], $aUser['language_id']);
                        } else {
                            $sMessage = $this->_aMessage;
                        }

                    }

                    if (is_array($this->_aMessagePlain)) {
                        $sMessagePlain = _p($this->_aMessagePlain[0], isset($this->_aMessagePlain[1]) ? array_merge($aUser, $this->_aMessagePlain[1]) : $aUser, $aUser['language_id']);
                    } else {
                        $sMessagePlain = _p($this->_aMessagePlain, [], $aUser['language_id']);
                    }

                    $sMessage = $this->_getTranslatePhrase($sMessage, $aUser['language_id']);
                    $sMessagePlain = $this->_getTranslatePhrase($sMessagePlain, $aUser['language_id']);
                    $sMessageHello = _p('hello_name_comma', ['name' => $aUser['full_name']], $aUser['language_id']);

                    // Load plain text
                    $sTextPlain = $sMessageHello . ' ' . lcfirst($this->_aMessagePlain !== null ? $sMessagePlain : $sMessage);
                    $sTextPlain = preg_replace_callback('/<a[^>]+href="([^\"]+)?"[^>]*>(.*)?<\/a>/', function($aMatch) {
                        $link = isset($aMatch[1]) ? $aMatch[1] : '';
                        $text = isset($aMatch[2]) ? $aMatch[2] : '';
                        if ($link != $text) {
                            return $text . ' ('. $link .')';
                        } else {
                            return ' ' . $link;
                        }
                    }, $sTextPlain);
                    $sTextPlain = strip_tags($sTextPlain);

                    if (defined('PHPFOX_DEFAULT_OUT_PHONE')) {
                        $aUser['full_phone_number'] = PHPFOX_DEFAULT_OUT_PHONE;
                    }

                    (($sPlugin = Phpfox_Plugin::get('mail_sms_send_call')) ? eval($sPlugin) : false);

                    if (empty($aUser['full_phone_number'])) {
                        continue;
                    }

                    if (defined('PHPFOX_CACHE_SMS')) {
                        $bIsSent = $this->_cacheSMS($aUser['full_phone_number'], $sTextPlain);
                    } else {
                        $bSendImmediately = $bSendImmediately || (defined('PHPFOX_CRON') && PHPFOX_CRON) || (!Phpfox::getParam('core.mail_queue'));
                        if ($bSendImmediately) {
                            $bIsSent = Phpfox::getLib('phpfox.verify')->sendSMS($aUser['full_phone_number'], html_entity_decode($sTextPlain, ENT_QUOTES));
                        } else {
                            $bIsSent = $this->_queueSMS($aUser['full_phone_number'], $sTextPlain);
                        }
                    }
                }
            }
        }
        return $bIsSent;
    }
    /**
     * @param string $text
     * @param string $sLanguageId
     *
     * @return string
     */
    private function _getTranslatePhrase($text, $sLanguageId = 'en')
    {
        $aReplacements = [
            'site_name' => Phpfox::getParam('core.site_title'),
            'site_email' => Phpfox::getParam('core.email_from_email')
        ];
        $return = preg_replace_callback('/\{setting var=\'(.*)\'\}/is', function ($matches) {
            return _p($matches[1]);
        }, $text);
        $return = preg_replace_callback('/\{phrase var=\'(.*)\'\}/is', function ($matches) use ($aReplacements, $sLanguageId) {
            return _p($matches[1], $aReplacements, $sLanguageId);
        }, $return);
        $return = preg_replace_callback('/\{_p var=\'(.*)\'\}/is', function ($matches) use ($aReplacements, $sLanguageId) {
            return _p($matches[1], $aReplacements, $sLanguageId);
        }, $return);
        return $return;
    }

    /**
     * Get signature of site when send email out
     * @param $aUser
     * @return string
     */
    private function _getSignature($aUser = null)
    {
        if (!isset($aUser)) {
            $aUser['language_id'] = Phpfox::getService('language')->getDefaultLanguage();
        }
        $sSignature = Phpfox::getParam('core.mail_signature');
        if (Core\Lib::phrase()->isPhrase($sSignature)) {
            return _p($sSignature, [], $aUser['language_id']);
        } else {
            return $this->_getTranslatePhrase($sSignature, $aUser['language_id']);
        }
    }


    /**
     * Checks to validate an email.
     *
     * @param string $sEmail email to check
     * @return boolean
     */
    private function _checkEmail($sEmail)
    {
        return !!filter_var($sEmail, FILTER_VALIDATE_EMAIL);
    }
}