<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/longpage/lib.php');
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once($CFG->dirroot.'/mod/questionnaire/questionnaire.class.php');
require_once($CFG->libdir.'/filelib.php');

const COURSE_ID = 4;
const ENROL_ID = 7;
const MODULE_ID = 21;
const PRESURVEY_CM_ID = 86;//317;//296; // 86;
const ANSWER1_ID = 0;
const ANSWER2_ID = 1;
const SECONDSTEP_CM_ID = 136;
const THIRDSTEP_CM_ID = 156;
const MOODLE_URL = "https://aple.moodle.staging.fernuni-hagen.de/";

$longpagetext1 = "<p><em>Bitte legen Sie mindestens fünf Aufgaben an und geben Sie diese frei, wenn Sie mit ihnen zufrieden sind. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=4\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>, um mit der Studie fortzufahren.<br></em></p>";
$longpagetext2 = "<p><em>Bitte legen Sie mindestens fünf Aufgaben an und geben Sie diese frei, wenn Sie mit ihnen zufrieden sind. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=4\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>, um mit der Studie fortzufahren.<br></em></p>";
$trainingtext = "<p><em>Bitte legen Sie mindestens drei Aufgaben an und geben Sie diese frei, wenn Sie mit ihnen zufrieden sind. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=4\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>, um mit der Studie fortzufahren.<br></em></p><h3>Aufbau und Änderung von Gewohnheiten</h3>
<p>Es lohnt sich, bereits <em>früh </em>im
    Studium förderliche Lern- und Arbeitsroutinen zu etablieren. Denn der
    Aufbau neuer (guter) Gewohnheiten ist deutlich einfacher als
    der Abbau bereits bestehender (schlechter) Gewohnheiten. Gute
    Gewohnheiten
    sind natürlich nicht nur im Studium hilfreich, sondern auch in allen
    anderen
    Lebensbereichen. Wählen Sie Ihre neuen Gewohnheiten jedoch mit
    Bedacht aus.
    Gelingt der Gewohnheitsaufbau, lässt sich die Gewohnheit nicht mehr
    einfach
    so „löschen“.</p>
<h4>Erwünschte Gewohnheiten aufbauen</h4>
<p>Eine neue Gewohnheit aufzubauen ist
    wie einen <em>Trampelpfad </em>anzulegen:
    Wenn Sie einmal durch eine Wiese mit hohem Gras laufen, stellen sich
    die Grashalme sofort wieder auf. Wenn Sie jedoch immer und immer wieder
    denselben Weg durch
    das hohe Gras laufen, entsteht
    mit der Zeit ein Pfad,
    der auch nicht
    mehr so schnell zuwächst. Wenn Sie eine neue Gewohnheit aufbauen,
    legen Sie aktiv einen
    solchen „Trampelpfad“ in Ihrem Gehirn an. Dazu brauchen Sie einen
    Auslösereiz, eine Routine und eine Belohnung. Diese drei Komponenten
    fügen
    Sie zu einer Schleife
    zusammen. Durch vielfache Wiederholung automatisieren
    Sie die Schleife und das erwünschte Verhalten.</p>

<p>Lassen Sie mich zur
    Veranschaulichung ein bereits früher genanntes Beispiel wieder
    aufgreifen: Sie möchten zukünftig abends eine
    To-Do-Liste für den nächsten Tag schreiben (Routine). Als
    Auslösereiz wählen
    Sie das Beenden des Zähneputzens. Sobald Sie mit dem Putzen der
    Zähne fertig
    sind, setzen Sie sich an den Schreibtisch und notieren Ihre To-Dos.
    Ihre Belohnung könnte
    (zunächst) sein, dass Sie nach dem To-Do-Liste-Schreiben noch zehn
    Minuten
    ein spannendes Buch lesen. Diese Schleife durchlaufen Sie nun einige
    Male ganz bewusst und immer in gleicher Form. Dadurch bringen Sie
    Ihrem Gehirn bei,
    Auslösereiz, Routine und Belohnung miteinander zu verknüpfen. Das
    wird am Anfang vielleicht lästig sein – möglicherweise stellen
    Sie aber auch bald fest, dass Ihnen das
    To-Do-Liste-Schreiben sogar Spaß macht, Sie sich danach erleichtert
    fühlen und besser schlafen können. In diesem Fall bräuchten Sie die
    extrinsische Belohnung (Buch lesen)
    vielleicht irgendwann gar nicht
    mehr. Nach einiger Zeit werden
    Sie merken, dass Sie sich nicht mehr bewusst
    an Ihre neue Routine erinnern müssen, sondern sich automatisch nach
    dem
    Zähneputzen hinsetzen und Ihre To-Dos notieren.</p>

