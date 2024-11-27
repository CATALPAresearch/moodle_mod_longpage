<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/longpage/lib.php');
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once($CFG->dirroot.'/mod/questionnaire/questionnaire.class.php');
require_once($CFG->libdir.'/filelib.php');

const COURSE_ID = 60;
const ENROL_ID = 136;
const MODULE_ID = 35;
const PRESURVEY_CM_ID = 6006; //573;//317;//296; // 86;
const ANSWER1_ID = 0;
const ANSWER2_ID = 2;
const SECONDSTEP_CM_ID = 6007;
const THIRDSTEP_CM_ID = 6008;
const MOODLE_URL = "https://aple.moodle.staging.fernuni-hagen.de/";

$longpagetext1 = "<p><em>Bitte legen Sie mindestens drei Aufgaben an. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=".COURSE_ID."\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>.<br></em></p>";
$longpagetext2 = "<p><em>Bitte legen Sie mindestens drei Aufgaben an. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=".COURSE_ID."\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>.<br></em></p>";
$trainingtext = "<p><em>Bitte legen Sie mindestens drei Aufgaben an. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=".COURSE_ID."\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>.<br></em></p>
";

list($cm, $course, $questionnaire) = questionnaire_get_standard_page_items(PRESURVEY_CM_ID);
$questionnaire = new questionnaire($course, $cm, 0, $questionnaire);
$response = $questionnaire->get_responses($USER->id);
if ($response) {
    $entry = reset($response);
    $responses = $questionnaire->get_structured_response($entry->id);
    $longpagetext1 .= $responses[ANSWER1_ID]->answers[0];
    $longpagetext2 .= $responses[ANSWER2_ID]->answers[0];
}

$curl = new \curl(["cookie" => true]);

function login() {
    global $curl;
    $credentials = json_decode(file_get_contents("credentials.json"), true);
    $credentials = array_filter($credentials, function($item) {
        return $item["moodleUrl"] == MOODLE_URL;
    });
    $credentials = reset($credentials);
    $moodleUser = $credentials["moodleUser"];
    $moodlePassword = $credentials["moodlePassword"];

    $contents = $curl->get(MOODLE_URL . 'login/index.php');
    $response = $curl->getResponse();
    preg_match('/<input type="hidden" name="logintoken" value="(.*)">/', $contents, $matches);
    $token = $matches[1];

    $contents = $curl->post(MOODLE_URL . 'login/index.php', [
        'username' => $moodleUser,
        'password' => $moodlePassword,
        'logintoken' => $token
    ]);
    $response = $curl->getResponse();
}

