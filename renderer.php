<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Short answer question renderer class.
 *
 * @package    qtype
 * @subpackage checkpassword
 * @copyright  2024 Bienvenu ACCLOMBESSI [Gits] bienvenu.acclombessi@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for short answer questions.
 *
 * @copyright  2024 Bienvenu ACCLOMBESSI [Gits] bienvenu.acclombessi@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_checkpassword_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
            'class' => 'form-control d-inline',
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }

        $feedbackimg = '';
        if ($options->correctness) {
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));
            if ($answer) {
                $fraction = $answer->fraction;
            } else {
                $fraction = 0;
            }
            $inputattributes['class'] .= ' ' . $this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        if (preg_match('/_____+/', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $inputattributes['size'] = round(strlen($placeholder) * 1.1);
        }
        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;

        if ($placeholder) {
            $inputinplace = html_writer::tag('label', $options->add_question_identifier_to_label(get_string('answer')),
                    array('for' => $inputattributes['id'], 'class' => 'sr-only'));
            $inputinplace .= $input;
            $questiontext = substr_replace($questiontext, $inputinplace,
                    strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$placeholder) {
            $result .= html_writer::start_tag('div', ['class' => 'ablock d-flex flex-wrap align-items-center']);
            $answerspan = html_writer::tag('span', $input, array('class' => 'answer'));
            $label = $options->add_question_identifier_to_label(get_string('answercolon', 'qtype_numerical'), true);
            $result .= html_writer::tag('label', $label . $answerspan,
                    array('for' => $inputattributes['id']));
            $result .= html_writer::end_tag('div');
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        $response = $qa->get_last_qt_var('answer');
        $missingCriteria = [];
    
        // Vérifier les critères du mot de passe
        if (strlen($response) < 8) {
            $missingCriteria[] = get_string('feedbackmissinglength', 'qtype_checkpassword');
        }
        if (!$this->contains_uppercase($response)) {
            $missingCriteria[] = get_string('feedbackmissinguppercase', 'qtype_checkpassword');
        }
        if (!$this->contains_lowercase($response)) {
            $missingCriteria[] = get_string('feedbackmissinglowercase', 'qtype_checkpassword');
        }
        if (!$this->contains_number($response)) {
            $missingCriteria[] = get_string('feedbackmissingnumber', 'qtype_checkpassword');
        }
        if (!$this->contains_special_char($response)) {
            $missingCriteria[] = get_string('feedbackmissingspecialchar', 'qtype_checkpassword');
        }
    
        // Si aucun critère n'est manquant, le mot de passe est fort
        if (empty($missingCriteria)) {
            $feedback = get_string('feedbackstrong', 'qtype_checkpassword');
        } else {
            // Si des critères sont manquants, générer un feedback détaillé
            $feedback = get_string('feedbackweak', 'qtype_checkpassword') . ' ' .
                        get_string('feedbackmissing', 'qtype_checkpassword') . ' ' .
                        implode(', ', $missingCriteria) . '.';
        }
    
        return html_writer::div($feedback, 'specificfeedback');
    }
    
    private function contains_uppercase($password) {
        return preg_match('/[A-Z]/', $password);
    }
    
    private function contains_lowercase($password) {
        return preg_match('/[a-z]/', $password);
    }
    
    private function contains_number($password) {
        return preg_match('/[0-9]/', $password);
    }
    
    private function contains_special_char($password) {
        return preg_match('/[\W_]/', $password); // Vérifie les caractères spéciaux
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer($question->get_correct_response());
        if (!$answer) {
            return '';
        }

        return get_string('correctansweris', 'qtype_checkpassword',
                s($question->clean_response($answer->answer)));
    }
}