<p>In Bezug auf die einzelnen Komponenten der
    Gewohnheitsschleife gilt es Folgendes zu beachten:<br>

    ▸ <em>Auslösereiz</em>: Der
    Auslösereiz sollte auffällig-markant sein, sowie häufig und regelmäßig
    auftreten. Fast alle Auslösereize unserer täglichen Gewohnheiten stammen aus
    einer der folgenden fünf Kategorien:
    <br>
    ▸ Standorte oder Umgebungen (z. B. Schreibtisch, Keller, Hörsaal)<br>▸ Uhr- oder Tageszeiten (z. B. 8.00 Uhr, mittags, nach dem Abendessen)<br>▸ Emotionaler Zustand
    (z. B. Ärger,
    Freude, Langeweile)<br>▸ Andere Menschen (z. B. Nachbar/-in, Partner/-in, Lerngruppe)<br>▸ Unmittelbar vorangehende Handlungen (z. B. duschen, Kaffee kochen, PC anschalten)
    <br><br>
    Ereignisse eignen sich dabei besser als genaue Uhrzeiten.
    Die neue Routine kann zudem
    in einen bereits
    etablierten Ablauf (z. B. zwischen Zähneputzen und ins Bett gehen)
    „eingeklemmt“ oder daran angehängt werden. Häufig
    stoßen nach einiger
    Zeit nicht mehr nur die spezifischen Auslösereize die Routine an (z.
    B. ein
    bestimmter Schreibtisch),
    sondern auch verwandte Reize (z. B.
    ein beliebiger Schreibtisch). Der
    Einsatz von Erinnerungen (z. B.
    Haftnotiz „To-Do-Liste!“ am Badspiegel) kann beim Gewohnheitsaufbau ebenfalls
    helfen.
</p>

<p>▸ <em>Routine: </em>Zunächst sollte eine einfache, gut umsetzbare Routine ausgewählt werden,
    die man stets in gleicher
    Form durchführen kann. Variation sollte vor allem am Anfang vermieden werden –
    sie mag zwar vor Langeweile schützen, ist aber anstrengend und inkompatibel
    mit der Entwicklung von Automatizität. Bei komplexeren Routinen (z. B. eine Runde durch die Stadt joggen)
    kann man dafür sorgen, dass zumindest die ersten
    Handlungsschritte immer relativ gleichförmig ablaufen (z. B. Laufschuhe anziehen – Schlüsselband um
    den Hals hängen – Haus verlassen).</p>
<p>▸ <em>Belohnung</em>: Die Belohnung muss unmittelbar auf die Ausführung der Routine folgen
    und eine „echte“
    Belohnung sein – also subjektiv als angenehm und befriedigend wahrgenommen werden.
    Optimalerweise werden diese positiven Gefühle durch die Aktivität selbst
    hervorgerufen, z. B. weil die Ausführung Spaß macht oder man
    stolz darauf ist, es geschafft zu haben (= intrinsischer Anreiz). Man kann aber
    zunächst auch mit einem extrinsischen Anreiz nachhelfen. Beispielsweise tragen positive Rückmeldungen durch andere („Super,
    wie Du das durchziehst!“) dazu bei, dass die (intrinsische) Motivation beim
    Aufbau neuer Gewohnheiten länger aufrechterhalten werden kann. Materielle
    Belohnungen oder Lob können den Gewohnheitsaufbau also unterstützen –
    interessanterweise vor allem dann, wenn ihr Auftreten unerwartet ist (z. B. wenn man unerwartet von einem/-r
    Dozent/-in gelobt wird). Extrinsische Anreize sollten jedoch nicht zum
    eigentlichen Ziel des Verhaltens werden. Das eigentliche Ziel ist und bleibt
    das Verhalten selbst. Zudem sollte man sich nur belohnen, wenn man die Routine
    auch wirklich ausgeführt hat. Die Belohnung darf also nichts sein, das
    unabhängig vom Ausführen oder Nichtausführen der Routine sowieso auftreten
    wird (z. B. Abendessen).
