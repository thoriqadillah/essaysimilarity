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
 * Question type class for the essay question type.
 *
 * @package    qtype
 * @subpackage essaysimilarity
 * @copyright  2022 thoriqadillah
 * @author     thoriqadillah
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/essaysimilarity/question.php');

class qtype_essaysimilarity extends question_type {
  // Show/hide values for dropdown
  const SHOW_NONE                  = 0;
  const SHOW_STUDENTS_ONLY         = 1;
  const SHOW_TEACHERS_ONLY         = 2;
  const SHOW_TEACHERS_AND_STUDENTS = 3;

  const DEFAULT_UPPER_CORRECTNESS = 0.99;
  const DEFAULT_LOWER_CORRECTNESS = 0.01;

  const EMPTY = '';

  /**
   * Constant value for question language options
   */
  const NO_LANG = 'none';

  public function is_manual_graded() {
    return true;
  }

  public function extra_question_fields() {
    // DB table name of the plugin is the first index, the rest is table column of the plugin table
    return [
      "qtype_essaysimilarity_option", 
      "responseformat",            
      "responserequired",
      "responsefieldlines",
      "minwordlimit",
      "maxwordlimit",
      "attachments",
      "attachmentsrequired",
      "maxbytes",
      "filetypeslist",
      "graderinfo",
      "graderinfoformat",
      "showfeedback",
      "answerkey",
      "answerkeyformat",
      "showanswerkey",
      "showtextstats",
      "textstatitems",
      "responsetemplate",
      "responsetemplateformat",
      "questionlanguage",
      "upper_correctness",
      "lower_correctness",
    ];
  }

  public function response_file_areas() {
    return ['attachments', 'answer'];
  }

  public function get_question_options($question) {
    parent::get_question_options($question);
  }

  public function save_question_options($question) {
    global $DB;

    $plugin = $this->plugin_name();
    $plugintable = 'qtype_essaysimilarity_option';

    $graderinfo = $this->import_or_save_files($question->graderinfo, $question->context, $plugin, 'graderinfo', $question->id);
    $oldquestion = $DB->get_record($plugintable, ['questionid' => $question->id]);

    $textstatitems = '';
    if (!empty($question->textstatitems)) {
      $textstatitems = $question->textstatitems;
      $textstatitems = array_keys($textstatitems);
      $textstatitems = implode(',', $textstatitems);
    }

    $newquestion = (object) [
      "questionid"              => $question->id,
      "responseformat"          => $question->responseformat,
      "responserequired"        => $question->responserequired,
      "responsefieldlines"      => $question->responsefieldlines,
      "minwordlimit"            => isset($question->minwordlimit) ? $question->minwordlimit : 0,
      "maxwordlimit"            => isset($question->maxwordlimit) ? $question->maxwordlimit : 0,
      "attachments"             => $question->attachments,
      "attachmentsrequired"     => $question->attachmentsrequired,
      "maxbytes"                => isset($question->maxbytes) ? $question->maxbytes : 0,
      "filetypeslist"           => isset($question->filetypeslist) ? $question->filetypeslist : self::EMPTY,
      "graderinfo"              => $graderinfo,
      "graderinfoformat"        => $question->graderinfo['format'],
      "showfeedback"            => $question->showfeedback,
      "answerkey"               => $question->answerkey['text'],
      "answerkeyformat"         => $question->answerkey['format'],
      "showanswerkey"           => $question->showanswerkey,
      "showtextstats"           => $question->showtextstats,
      "textstatitems"           => $textstatitems,
      "responsetemplate"        => isset($question->responsetemplate['text']) ? $question->responsetemplate['text'] : self::EMPTY,
      "responsetemplateformat"  => isset($question->responsetemplate['format']) ? $question->responsetemplate['format'] : 0,
      "questionlanguage"        => isset($question->questionlanguage) ? $question->questionlanguage : self::NO_LANG,
      "upper_correctness"       => $question->upper_correctness ? $question->upper_correctness : self::DEFAULT_UPPER_CORRECTNESS,
      "lower_correctness"       => $question->lower_correctness ? $question->lower_correctness : self::DEFAULT_LOWER_CORRECTNESS,
    ];

    if ($oldquestion) {
      $newquestion->id = $oldquestion->id;
      $DB->update_record($plugintable, $newquestion);
    } else {
      $DB->insert_record($plugintable, $newquestion);
    }
  }

  public function delete_question($questionid, $contextid) {
    global $DB;

    $DB->delete_records('qtype_essaysimilarity_option', ['questionid' => $questionid]);
    parent::delete_question($questionid, $contextid);
  }

  protected function initialise_question_instance(question_definition $question, $questiondata) {
    parent::initialise_question_instance($question, $questiondata);

    $defaults = self::get_defaults();
    foreach ($defaults as $name => $default) {
      $question->$name = isset($questiondata->options->$name) ? $questiondata->options->$name : $default;
    }
  }

  /**
   * Get default values of the question
   */
  public static function get_defaults() {
    return [
      "responseformat"          => 'editor',
      "responserequired"        => 1,
      "responsefieldlines"      => 10,
      "minwordlimit"            => 0,
      "maxwordlimit"            => 0,
      "attachments"             => 0,
      "attachmentsrequired"     => 0,
      "maxbytes"                => 0,
      "filetypeslist"           => self::EMPTY,
      "graderinfo"              => self::EMPTY,
      "graderinfoformat"        => 0,
      "showfeedback"            => self::SHOW_TEACHERS_AND_STUDENTS,
      "answerkey"               => self::EMPTY,
      "answerkeyformat"         => 1,
      "showanswerkey"           => self::SHOW_NONE,
      "showtextstats"           => self::SHOW_TEACHERS_ONLY,
      "textstatitems"           => self::EMPTY,
      "responsetemplate"        => self::EMPTY,
      "responsetemplateformat"  => 0,
      "questionlanguage"        => self::NO_LANG,
      "upper_correctness"       => self::DEFAULT_UPPER_CORRECTNESS,
      "lower_correctness"       => self::DEFAULT_LOWER_CORRECTNESS,
    ];
  }

  public function move_files($questionid, $oldcontextid, $newcontextid) {
    parent::move_files($questionid, $oldcontextid, $newcontextid);
    $plugin = $this->plugin_name();
    $fs = get_file_storage();
    $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, $plugin, 'graderinfo', $questionid);
  }

  protected function delete_files($questionid, $contextid) {
    parent::delete_files($questionid, $contextid);
    $plugin = $this->plugin_name();
    $fs = get_file_storage();
    $fs->delete_area_files($contextid, $plugin, 'graderinfo', $questionid);
  }

  public function plugin_name() {
    return 'qtype_essaysimilarity';
  }
  
}