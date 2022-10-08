<?php
defined('PHPFOX') or exit('NO DICE!');

/**
 * Class User_Component_Controller_Admincp_Spams_Add
 */
class User_Component_Controller_Admincp_Spams_Add extends Phpfox_Component
{
    /**
     * Controller
     */
    public function process()
    {
        $aQuestion = [];
        $iQuestionId = $this->request()->getInt('id');


        if (($aVals = $this->request()->getArray('val'))) {
            if ($iQuestionId) {
                $aVals['question_id'] = $iQuestionId;
                if (Phpfox::getService('user.process')->editSpamQuestion($aVals)) {
                    $successMessage = _p('question_successfully_updated');
                }
            } elseif (Phpfox::getService('user.process')->addSpamQuestion($aVals)) {
                $successMessage = _p('question_successfully_created');
            }
            if (isset($successMessage)) {
                Phpfox::addMessage($successMessage);
                Phpfox::getLib('url')->send('admincp.user.spam');
            }
        }

        if ($iQuestionId) {
            $aQuestion = Phpfox::getService('user')->getSpamQuestion($iQuestionId);
            $this->template()->assign('sPhraseTitle', $aQuestion['question_phrase']);
            if (!empty($aQuestion['original_answers_phrases'])) {
                $aParsedAnswerPhrases = [];
                foreach ($aQuestion['original_answers_phrases'] as $key => $sAnswerPhrase) {
                    $varName = 'answer_var_name_' . $key;
                    $aParsedAnswerPhrases[] = $varName;
                    $this->template()->assign($varName, $sAnswerPhrase);
                }
                $aQuestion['parsed_answers_phrases'] = $aParsedAnswerPhrases;
            }
        } elseif (!empty($aVals)) {
            // populate form when submit unsuccessfully
            $aQuestion = [
                'question_phrase' => $aVals['question'],
                'is_active' => isset($aVals['is_active']) ? (int)$aVals['is_active'] : 0,
                'case_sensitive' => isset($aVals['case_sensitive']) ? (int)$aVals['case_sensitive'] : 0,
                'answers_phrases' => isset($aVals['answer']) ? $aVals['answer'] : []
            ];
        }

        $this->template()
            ->setBreadCrumb(_p('anti_spam_security_questions'))
            ->setTitle(_p('anti_spam_security_questions'))
            ->setSectionTitle(_p('anti_spam_questions'))
            ->setActiveMenu('admincp.user.spam')
            ->assign([
                'aQuestion' => $aQuestion,
                'aForms' => $aQuestion,
                'iQuestionId' => $iQuestionId,
                'sSiteUsePhrase' => $this->url()->makeUrl('admincp.language.phrase.add', ['last-module' => 'user']),
            ])
            ->setHeader([
                'admin.spam.js' => 'module_user',
            ])
            ->setPhrase([
                'setting_require_all_spam_questions_on_signup',
                'edit_question',
            ]);
    }

    /**
     * Garbage collector. Is executed after this class has completed
     * its job and the template has also been displayed.
     */
    public function clean()
    {
        (($sPlugin = Phpfox_Plugin::get('user.component_controller_admincp_spams_add_clean')) ? eval($sPlugin) : false);
    }
}