</p>

<p>Der (willentlich angestoßene) Gewohnheitsaufbau umfasst drei Phasen:<br>
    ▸ In der <strong>Initiierungsphase </strong>wird eine bewusste
    Entscheidung getroffen, welche neue
    Gewohnheit aufgebaut werden soll, sowie Auslösereiz,
    Routine und Belohnung bestimmt.<br>
    ▸ Darauf folgt die <strong>Lernphase</strong>. Durch wiederholtes
    Durchlaufen der Gewohnheitsschleife wird die Gewohnheit immer regelmäßiger und
    automatischer ausgeführt, d. h. die
    Gewohnheitsstärke nimmt kontinuierlich zu.<br>
    ▸ In der <strong>Stabilitätsphase </strong>erreicht
    die Gewohnheitsstärke ein Plateau.
    Man muss sich nicht mehr aktiv ans Handeln erinnern, die Routine hat
    sich
    verselbstständigt und wird mit minimaler Anstrengung weiter
    aufrechterhalten. Dass diese Phase erreicht ist, kann man auch daran
    erkennen,
    dass einem etwas fehlt, wenn man die Routine einmal nicht ausführt.</p>


<p>Der Aufbau einer neuen Gewohnheit dauert im Mittel <em>66 Tage</em>,
    also knapp zehn Wochen. Dies
    geht aus einer häufig zitierten Studie zum Aufbau
    gesundheitsförderlicher Routinen
    hervor.
    Die Spannweite (also die
    Differenz zwischen kleinstem und größtem Messwert) war in dieser
    Studie
    allerdings sehr groß, mit einem Minimum von 18 und einem Maximum von
    254 Tagen.
    Diese doch sehr unterschiedlich lange Dauer des Gewohnheitsaufbaus
    kann auf persönliche
    Merkmale der Studienteilnehmer/-innen, aber auch auf Unterschiede in
    der
    Komplexität der Routinen zurückzuführen sein – eine simple
    Gewohnheit
    aufzubauen (z. B. nach dem morgendlichen Aufstehen ein Glas
    Wasser zu trinken) benötigt weniger Zeit als eine komplexe
    Gewohnheit (z. B. 15 Minuten Bewegung am Tag). Darüber
    hinaus findet sich nicht immer sofort ein geeigneter
    Auslösereiz/-kontext. Je unkomplizierter sich das erwünschte Verhalten
    in die bereits bestehenden täglichen Abläufe
    integrieren und regelmäßig wiederholen lässt, desto größer ist die
    Chance, dass
    es zur Routine wird.<br>

    Diese Befunde liefern auch eine plausible
    Erklärung für das oft klägliche Scheitern vieler <em>Neujahrsvorsätze </em>innerhalb
    der ersten drei Monate: Man startet
    voller Elan und Enthusiasmus ins neue Jahr: Endlich wird alles
    anders! Ab
    sofort bin ich ein neuer Mensch! Nach ein paar Wochen (oder auch
    schon Tagen) lässt dieser Anfangsenthusiasmus dann nach – ohne dass sich
    bereits tiefgreifende
    Veränderungen eingestellt hätten. Frustriert wirft man das Handtuch
    und
    resümiert: „Neujahrsvorsätze funktionieren einfach nicht!“ Die
    Erkenntnis, dass
    sich spürbare Veränderungen oft erst nach vielen Wochen oder sogar
    Monaten
    einstellen, ermöglicht die Anpassung solch überzogener Vorstellungen
    und eine
    realistischere Planung.<br>

    Ermutigend an der Studie ist zudem der
    Befund, dass einzelne <strong>Auslassungen </strong>der
    neuen Routine („verpasste Gelegenheiten“) den Gewohnheitsaufbau nicht entscheidend verlangsamen. Es bedeutet
    also keinen Rückschlag, wenn man ein- oder zweimal aussetzt. Zwar ist es
    besser, insbesondere am Anfang diszipliniert zu sein und die Routine möglichst
    zuverlässig auszuführen. Bei so vielen kleinen Schritten fällt ein Schritt mehr oder weniger jedoch
    nicht so sehr ins Gewicht.
    Die Überzeugung, dass man
    den Aufbau einer neuen Routine
    nach einmaligem Aussetzen
    eigentlich auch gleich wieder bleiben lassen könne, „weil es ja jetzt
    sowieso keinen Sinn mehr hat“, wäre damit als Rationalisierung entlarvt. Und
    wenn es sich um eine Gewohnheit
    handelt, die man dauerhaft etablieren möchte (z. B. für viele Jahre oder Jahrzehnte), ist es vielleicht auch gar
    nicht so wichtig, ob es nun ein paar
    Tage kürzer oder länger gedauert hat, sie aufzubauen.</p>
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
//set cookie from $_COOKIE in header
// $cookies = implode("; ", array_map(function($key, $value) {
//     return $key . "=" . $value;
// }, array_keys($_COOKIE), $_COOKIE));
// $curl->setHeader("Cookie", $cookies);
// print_r($cookies);


