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
 * Strings for component 'longpage', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_longpage
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['content'] = 'Longpage Inhalt';
$string['contentheader'] = 'Inhalt';
$string['createpage'] = 'Eine neue Longpage anlegen';
$string['displayoptions'] = 'Verfügbare Anzeigeoptionen';
$string['displayselect'] = 'Darstellung';
$string['displayselectexplain'] = 'Darstellungstyp wählen';
$string['indicator:cognitivedepth'] = 'Longpage Verarbeitungstiefe';
$string['indicator:cognitivedepth_help'] = 'Gibt die Verarbeitungtiefe für Lernende an';
$string['indicator:socialbreadth'] = 'Longpage social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Page resource.';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Aktive';
$string['legacyfilesdone'] = 'Abgeschlossen';
$string['loading'] = 'Wird geladen...';

$string['messageprovider:posts'] = 'Abonnierte Threads';

$string['messagesubjectpostcreated'] = 'Neuer Beitrag innerhalb eines abonnierten Threads';
$string['messagesubjectpostcreated_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen neuen Beitrag innerhalb eines von Ihnen abonnierten Threads verfasst: „{$a->shortcontent}“';
$string['messagefullpostcreated'] = '{$a->firstname} {$a->lastname} hat einen neuen Beitrag innerhalb eines von Ihnen abonnierten Threads verfasst:\n „{$a->content}“';
$string['messagehtmlpostcreated'] = '<p>{$a->firstname} {$a->lastname} hat <a href="{$a->contexturl}">einen neuen Beitrag</a> innerhalb eines von Ihnen abonnierten Threads verfasst:</p><p>„{$a->content}“</p>';
$string['messagesmallpostcreated'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagecontexturlnamepostcreated'] = 'Neuer Beitrag';

$string['messagesubjectpostdeleted'] = 'Beitrag innerhalb eines abonnierten Threads gelöscht';
$string['messagesubjectpostdeleted_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen Beitrag innerhalb eines von Ihnen abonnierten Threads gelöscht';
$string['messagefullpostdeleted'] = '{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads gelöscht:\n „{$a->content}“';
$string['messagehtmlpostdeleted'] = '<p>{$a->firstname} {$a->lastname} hat <a href="{$a->contexturl}">folgenden Beitrag</a> innerhalb eines von Ihnen abonnierten Threads gelöscht:</p><p>„{$a->content}“</p>';
$string['messagesmallpostdeleted'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagecontexturlnamepostdeleted'] = 'Thread, innerhalb dessen der Beitrag gelöscht wurde';

$string['messagesubjectpostupdated'] = 'Beitrag innerhalb eines abonnierten Threads wurde bearbeitet';
$string['messagesubjectpostupdated_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen Beitrag innerhalb eines von Ihnen abonnierten Threads bearbeitet';
$string['messagefullpostupdated'] = '{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads bearbeitet:\n„{$a->oldcontent}“\nNeuer Text: „{$a->content}“';
$string['messagehtmlpostupdated'] = '<p>{$a->firstname} {$a->lastname} hat <a href="{$a->contexturl}">folgenden Beitrag</a> innerhalb eines von Ihnen abonnierten Threads bearbeitet:</p><p>„{$a->oldcontent}“</p><p>Neuer Text:</p><p>„{$a->content}“</p>';
$string['messagesmallpostupdated'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagecontexturlnamepostupdated'] = 'Bearbeiteter Beitrag';

$string['modulename'] = 'Longpage';
$string['modulename_help'] = 'Longpage ermöglocht Lehrenden länge Tetxte in Form von HTML abzulegen.';

$string['modulename_link'] = 'mod/longpage/view';
$string['modulenameplural'] = 'Seiten';
$string['optionsheader'] = 'Darstellungsoptionen';
$string['page-mod-page-x'] = 'Jede Page/Longpage';
$string['longpage:addinstance'] = 'Neue Seite als Ressource hinzufügen';
$string['longpage:view'] = 'Seiteninhalt anzeigen';
$string['longpage:modannotations'] = 'Annotations editieren und löschen';
$string['pluginadministration'] = 'Longpage administrieren';
$string['pluginname'] = 'Longpage';
$string['popupheight'] = 'Pop-up Höhe (in Pixeln)';
$string['popupheightexplain'] = 'Gibt die Standardhöhe von Pop-up Fenstern an.';
$string['popupwidth'] = 'Pop-up Breite (in Pixeln)';
$string['popupwidthexplain'] = 'Gibt die Standardbreite von Pop-up Fenstern an.';
$string['printheading'] = 'Titel der Seite anzeigen';
$string['printheadingexplain'] = 'Seitennamen oberhalb des Inhalts anzeigen?';
$string['printintro'] = 'Seitenbeschreibung anzeigen?';
$string['printintroexplain'] = 'Seitenbeschreibung oberhalb des Inhalts anzeigen?';
$string['privacy:metadata'] = 'Longpage speichert auch personenbezogene Daten.';
$string['search:activity'] = 'Longpage';
$string['activatefunctionalities'] = 'Longpage-Funktionen (de-)aktivieren';
$string['activatefunctionalitiesexplain'] = 'Longpage-Funktionen für eine Longpage-Instanz (de-)aktivieren';
$string['showreadingcomprehension'] = 'Leseverständnis ermitteln und anzeigen';
$string['showreadingcomprehensionexplain'] = 'Aufgaben zur Ermittlung und Visualisierung des Leseverständnisses anzeigen';
$string['showreadingprogress'] = 'Lesefortschritt anzeigen';
$string['showreadingprogressexplain'] = 'Lesefortschrittsbalken anzeigen (Lesefortschritt wird immer geloggt!)';
$string['showsearch'] = 'Suche anzeigen';
$string['showsearchexplain'] = 'Suche in Seitenleiste anzeigen';
$string['showtableofcontents'] = 'Inhaltsverzeichnis anzeigen';
$string['showtableofcontentsexplain'] = 'Inhaltsverzeichnis in Seitenleiste anzeigen';
$string['showposts'] = 'Anmerkungen aktivieren';
$string['showpostsexplain'] = 'Funktionalität zum Erstellen und Lesen von Anmerkungen aktivieren';
$string['showhighlights'] = 'Markierungen aktivieren';
$string['showhighlightsexplain'] = 'Funktionalität zum Erstellen und Lesen von Markierungen aktivieren';
$string['showbookmarks'] = 'Lesezeichen aktivieren';
$string['showbookmarksexplain'] = 'Funktionalität zum Erstellen und Verwenden von Lesezeichen aktivieren';
$string['showeditquestionsnoai'] = 'Manuelle Fragenerstellung aktivieren';
$string['showeditquestionsnoai_desc'] = 'Wenn aktiviert, können Lehrende Fragen manuell erstellen und bearbeiten';
$string['showeditquestionsai'] = 'KI-gestützte Fragenerstellung aktivieren'; 
$string['showeditquestionsai_desc'] = 'Wenn aktiviert, können Lehrende KI-Funktionen zum Erstellen und Bearbeiten von Fragen nutzen';
