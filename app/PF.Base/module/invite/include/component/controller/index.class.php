<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

class Invite_Component_Controller_Index extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $bCanCheckPermission = !$this->request()->get('user') && !$this->request()->get('id');

        if ($bCanCheckPermission && Phpfox::isUser(true) && !Phpfox::getUserParam('invite.can_invite_friends')) {
            return Phpfox::getLib('module')->setController('invite.invitations');
        }

        list($bIsRegistration, $sNextUrl) = $this->url()->isRegistration(2);
        $bIsAllowSignup = Phpfox::getParam('user.allow_user_registration');

        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_start')) ? eval($sPlugin) : false);

        // is a user sending an invite
        if (($aVals = $this->request()->getArray('val')) && $bIsAllowSignup) {
            $sPersonalMessage = $sPersonalMessageSms = '';
            // add personal message
            if (!empty($aVals['personal_message'])) {
                $sPersonalMessage = _p('full_name_added_the_following_personal_message', ['full_name' => Phpfox::getUserBy('full_name')]) . $aVals['personal_message'];
                $sPersonalMessageSms = _p('full_name_added_the_following_personal_message_sms', ['full_name' => Phpfox::getUserBy('full_name')]) . $aVals['personal_message'];
            }
            // we may have a bunch of emails separated by commas, lets array them
            $aMails = !empty($aVals['emails']) ? explode(',', $aVals['emails']) : [];

            list($aMails, $aInvalid, $aCacheUsers) = Phpfox::getService('invite')->getValid($aMails,
                Phpfox::getUserId());

            $sSubject = _p(
                'full_name_invites_you_to_site_title',
                [
                    'full_name' => Phpfox::getUserBy('full_name'),
                    'site_title' => Phpfox::getParam('core.site_title'),
                ]
            );
            //Emails
            if (!empty($aMails)) {
                foreach ($aMails as $sEmail) {
                    $sEmail = trim($sEmail);
                    // we insert the invite id and send the reference, so we can track which users
                    // have signed up

                    $iInvite = Phpfox::getService('invite.process')->addInvite($sEmail, Phpfox::getUserId(), true);

                    (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_send')) ? eval($sPlugin) : false);

                    $sFromEmail = (!empty(Phpfox::getUserBy('email')) ? Phpfox::getUserBy('email') : Phpfox::getParam('core.email_from_email'));

                    // check if we could send the mail
                    $sLink = Phpfox_Url::instance()->makeUrl('invite', ['id' => $iInvite]);
                    $sMessage = _p(
                            'full_name_invites_you_to_site_title_link',
                            [
                                'full_name' => Phpfox::getUserBy('full_name'),
                                'site_title' => Phpfox::getParam('core.site_title'),
                                'link' => $sLink,
                            ]
                        ) . $sPersonalMessage;

                    Phpfox::getLib('mail')->to($sEmail)
                        ->fromEmail($sFromEmail)
                        ->fromName(Phpfox::getUserBy('full_name'))
                        ->subject($sSubject)
                        ->message($sMessage)
                        ->send();
                }
            }
            $aCacheUsersByPhone = $aPhones = [];
            if (Phpfox::getParam('core.enable_register_with_phone_number')) {
                $aPhones = !empty($aVals['phones']) ? explode(',', $aVals['phones']) : [];
                list($aPhones, $aInvalidByPhone, $aCacheUsersByPhone) = Phpfox::getService('invite')->getValid($aPhones, Phpfox::getUserId(), true);
                $aInvalid = array_merge($aInvalid, $aInvalidByPhone);
            }
            //Phone numbers
            if (!empty($aPhones)) {
                foreach ($aPhones as $sPhone) {
                    $sPhone = trim($sPhone);
                    // we insert the invite id and send the reference, so we can track which users
                    // have signed up

                    $iInvite = Phpfox::getService('invite.process')->addInvite($sPhone, Phpfox::getUserId(), true);

                    (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_sms_send')) ? eval($sPlugin) : false);

                    // check if we could send the mail
                    $sLink = Phpfox_Url::instance()->makeUrl('invite', ['id' => $iInvite]);
                    $sMessage = _p(
                            'full_name_invites_you_to_site_title_link_sms',
                            [
                                'full_name' => Phpfox::getUserBy('full_name'),
                                'site_title' => Phpfox::getParam('core.site_title'),
                                'link' => $sLink,
                            ]
                        ) . $sPersonalMessageSms;

                    Phpfox::getLib('verify')->sendSMS($sPhone, $sMessage);
                }
            }

            if ($bIsRegistration === true) {
                $this->url()->send($sNextUrl, null, _p('your_friends_have_successfully_been_invited'));
            } elseif (empty($aMails) && empty($aPhones)) {
                Phpfox_Error::set(_p(Phpfox::getParam('core.enable_register_with_phone_number') ? 'you_must_fill_in_at_least_valid_email_or_sms' : 'you_must_fill_in_at_least_one_valid_email'));
            }

            if (!empty($aInvalid)) {
                foreach ($aInvalid as $key => $value) {
                    $aInvalid[$key] = Phpfox::getLib('parse.output')->htmlspecialchars($value);
                }
            }

            $this->template()->assign([
                    'aValid' => array_merge($aMails, $aPhones),
                    'aInValid' => $aInvalid,
                    'aUsers' => $aCacheUsers,
                    'aUsersByPhone' => $aCacheUsersByPhone
                ]
            );
        }

        // check if someone is visiting a link sent by email
        if (($iId = $this->request()->get('id'))) {
            if (Phpfox::isUser() == true) {
                $this->url()->send('');
            }
            // we update the entry to be seen:
            if (Phpfox::getService('invite.process')->updateInvite($iId)) {
                $this->url()->send('user.register');
            } else {
                return Phpfox_Error::display(_p('your_invitation_has_expired_or_it_was_not_valid'));
            }
        } // check if someone is visiting from a link pasted in a site or other places
        elseif ($iId = $this->request()->get('user')) {
            Phpfox::getService('invite.process')->updateInvite($iId, false);
            $this->url()->send('user.register');
        }

        $sSiteEmail = !empty(Phpfox::getUserBy('email')) ? Phpfox::getUserBy('email') : Phpfox::getUserBy('full_phone_number');

        $this->template()->setTitle(_p('invite_your_friends'))
            ->setBreadCrumb(_p('invite_your_friends'))
            ->setHeader('cache', [
                'invite.js' => 'module_invite'
            ])
            ->assign([
                    'sFullName' => Phpfox::getUserBy('full_name'),
                    'sSiteEmail' => $sSiteEmail,
                    'sSiteTitle' => Phpfox::getParam('core.site_title'),
                    'sIniviteLink' => Phpfox_Url::instance()->makeUrl('invite', ['user' => Phpfox::getUserId()]),
                    'bIsRegistration' => $bIsRegistration,
                    'sNextUrl' => $this->url()->makeUrl($sNextUrl),
                ]
            )->buildSectionMenu('invite', [
                _p('invite_friends') => '',
                _p('pending_invitations') => 'invite.invitations',
            ]);

        if (!$bIsAllowSignup) {
            return Phpfox_Error::display(_p('you_cant_send_invitations_at_this_time_because_we_have_disabled_user_registration'));
        }

        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_process_end')) ? eval($sPlugin) : false);

        return null;
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('invite.component_controller_index_clean')) ? eval($sPlugin) : false);
    }
}