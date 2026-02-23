<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

// Get the longpage record
$page = $DB->get_record('longpage', ['id' => 1], '*', MUST_EXIST);

echo "Current values in database:\n";
echo "showreadingprogress: " . $page->showreadingprogress . " (type: " . gettype($page->showreadingprogress) . ")\n";
echo "showreadingtime: " . $page->showreadingtime . " (type: " . gettype($page->showreadingtime) . ")\n";
echo "showreadingcomprehension: " . $page->showreadingcomprehension . " (type: " . gettype($page->showreadingcomprehension) . ")\n";
echo "showsearch: " . $page->showsearch . " (type: " . gettype($page->showsearch) . ")\n";
echo "showtableofcontents: " . $page->showtableofcontents . " (type: " . gettype($page->showtableofcontents) . ")\n";
echo "showposts: " . $page->showposts . " (type: " . gettype($page->showposts) . ")\n";
echo "showhighlights: " . $page->showhighlights . " (type: " . gettype($page->showhighlights) . ")\n";
echo "showbookmarks: " . $page->showbookmarks . " (type: " . gettype($page->showbookmarks) . ")\n";
echo "showeditquestionsnoai: " . $page->showeditquestionsnoai . " (type: " . gettype($page->showeditquestionsnoai) . ")\n";
echo "showeditquestionsai: " . $page->showeditquestionsai . " (type: " . gettype($page->showeditquestionsai) . ")\n";

echo "\n!empty() test:\n";
echo "!empty({$page->showreadingtime}) = " . (!empty($page->showreadingtime) ? 'true' : 'false') . "\n";

// Test with 0
$test_zero = 0;
echo "!empty(0) = " . (!empty($test_zero) ? 'true' : 'false') . "\n";

// Test with "0" string
$test_zero_string = "0";
echo "!empty('0') = " . (!empty($test_zero_string) ? 'true' : 'false') . "\n";
