<?php

require('../../config.php');
const COURSE_ID = 4;
const PARTICIPANTS_ROLE_ID = 9;

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$string = generateRandomString();
$name = $string;
$password= $string;

//print($name);
$user = create_user_record($name, $password);
//print_r($user);
print($user->username);
print($user->password);
$reason = null;
//$user = authenticate_user_login($user->username, $user->password, false, $reason);
//print_r($user);
//print_r($reason);
complete_user_login($user);

$user->firstname = "Proband";
$user->lastname =(string) ($user->id);
$user->idnumber = (string) ($user->id);
$user->email = $user->username . "@example.com";
user_update_user($user, false, false);

$instance = null;
$enrolinstances = enrol_get_instances(COURSE_ID, true);
foreach ($enrolinstances as $courseenrolinstance) {
    if ($courseenrolinstance->enrol == "manual") {
        $instance = $courseenrolinstance;
        break;
    }
}
$enrol = enrol_get_plugin('manual');
$enrol->enrol_user($instance, $user->id, PARTICIPANTS_ROLE_ID, 0, 0, ENROL_USER_ACTIVE);

redirect(new moodle_url('/course/view.php', ['id' => COURSE_ID]));
?>