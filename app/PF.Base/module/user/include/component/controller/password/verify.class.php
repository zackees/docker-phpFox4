<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Password_Verify
 */
class User_Component_Controller_Password_Verify extends Phpfox_Component
{
    /**
     * Process the controller
     *
     */
    public function process()
    {
        if (Phpfox::isUser()) {
            $this->url()->send('');
        }

        if ($sRequest = $this->request()->get('id')) {
            if (($aVals = $this->request()->getArray('val'))) {
                $oValid = Phpfox::getLib('validator')->set(['sFormName' => 'js_request_password_form', 'aParams' => [
                    'newpassword' => [
                        'def' => 'password',
                        'title' => _p('provide_a_valid_new_password')
                    ],
                    'newpassword2' => [
                        'def' => 'reenter',
                        'compare_with' => 'newpassword',
                        'subtitle' => _p('provide_a_valid_confirm_password'),
                        'title' => _p('your_confirmed_password_does_not_match_your_new_password')
                    ]
                ]]);
                if ($oValid->isValid($aVals)) {
                    if (Phpfox::getService('user.password')->updatePassword($sRequest, $aVals)) {
                        $this->url()->send('user.login', null, _p('password_successfully_updated'));
                    }
                }
            }
            if (Phpfox::getParam('user.shorter_password_reset_routine')) {
                if (Phpfox::getService('user.password')->isValidRequest($sRequest) == true) {
                    $this->template()->assign(array('sRequest' => $sRequest));
                }
            } else {
                $bIsPhone = (bool)$this->request()->get('is_phone');
                if (Phpfox::getService('user.password')->verifyRequest($sRequest)) {
                    if ($bIsPhone) {
                        $this->url()->send('user.login', null, _p('new_password_successfully_sent_check_your_phone_number_to_use_your_new_password'));
                    } else {
                        $this->url()->send('user.login', null, _p('new_password_successfully_sent_check_your_email_to_use_your_new_password'));
                    }
                }
            }
        }

        $this->template()
            ->setTitle(_p('password_request_verification'))
            ->setBreadCrumb(_p('password_request_verification'))
            ->assign([
                'sPasswordDescription' => _p(Phpfox::getParam('user.required_strong_password') ? 'strong_password_form_description' : 'normal_password_form_description',
                    ['min' => Phpfox::getParam('user.min_length_for_password'), 'max' => Phpfox::getParam('user.max_length_for_password')])
            ])
        ;
    }
}
