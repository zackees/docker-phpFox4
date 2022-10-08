<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Spam
 */
class User_Component_Controller_Admincp_Spam extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $deleteId = $this->request()->get('delete');

        if ($deleteId) {
            if (Phpfox::getService('user.process')->deleteSpamQuestion($deleteId)) {
                Phpfox::addMessage(_p('question_successfully_deleted'));
            }
            Phpfox::getLib('url')->send('admincp.user.spam');
        }

        $aQuestions = Phpfox::getService('user')->getSpamQuestions(false);

        if (($iQuestionId = $this->request()->getInt('id'))) {
            foreach ($aQuestions as $aQuestion) {
                if ($aQuestion['question_id'] == $iQuestionId) {
                    $aEditQuestion = $aQuestion;
                }
            }
        }

        $this->template()
            ->setBreadCrumb(_p('anti_spam_security_questions'))
            ->setActionMenu([_p('add_new_question') => [
                'url' => $this->url()->makeUrl('admincp.user.spams.add')
            ]
            ])
            ->setActiveMenu('admincp.user.spam')
            ->setTitle(_p('anti_spam_security_questions'))
            ->setSectionTitle(_p('anti_spam_questions'))
            ->assign([
                'sSiteUsePhrase' => $this->url()->makeUrl('admincp.language.phrase.add', ['last-module' => 'user']),
                'aQuestions' => $aQuestions,
                'aEditQuestion' => isset($aEditQuestion) ? $aEditQuestion : null
            ])
            ->setHeader([
                'admin.spam.js' => 'module_user',
                'admin.spam.css' => 'module_user'
            ])
            ->setPhrase([
                'setting_require_all_spam_questions_on_signup',
                'edit_question'
            ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_spam_clean')) ? eval($sPlugin) : false);
    }
}
