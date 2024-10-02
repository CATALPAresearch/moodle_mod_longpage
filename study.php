<?php

require('../../config.php');
const COURSE_ID = 60;
const PARTICIPANTS_ROLE_ID = 10;

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
if(isloggedin()) 
{
    require_logout();
    //redirect to current page
    redirect($_SERVER['PHP_SELF']);
}

$string = generateRandomString();
$name = $string;
$password= $string;

$user = create_user_record($name, $password);
$reason = null;

complete_user_login($user);

$user->firstname = "Proband";
$user->lastname =(string) ($user->id);
$user->idnumber = (string) ($user->id);
if(isset($_GET['test'])) {
    $user->idnumber = "test" . $user->idnumber;
}
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