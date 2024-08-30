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
$trainingtext = "<p><em>Bitte legen Sie mindestens drei Aufgaben an und geben Sie diese frei, wenn Sie mit ihnen zufrieden sind. Wenn Sie fertig sind, klicken Sie <a href=\"https://aple.moodle.staging.fernuni-hagen.de/course/view.php?id=4\" class=\"btn btn-primary disabled\" id=btnContinueStudy>hier</a>, um mit der Studie fortzufahren.<br></em></p><h4>Parzival</h4>
<p><b>Parzival</b> ist ein Versroman der mittelhochdeutschen höfischen Literatur von Wolfram von Eschenbach, der zwischen 1200 und 1210 entstand. Das Werk umfasst etwa 25.000 paarweise gereimte Verse und wird in den modernen Ausgaben in 16 Bücher gegliedert.
<br>
In kunstvoll verzahnten Handlungssträngen einer Doppelromanstruktur werden die Aventiuren
    erzählt, die abenteuerlichen Geschicke zweier ritterlicher Hauptfiguren
    – einerseits die Entwicklung des Titelhelden Parzival (von
    altfranzösisch <i>Perceval</i>) vom Unwissenden im Narrenkleid zum Gralskönig, andererseits die gefahrvollen Bewährungsproben für den Artusritter Gawain.
</p>
<p>Thematisch gehört der Roman zur sogenannten Artusepik, wobei die Aufnahme Parzivals in die Tafelrunde des legendären britischen Königs nur als Durchgangsstation der Gralssuche erscheint, doch dann zur Voraussetzung seiner Bestimmung als Gralskönig wird.
<br>
Der Stoff wurde literarisch, aber auch in der Bildenden Kunst und
    in der Musik vielfach bearbeitet; die nachhaltigste Wirkung erreichte Richard Wagners Adaption für das Musiktheater mit seinem <i>Bühnenweihfestspiel</i> <i>Parsifal</i> (Uraufführung 1882).
</p>
<h4>Thema</h4>
<p>Der Parzival-Stoff behandelt komplexe
    Themengebiete. Es geht um das Verhältnis von Gesellschaft und Weltferne,
    die Gegensätze zwischen Männerwelt und Frauenwelt, die Spannung
    zwischen der höfischen Gesellschaft und der spirituellen Gemeinschaft
    der Gralshüter, um Schuld im existenziellen Sinn, Minne und Sexualität,
    Erlösungs-, Heils-, Heilungs- und Paradiesesphantasien. Aufgegriffen
    wird nach psychologischem bzw. psychoanalytischem Fokus die Entwicklung des Protagonisten von seiner Selbstbezogenheit zur Empathiefähigkeit und zum Ausbruch aus der engen Dyade
    mit Parzivals Mutter Herzeloyde. Parzival ist zunächst ein Ignorant und
    Sünder, der im Handlungsverlauf zu Erkenntnis und Läuterung gelangt und
    bei seinem zweiten Besuch auf der Gralsburg Munsalvaesche den Makel des
    Frageversäumnisses (der mitleidbezeugenden Frage nach dem Leiden seines
    Onkels, des Gralskönigs) wiedergutmachen kann. Parzival ist die Erlösergestalt im Gralsmythos.</p>
<h4>Literaturgeschichtliche Einordnung</h4>
<h5>Geschichte und Struktur</h5>
<p>Unter den Versromanen der mittelhochdeutschen Literatur ragt Wolframs <i>wilde maere</i> – von Gottfried von Straßburg im „Literaturexkurs“ des Tristan polemisch abwertend so genannt – in mehrfacher Hinsicht heraus:
<ul>
    <li>Mit seiner komplexen Sinnstruktur und der aufwendigen erzählerischen Komposition ist der <i>Parzival</i> von vornherein keine „leichte Lektüre“; dennoch kann dem Werk mit über 80 überlieferten Textzeugnissen eine einzigartige Wirkungsgeschichte schon im Mittelalter nachgesagt werden. Joachim Bumke (siehe unten: <i>Literatur</i>)
        spricht von einer „literarischen Sensation“, die das Werk gewesen sein
        müsse, so häufig zitiert und kopiert wie kein anderes im 13.
        Jahrhundert.</li>
