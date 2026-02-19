<?php
// This file is part of Moodle - http://moodle.org/
//
// Contains debug functionality specific to modedit context for TinyMCE issues

require_once('../../config.php');
require_login();

// Must be admin or teacher to access this debug tool
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/mod/longpage/debug_modedit_test.php');
$PAGE->set_context($context);
$PAGE->set_title('Longpage Modedit Debug Test');
$PAGE->set_heading('Longpage Modedit Debug Test');

echo $OUTPUT->header();
?>

<div style="font-family: Arial, sans-serif; margin: 20px;">
    <h2>🔧 Longpage Modedit Context Test</h2>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3>Current Status</h3>
        <ul>
            <li><strong>Current URL:</strong> <?php echo s($_SERVER['REQUEST_URI']); ?></li>
            <li><strong>Is Modedit Context:</strong> <?php echo strpos($_SERVER['REQUEST_URI'], 'modedit.php') !== false ? '✅ YES' : '❌ NO'; ?></li>
            <li><strong>Has Update Param:</strong> <?php echo strpos($_SERVER['REQUEST_URI'], 'update=') !== false ? '✅ YES' : '❌ NO'; ?></li>
            <li><strong>Editing Flag Defined:</strong> <?php echo defined('EDITING_LONGPAGE_MODULE') ? '✅ YES' : '❌ NO'; ?></li>
            <li><strong>On Debug Page:</strong> <?php echo strpos($_SERVER['REQUEST_URI'], 'debug_modedit_test.php') !== false ? '✅ YES (Expected to show NO above)' : '❌ NO'; ?></li>
        </ul>
        
        <?php if (strpos($_SERVER['REQUEST_URI'], 'debug_modedit_test.php') !== false): ?>
        <div style="background: #d1ecf1; padding: 10px; border-radius: 3px; margin-top: 10px;">
            <strong>Note:</strong> You are currently on the debug test page. The "Editing Flag Defined: NO" above is expected. 
            To test TinyMCE functionality, click one of the "Edit:" links below to go to the actual module edit page.
        </div>
        <?php endif; ?>
    </div>
    
    <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3>Test Links</h3>
        <p>Try these links to test the TinyMCE functionality:</p>
        <ul>
            <?php
            // Find a longpage module to test with
            $longpages = $DB->get_records_sql("
                SELECT cm.id, cm.course, l.name 
                FROM {course_modules} cm 
                JOIN {modules} m ON cm.module = m.id 
                JOIN {longpage} l ON cm.instance = l.id 
                WHERE m.name = 'longpage' 
                AND cm.visible = 1 
                LIMIT 5
            ");
            
            if (empty($longpages)) {
                echo '<li>❌ No longpage modules found for testing</li>';
            } else {
                foreach ($longpages as $lp) {
                    $editurl = new moodle_url('/course/modedit.php', [
                        'update' => $lp->id,
                        'return' => 1
                    ]);
                    echo '<li><a href="' . $editurl . '" target="_blank">Edit: ' . s($lp->name) . '</a></li>';
                }
            }
            ?>
        </ul>
    </div>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3>Debug Tools</h3>
        <p>Access these tools to diagnose TinyMCE issues:</p>
        <ul>
            <li><a href="debug_editor.php" target="_blank">General Editor Debug Tool</a></li>
            <li><a href="debug_modedit.html" target="_blank">Modedit Context Debug (Static)</a></li>
        </ul>
    </div>
    
    <div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3>Common Issues & Solutions</h3>
        <ul>
            <li><strong>"Standards mode" error:</strong> Document DOCTYPE issues</li>
            <li><strong>Editor not loading:</strong> JavaScript conflicts with Vue.js</li>
            <li><strong>Embedquestion conflicts:</strong> Atto plugin interference</li>
        </ul>
    </div>
</div>

<script>
console.log('Longpage modedit debug test loaded');
console.log('EDITING_LONGPAGE_MODULE flag:', window.EDITING_LONGPAGE_MODULE);
console.log('jQuery available:', !!window.jQuery);
console.log('Vue.js available:', !!window.Vue);
console.log('Document mode:', document.compatMode);
</script>

<?php
echo $OUTPUT->footer();
?>