/**
 * 
 */

function tabelle(divID, fields, url) {
			console.log('/report/toverview/rest/router.php' + url);
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
        			data.addColumn('datetime', 'Geändert');
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
            	},
	            error: function (data) {
	            	console.log(data);
	                alert("Ajax-Call fehlgeschlagen!");
	            }
	        });
};