</ul>
</p>
<p>
<ul>
    <li>Die Einteilung in 16 Bücher und 827 Abschnitte zu 30 Versen ist der ersten kritischen Edition Karl Lachmanns (1793–1851) von 1833 zu verdanken. Diese Edition ist bis in die Gegenwart gültig und unersetzt geblieben. Zuvor hatte Christoph Heinrich Myller, ein Schüler Johann Jacob Bodmers, einen Editionsversuch unternommen. Ludwig Tiecks Vorhaben von 1801, den <i>Parzival</i> Wolframs zu edieren, wurde nicht verwirklicht.</li>
</ul>
</p>
<p>
<ul>
    <li>Wolfram verarbeitet alle geläufigen Problemstellungen seiner literarischen Epoche (vor allem Minne-Problematik, Aventiure-Forderungen, Geeignetsein zum Herrscher, religiöse Determiniertheit)
        – teilweise kritisch ironisierend, teilweise für seine Zeit neuartig
        zuspitzend; dem Roman kommt damit exemplarische Bedeutung für die
        Themenkomplexe der höfischen Literatur insgesamt zu.</li>
</ul>
</p>
<p>
<ul>
    <li>Der Autor verfolgt parallel zum Hauptgeschehen um Parzival eine
        Vielzahl von weiteren Handlungssträngen. In immer neuen „Würfelwürfen“ (<i>schanzen</i>, Parz. 2,13 – Metapher Wolframs im Prolog des <i>Parzival</i>
        in Bezug auf sein eigenes narratives Verfahren) spielt er die
        politischen, gesellschaftlichen und religiösen Probleme, vor die sich
        Parzival gestellt sieht, mit anderen Protagonisten durch und entfaltet
        die Romanhandlung so zu einer umfassenden Anthropologie.</li>
</ul>
</p>
<p>Wolfram selbst war sich bewusst, dass seine oft sprunghafte,
    bildreich assoziierende Erzählweise neu und ungewöhnlich war; er
    vergleicht sie mit dem „Hakenschlagen eines Hasen auf der Flucht vor
    Ignoranten“ (<i>tumben liuten</i>, Parz. 1,15 ff) und betont damit,
    wiederum gegenüber Gottfried, der dieselbe Metapher spöttisch abwertend
    verwendet, selbstbewusst seine auffällige sprachkünstlerische Formkraft
    und inhaltliche sowie thematische Phantasie. Auffällig und ungewöhnlich
    für einen mittelalterlichen Autor ist das souveräne Neuarrangement des
    vorgefundenen Stoffes durch Wolfram. Die Bearbeitung geschieht gemäß
    eigenen literarischen Ideen und Intentionen.
</p>
<p>Der <i>Parzival</i> folgt einer Doppelromanstruktur mit einem
    langen Prolog. Nach den ersten beiden Büchern, die sich der
    Vorgeschichte der Haupthandlung widmen, also den Abenteuern von
    Gahmuret, Parzivals Vater, beginnt Wolfram von der Kindheit seines
    Protagonisten zu erzählen. Es folgt später der Wechsel zur
    Gawan-Handlung, die durch den Besuch Parzivals beim Einsiedler
    Trevrizent unterbrochen und anschließend wieder aufgenommen wird. Der
    Inhalt der beiden letzten Bücher ist Parzival gewidmet.
</p>

<div>
    <h4><span></span>Wolframs <i>Parzival</i> und Chrétiens <i>Perceval</i></h4>
</div>
<p>Hauptquelle des <i>Parzival</i> ist der unvollendete Versroman <i>Perceval le Gallois ou le conte du Graal/Li contes del Graal</i> von Chrétien de Troyes,
    entstanden um 1180 und 1190. Wolfram selbst distanziert sich im Epilog
    von Chrétien und nennt mehrfach das Werk eines „Kyot“ als Vorlage, das
    er mit einer abenteuerlichen Entstehungsgeschichte versieht. Da aber ein
    solcher „Kyot“ außerhalb von Wolframs Dichtung nicht identifiziert
    werden konnte, werden diese Angaben in der Forschung als Quellenfiktion
    und literarische Koketterie des Autors eingeordnet.
