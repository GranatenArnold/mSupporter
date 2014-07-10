<?php

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');


defined('MOODLE_INTERNAL') || die;

// page parameters
global $OUTPUT;
//admin_externalpage_setup('reporttoverview', '', null, '', array('pagelayout'=>'report'));


$context = context_system::instance();
require_capability('report/toverview:view', $context);

echo '<script type="text/javascript">
<!--
window.location = "/report/toverview/html/interactive.html";
//–>
</script>';

/*
$context = get_context_instance(CONTEXT_SYSTEM);
echo "<pre>".print_r($context, true)."</pre>";
has_capability($capability, $context);
 * 
/*

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('toverview', 'report_toverview'));

$loader = '<script src="//code.jquery.com/jquery-1.10.2.js"></script>
			<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
			<script type="text/javascript">
		    $(document).ready(function () {
    		
		    	//
		    	// Kurse: Supporter Tool einbinden
		    	//
    			$.get("../../report/toverview/html/interactive.html", function( inhalt ) {
    				$( "#content" ).html(inhalt);
				});
			});
			</script>
			<div id="content"></div>
		';
echo $loader;
echo $OUTPUT->footer();

*/