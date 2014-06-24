<?php

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');


defined('MOODLE_INTERNAL') || die;

// page parameters
global $CFG, $DB, $OUTPUT;
admin_externalpage_setup('reporttoverview', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('toverview', 'report_toverview'));

$loader = '<script src="//code.jquery.com/jquery-1.10.2.js"></script>
			<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
			<script type="text/javascript">
		    $(document).ready(function () {
    		
		    	//
		    	// Kurse: Supporter Tool einbinden
		    	//
    			$.get("../../report/toverview/interactive.html", function( inhalt ) {
    				$( "#content" ).html(inhalt);
				});
			});
			</script>
			<div id="content"></div>
		';
echo $loader;
echo $OUTPUT->footer();

