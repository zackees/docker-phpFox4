<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * PHPMailer Sendmail
 *
 * @copyright       [PHPFOX_COPYRIGHT]
 * @author          phpFox LLC
 * @package         Phpfox
 * @version         $Id: mail.class.php 1666 2010-07-07 08:17:00Z phpFox LLC $
 */
class Phpfox_Mail_Driver_Phpmailer_Mail implements Phpfox_Mail_Interface
{
    /**
     * PHPMailer Object
     *
     * @var Object
     */
    private $_oMail;

    /**
     * Class constructor that loads PHPMailer class and sets all the needed variables.
     *
     * @return mixed FALSE if we cannot load PHPMailer, or NULL if we were.
     */
    public function __construct()
    {
        $this->_oMail = new PHPMailer();
        $this->_oMail->From = (Phpfox::getParam('core.email_from_email') ? Phpfox::getParam('core.email_from_email') : 'server@localhost.com');
        $this->_oMail->FromName = (Phpfox::getParam('core.mail_from_name') === null ? Phpfox::getParam('core.site_title') : Phpfox::getParam('core.mail_from_name'));
        $this->_oMail->WordWrap = 75;
        $this->_oMail->CharSet = 'utf-8';
    }

    /**
     * Sends out an email.
     *
     * @param mixed $mTo Can either be a persons email (STRING) or an ARRAY of emails.
     * @param string $sSubject Subject message of the email.
     * @param string $sTextPlain Plain text of the message.
     * @param string $sTextHtml HTML version of the message.
     * @param string $sFromName Name the email is from.
     * @param string $sFromEmail Email the email is from.
     * @return bool TRUE on success, FALSE on failure.
     * @throws phpmailerException
     */
    public function send($mTo, $sSubject, $sTextPlain, $sTextHtml, $sFromName = null, $sFromEmail = null)
    {
        $this->_oMail->addAddress($mTo);
        $this->_oMail->Subject = $sSubject;
        $this->_oMail->Body = $sTextHtml;
        $this->_oMail->AltBody = $sTextPlain;
        $this->_oMail->IsHTML(true);

        $sCoreFromName = Phpfox::getParam('core.mail_from_name');
        $sCoreFromEmail = Phpfox::getParam('core.email_from_email');
        $this->_oMail->FromName = $sCoreFromName;
        $this->_oMail->From = $sCoreFromEmail;

        $this->_oMail->addReplyTo($sFromEmail !== null ? $sFromEmail : $sCoreFromEmail, $sFromName !== null ? $sFromName : $sCoreFromName);

        (($sPlugin = Phpfox_Plugin::get('library_phpfox_mailer_mail_send_start')) ? eval($sPlugin) : false);

        // ignore phpmailer exception
        try {
            $bSentResult = $this->_oMail->send();
        } catch (Exception $ex) {
            $bSentResult = false;
        }

        $this->_oMail->clearAddresses();
        if (!$bSentResult) {
            if (isset($this->_oMail->ErrorInfo)) {
                Phpfox::getLog('mail.log')->error($this->_oMail->ErrorInfo);
            }
            return false;
        }
        return true;
    }

    public function test($aVals)
    {
        $this->_oMail = new PHPMailer();
        $this->_oMail->From = ($aVals['email_from_email'] ? $aVals['email_from_email'] : 'server@localhost.com');
        $this->_oMail->FromName = ($aVals['mail_from_name'] === null ? Phpfox::getParam('core.site_title') : $aVals['mail_from_name']);
        $this->_oMail->WordWrap = 75;
        $this->_oMail->CharSet = 'utf-8';
    }
}
