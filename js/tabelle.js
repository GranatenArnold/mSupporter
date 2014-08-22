/**
 * 
 */

var loader = "<img src='/report/toverview/pix/loader.gif' style='display: block; margin-left: auto; margin-right: auto; margin-top: 50px;'></src>";

function tabelle(divID, fields, url) {
			//console.log('/report/toverview/rest/router.php' + url);
			var loader = "<img src='/report/toverview/pix/loader.gif' style='display: block; margin-left: auto; margin-right: auto; margin-top: 50px;'></src>";
			$( "#"+divID ).html(loader);
			$.ajax({
				fields : fields,
	            url: '/report/toverview/rest/router.php' + url,
	            type: 'POST',
	            dataType: 'json',
    	        //data: postData,
        	    success: function (result) {
        	    	
        			var data = new google.visualization.DataTable();
        			
        			//Spalten, die auf jeden Fall vorkommen sollen
        			data.addColumn('number', 'ID');
        			data.addColumn('string', 'Semester');
        			data.addColumn('string', 'FB');
        			data.addColumn('string', 'Name');
        			data.addColumn('number', 'Teilnehmer');
        			$.each(fields, function(spalte, eigenschaften) {
        				data.addColumn(eigenschaften.typ, eigenschaften.bezeichnung);
        			});
        			data.addColumn('datetime', 'Erstellt');
        			data.addColumn('datetime', 'GeÃƒÂ¤ndert');
        	    	result = result.Records;
        	    	var array = new Array();
        	    	$.each(result, function(id, felder) {
        	    		//console.log(felder);
        	    		var subarray = new Array();
        	    		subarray.push(parseInt(felder.course));
        	    		subarray.push(felder.semester);
        	    		subarray.push(felder.fb);
        	    		subarray.push(felder.fullname);
        	    		subarray.push(parseInt(felder.participants));
        	    		$.each(fields, function(spalte, eigenschaften) {
        	    			if(eigenschaften.typ == "number") {
        	    				subarray.push(parseInt(felder[spalte]));
        	    			}
        	    			else {
        	    				subarray.push(felder[spalte]);
        	    			}
        	    			//array.push(felder[spalte]);
        					//data.addColumn('boolean', eigenschaften.bezeichnung);
        				});
        	    		subarray.push(new Date(felder.timecreated*1000));
        	    		subarray.push(new Date(felder.timemodified*1000));
        	    		array.push(subarray);
        	    		//data.addRow(array);
        				//data.addRow('boolean', eigenschaften.bezeichnung);
        			});
        	    	data.addRows(array);
            	    var table = new google.visualization.Table(document
        					.getElementById(divID));
        			table.draw(data, {
        				showRowNumber : false
        			});
        			
        			// Setup listener
					google.visualization.events.addListener(table, 'select', selectHandler);
			
					// Select Handler. Call the table's getSelection() method
					function selectHandler() {
						var selection = table.getSelection();
						$
							.ajax({
								url : "/report/toverview/html/detailed_course_view.html",
								context : document.body,
								success: function (inhalt) {
									var x = data.getValue(selection[0].row, 0);
									var find = "#ID#";
									var regex = new RegExp(find, 'g');
									//console.log(regex);
									inhalt = inhalt.replace(regex, x);
									//console.log(inhalt);
									$( "#getCourse").html(inhalt);
									$("#tabs").tabs({active: 0});
								}
							});
						}
					
        			
            	},
	            error: function (data) {
	            	console.log(data);
	                alert("Ajax-Call fehlgeschlagen!");
	            }
	        });
};
