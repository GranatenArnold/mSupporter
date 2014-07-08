/**
 * 
 */

function tabelle() {

	this.sorting = 'course ASC';
	this.baseURL = '../../report/toverview/rest/router.php';
	this.URL = '/irgendwas';
	this.fields = []; // [[Spaltenname, Beschriftung]] Bsp.:
	// [['timecreated', 'Erstellt'], ['course', 'Kurs']]

	this.zeichnen = function(divID, fields) {
		google.load('visualization', '1', {
			packages : [ 'table' ]
		});
		google.setOnLoadCallback(drawTable);
		function drawTable() {
			
			/*
			$.each(fields, function(spalte, eigenschaften) {
				data.addRow('boolean', eigenschaften.bezeichnung);
			});
			*/
			$.ajax({
				fields : this.fields,
	            url: '../../report/toverview/rest/router.php/Dateien', //?jtSorting=' + jtParams.jtSorting,
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
        			$.each(fields, function(spalte, eigenschaften) {
        				data.addColumn(eigenschaften.typ, eigenschaften.bezeichnung);
        			});
        	    	result = result.Records;
        	    	var array = new Array();
        	    	$.each(result, function(id, felder) {
        	    		console.log(felder);
        	    		var subarray = new Array();
        	    		subarray.push(parseInt(felder.course));
        	    		subarray.push(felder.semester);
        	    		subarray.push(felder.fb);
        	    		subarray.push(felder.fullname);
        	    		
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
        	    		array.push(subarray);
        	    		//data.addRow(array);
        				//data.addRow('boolean', eigenschaften.bezeichnung);
        			});
        	    	data.addRows(array);
            	    var table = new google.visualization.Table(document
        					.getElementById(divID));
        			table.draw(data, {
        				showRowNumber : true
        			});
            	},
	            error: function () {
	                alert("Ajax-Call fehlgeschlagen!");
	            }
	        });
			
			
		}
		;
	};
};