</p>
<p>Die Handlung des <i>Parzival</i> ist gegenüber der Vorlage
    umfangreich erweitert, insbesondere durch die Rahmung mit der
    einleitenden Vorgeschichte um Parzivals Vater Gahmuret und den abschließenden Ereignissen im Zusammentreffen Parzivals mit seinem Halbbruder Feirefiz.
    Die Einbettung in die Familiengeschichte dient – über die pure Lust am
    Fabulieren hinaus – der verstärkten Kausalmotivation der Handlung.
    Wolfram kommt auf fast 24.900 Verse gegenüber den 9.432 Versen bei
    Chrétien.
</p>
<p>In jenen Passagen, in denen Wolfram Chrétien inhaltlich folgt
    (Buch III bis Buch XIII), geht er wesentlich freier und selbstbewusster
    an die Nacherzählung als andere zeitgenössische Autoren (etwa Hartmann von Aue, dessen Artus-Romane Erec und Iwein
    Bearbeitungen von Chrétiens Romanen sind). Der Textumfang der Vorlage
    erfuhr eine Verdoppelung auf etwa 18.000 Verse, weil Wolfram seine
    Protagonisten wesentlich ausführlicher ethische und religiöse
    Fragestellungen reflektieren lässt und sich selbst als reflektierender
    Erzähler zu Vorgängen der fiktiven Handlung äußert. Er bindet die
    Figuren in ein Netz von Verwandtschaftsbeziehungen ein und weist ihnen Namen zu.
</p>
<p>Siehe auch die kymrische Sage <i>Peredur fab Efrawg</i>
    („Peredur, Sohn des Efrawg“), die ebenfalls dieses Thema behandelt. Die
    wechselseitige Beeinflussung konnte noch nicht restlos geklärt werden.</p>
<h4>Handlung – Überblick</h4>
<p>Parzivals Erziehung zum Ritter
    und seine Suche nach dem Gral ist zwar –&nbsp;wie der Erzähler mehrfach
    betont&nbsp;– Hauptthema der Handlung, fast gleichwertig aber verfolgt
    Wolfram kontrastierend die Ritterfahrt Gawans.
    Während Gawan durchgängig als der geradezu vollkommene Ritter auftritt
    und sich in zahlreichen Abenteuern immer erfolgreich bewährt, die
    Schuldigen an Missständen der Weltordnung zur Verantwortung zu ziehen
    und diese Ordnung zu restituieren, durchlebt Parzival neben Abenteuern
    auch extreme persönliche Konfliktsituationen und wird –&nbsp;aus Unkenntnis
    oder aufgrund von Fehlinterpretationen von Aussagen und Situationen&nbsp;–
    immer wieder selbst schuldig.
    Doch gerade er, der über lange Jahre hinweg die Folgen seines
    Fehlverhaltens ertragen muss, erlangt am Ende die Gralsherrschaft.
    Das Epos endet mit einem Ausblick auf die Geschichte von Parzivals Sohn Loherangrin (vgl. Wagners Lohengrin).
</p>
<p>Der folgende Überblick orientiert sich mit der Einteilung des Textes in sogenannte ‚Bücher‘ am etablierten Ordnungsprinzip Karl Lachmanns, des ersten ‚kritischen‘ Herausgebers des <i>Parzival</i>, auf dessen – mittlerweile allerdings überarbeitete – Edition die Forschung auch heute noch angewiesen ist.
</p>
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

login();
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

$id = createlongpage("Ihr erster Lehrtext (ohne KI-Unterstützung)", ["noAI"], $GLOBALS['longpagetext1'], 3, [
    "op" => "&",
    "c" => [
        ["type" => "profile", "sf" => "idnumber", "op" => "isequalto", "v" => $USER->idnumber],
        ["type" => "completion", "cm" => SECONDSTEP_CM_ID, "e" => 1],
        ["type" => "completion", "cm" => $id, "e" => 1],
        ["type" => "completion", "cm" => THIRDSTEP_CM_ID, "e" => 0]
    ],
    "showc" => [false, false, false, false]
]);

createlongpage("Ihr zweiter Lehrtext (mit KI-Unterstützung)", ["AI"], $GLOBALS['longpagetext2'], 3, [
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