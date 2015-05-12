Event.observe(window, "load", function(){
	$$("#tabs .menu").each(function(ele){
		ele.onmouseover = function(){
			ele.addClassName("menu_hover");
		};
		
		ele.onmouseout = function(){
			ele.removeClassName("menu_hover");
		};
	});
	
	
	$$(".table_container table").each(function(table){
		var counter = 0;
		
		var rows = table.select("tr");
		rows.each(function(row,index){
			if(row.className.length > 0)return;
			if(counter % 2 == 0)row.addClassName("even");
			if(index == rows.length-1)row.addClassName("last_row");
			
			row.onmouseover = function(){this.addClassName("hover");};
			row.onmouseout = function(){this.removeClassName("hover");};

			var cells = row.select("th", "td");
			cells.each(function(node, index){
				if(index == cells.length-1) node.addClassName("last_col");
			});
			
			counter++;
		});

		
	});

});