function createlongpage($longpagename, $tags, $longpagetext, $sectionid, $availabilityConditions) {
    global $curl;

    $contents = $curl->get(MOODLE_URL . "course/modedit.php?add=longpage&type=&course=" . COURSE_ID . "&section=$sectionid&return=0&sr=0");
    $response = $curl->getResponse();
    preg_match('/<input type="hidden" name="introeditor\[itemid\]" value="(.*)"/', $contents, $matches);
    
    if(!$matches)
    {
        login();
        $contents = $curl->get(MOODLE_URL . "course/modedit.php?add=longpage&type=&course=" . COURSE_ID . "&section=$sectionid&return=0&sr=0");
        $response = $curl->getResponse();
        preg_match('/<input type="hidden" name="introeditor\[itemid\]" value="(.*)"/', $contents, $matches);
    } 

    $introeditoritemid = $matches[1];
    preg_match('/<input type="hidden" name="longpage\[itemid\]" value="(.*)"/', $contents, $matches);
    $longpageitemid = $matches[1];
    preg_match('/<input type="hidden" name="sesskey" value="(.*)"/', $contents, $matches);
    $sesskey = $matches[1];

    $data = [
        "display" => "5",
        "completionunlocked" => "1",
        "course" => COURSE_ID,
        "coursemodule" => null,
        "section" => $sectionid,
        "module" => MODULE_ID,
        "modulename" => "longpage",
        "instance" => "null",
        "add" => "longpage",
        "update" => "0",
        "return" => "0",
        "sr" => "0",
        "revision" => "1",
        "sesskey" => $sesskey,
        "_qf__mod_longpage_mod_form" => "1",
        "mform_isexpanded_id_general" => "1",
        "mform_isexpanded_id_contentsection" => "1",
        "mform_isexpanded_id_appearancehdr" => "0",
        "mform_isexpanded_id_modstandardgrade" => "0",
        "mform_isexpanded_id_modstandardelshdr" => "0",
        "mform_isexpanded_id_availabilityconditionsheader" => "0",
        "mform_isexpanded_id_activitycompletionheader" => "0",
        "mform_isexpanded_id_tagshdr" => "0",
        "mform_isexpanded_id_competenciessection" => "0",
        "name" => $longpagename,
        "introeditor[text]" => "",
        "introeditor[format]" => "1",
        "introeditor[itemid]" => $introeditoritemid,
        "showdescription" => "0",
        "longpage[text]" => $longpagetext,
        "longpage[format]" => "1",
        "longpage[itemid]" => $longpageitemid,
        "printheading" => "1",
        "printintro" => "0",
        "showreadingprogress" => "0",
        "showreadingcomprehension" => "1",
        "showsearch" => "0",
        "showtableofcontents" => "0",
        "showposts" => "0",
        "showhighlights" => "0",
        "showbookmarks" => "0",
        "grade[modgrade_type]" => "point",
        "grade[modgrade_point]" => "100",
        "gradecat" => "4",
        "gradepass" => in_array("tour", $tags) ? "3" : "3",
        "visible" => "1",
        "cmidnumber" => null,
        "lang" => null,
        "groupmode" => "0",
        "availabilityconditionsjson" => json_encode($availabilityConditions),
        "completion" => "2",
        "completionview" => "1",
        "completionusegrade" => "1",
        "completionpassgrade" => "1",
        "tags" => "_qf__force_multiselect_submission",
        "competencies" => "_qf__force_multiselect_submission",
        "competency_rule" => "0",
        "submitbutton" => "Speichern und anzeigen"
    ];

    //tags to url parameters
    foreach ($tags as $i => $tag) {
        $data["tags[" . $i . "]"] = $tag;
    }
    
    $contents = $curl->post(MOODLE_URL . "course/modedit.php", $data);
    $response = $curl->getResponse();

    preg_match('/id=(\d+)/', $contents, $matches);
    return $matches[1];
}


// $id = createlongpage("1. Übungstext (ohne KI-Unterstützung)", ["noAI", "tour"], $GLOBALS['trainingtext'], 2, [
//     "op" => "&",
//     "c" => [
//         ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
//         ["type" => "completion", "cm" => PRESURVEY_CM_ID, "e" => 1],
//         ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 0]
//     ],
//     "showc" => [false, false, false]
// ]);

// $id = createlongpage("2. Übungstext (mit KI-Unterstützung)", ["AI", "tour"], $GLOBALS['trainingtext'], 2, [
//     "op" => "&",
//     "c" => [
//         ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
//         ["type" => "completion", "cm" => PRESURVEY_CM_ID, "e" => 1],
//         ["type" => "completion", "cm" => $id, "e" => 1],
//         ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 0]
//     ],
//     "showc" => [false, false, false, false]
// ]);

$id = createlongpage("Ihr erster Lehrtext (ohne KI-Unterstützung)", ["noAI", "tour"], $GLOBALS['longpagetext1'], 2, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => PRESURVEY_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false]
]);

createlongpage("Ihr zweiter Lehrtext (mit KI-Unterstützung)", ["AI", "tour"], $GLOBALS['longpagetext2'], 3, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => $id, "e" => 1],
        ["type" => "completion", "cm" => THIRDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false, false]
]);

redirect(new moodle_url('/course/view.php', ['id' => COURSE_ID]));
?>