function login() {
    global $ch, $cookies, $curl, $USER;
    $credentials = json_decode(file_get_contents("credentials.json"), true);
    $credentials = array_filter($credentials, function($item) {
        return $item["moodleUrl"] == MOODLE_URL;
    });
    $credentials = reset($credentials);
    $moodleUser = $credentials["moodleUser"];
    $moodlePassword = $credentials["moodlePassword"];
    //$moodleUser = $USER->username;
    //$moodlePassword = $USER->password;

    // curl_setopt($ch, CURLOPT_URL, MOODLE_URL . 'login/index.php');
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //$response = curl_exec($ch);
    $contents = $curl->get(MOODLE_URL . 'login/index.php');
    $response = $curl->getResponse();


    preg_match('/<input type="hidden" name="logintoken" value="(.*)">/', $contents, $matches);
    $token = $matches[1];
    //print_r($matches);

    // curl_setopt($ch, CURLOPT_URL, MOODLE_URL . 'login/index.php');
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    //     'username' => $moodleUser,
    //     'password' => $moodlePassword,
    //     'logintoken' => $token
    // ]));
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //$response = curl_exec($ch);
    $contents = $curl->post(MOODLE_URL . 'login/index.php', [
        'username' => $moodleUser,
        'password' => $moodlePassword,
        'logintoken' => $token
    ]);
    $response = $curl->getResponse();
    // $cookies = curl_getinfo($ch, CURLINFO_COOKIELIST);
    // //print_r($cookies);
    // //from Array ( [0] => aple.moodle.staging.fernuni-hagen.de FALSE / TRUE 0 MoodleSession 2tttdndat7ehlcr9v1rhqkfkb1 )  to "MoodleSession=2tttdndat7ehlcr9v1rhqkfkb1; colour=red"
    // $cookies = implode("; ", array_map(function($item) {
    //     return explode("\t", $item)[5] . "=" . explode("\t", $item)[6];
    // }, $cookies));


    //curl_pause($ch, CURLPAUSE_ALL);
}

function createlongpage($longpagename, $tags, $longpagetext, $sectionid, $availabilityConditions) {
    global $curl;
    $contents = $curl->get(MOODLE_URL . "course/modedit.php?add=longpage&type=&course=" . COURSE_ID . "&section=$sectionid&return=0&sr=0");
    $response = $curl->getResponse();
    //print_r($contents);

    preg_match('/<input type="hidden" name="introeditor\[itemid\]" value="(.*)"/', $contents, $matches);
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
        "gradepass" => in_array("tour", $tags) ? "3" : "5",
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

//login();
$id = createlongpage("1. Übungstext (ohne KI-Unterstützung)", ["noAI", "tour"], $GLOBALS['trainingtext'], 2, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => PRESURVEY_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false]
]);

$id = createlongpage("2. Übungstext (mit KI-Unterstützung)", ["AI", "tour"], $GLOBALS['trainingtext'], 2, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => PRESURVEY_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => $id, "e" => 1],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false, false]
]);

$id = createlongpage("1. Lehrtext (ohne KI-Unterstützung)", ["noAI"], $GLOBALS['longpagetext1'], 3, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => $id, "e" => 1],
        ["type" => "completion", "cm" => THIRDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false, false]
]);

createlongpage("2. Lehrtext (mit KI-Unterstützung)", ["AI"], $GLOBALS['longpagetext2'], 3, [
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