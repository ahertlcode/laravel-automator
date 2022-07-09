//AutoComplete JS library
function autocompletx(id,val,options,displaywin){
	opt = str_to_json(options);
	opt["criteria"] = val;
	$.ajax({
		url: "php-server/autocomplete.php", //specify the path to your php autocomplete file
		data: opt,
		method: "POST"
	}).done(function(response){
		str = "";
		if(response !=="" && response.length !== undefined){
			response.forEach(function(obj){
				col = opt['column_name'];
				str += '<li onclick="putvalue(&apos;'+id+'&apos;,&apos;'+obj[col]+'&apos;,&apos;'+displaywin+'&apos;);">'+obj[col]+'</li>';
			});
			if(str !== "") display(displaywin,str);
			else remove_div(displaywin);
		}else{
			remove_div(displaywin);
		}
	}).fail(function(err){
		console.log(err);
	});
}

function str_to_json(str){
	json_str = {};
	d_str = str.split(",");
	for(i=0; i<d_str.length; i++){
		i_str = d_str[i];
		l_str = i_str.split(":");
		json_str[l_str[0]] = l_str[1];
	}
	return json_str;
}

function putvalue(objid,valux,dwin){
	$("#"+objid).val(valux);
	remove_div(dwin);
}

function display(dwin,strObj){
	$("#"+dwin)
	.html(strObj)
	.css("display","block")
	.css("position","absolute")
	.css("margin-top","0px")
	.css("margin-left","2px")
	.css("width","220px")
	.css("height","auto")
	.css("padding","10px")
	.css("border","1px solid #ddd")
	.css("background-color","#fff")
	.css("color","#000");
	$("#"+dwin+" li")
	.css("list-style","none")
	.css("text-decoration","none")
	.css("cursor","pointer");
}

function remove_div(oid){
	$("#"+oid)
	.html("")
	.css("display","none")
	.css("position","absolute")
	.css("margin-top","0px")
	.css("margin-left","0px")
	.css("width","0px")
	.css("height","0px")
	.css("padding","none")
	.css("border","none");
}