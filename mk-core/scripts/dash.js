$(function(){
 if (!readCookie('ss')){ //Detects javascript and cookie support need for dashboard
    createCookie('ss','partial',365);
    window.location.reload();
 }
 else if (readCookie('ss') == 'partial'){
    if ($(".sidebar").css('display')){
        createCookie('ss','full',365);
        window.location.reload();
    }
 }

 $("button.answer#false").click(function(event){
  event.preventDefault();
  history.back();
 });
 $("button.linkbrowse").attr("data-toggle",'modal').attr("data-target",'#modal').click(function(event){
    event.preventDefault();
    var caller=event.currentTarget.id;

    $("#modal .modal-title").html("Link Browser...");
    $("#modal .modal-body").load("?section=content&action=gethref&ajax=1",function(){
     $(this).on('click',"div.selectable",function(){
      var location=$(this).find("a#location").attr('href');
      $("input#mediabox-"+caller).val(location);
     });
     $("div.page").attr("data-dismiss",'modal');
    });
  });
  
  $(".page div.actions").find("span").click(function(){
   var location=$(this).parent().find("a#location").attr('href');
   var action=$(this).attr('id');
   if (action != 'view')
   {
    window.location=location+"action="+action;
   }
   else
   {
    location=location.slice(0,-1);
    window.location=location;
   }
  });
             
  $( "#add-addin" )
      .button()
      .click(function(){ showAdd() }); 
     
  $("button#sidebarOpen").button({
   icons: {
    primary: "ui-icon-gear"
   },
   text:false
  });
  $("button#sidebarLogin").button({
   icons: {
    primary: "ui-icon-person"
   },
   text:false
  });
});

function toggleSidebar()
{
 $("button#sidebarClose").button({
  icons: {
   primary: "ui-icon-closethick"
  },
  text: false
 });
 $("div#overlay").toggle('fade')
 $("div.sidebar").toggle('slide');
}

function serializeInputs(key)
{
 var serial=$("#"+key+" input, #"+key+" select, #"+key+" textarea").serialize();

 $("input[name="+key+"]").val(serial);
}

function changeServerInputs() 
{
 switch ($("select#email_mta").val())
 {
  case "smtp":
  $("ul#email_server").html("<li><label for=\"email_server_host\">Host:</label> <input onkeyup=\"serializeInputs('email_server')\" id=\"email_server_host\" name=\"host\" type=\"text\" value=\"localhost\"></li>\n<li><label for=\"email_server_port\">Port:</label> <input onkeyup=\"serializeInputs('email_server')\" id=\"email_server_port\" name=\"host\" type=\"text\" placeholder=\"49\"></li>\n<li><label for=\"email_server_auth\">Use Authentication</label> <input onchange=\"serializeInputs('email_server')\" id=\"email_server_auth\" name=\"auth\" type=\"checkbox\" value=\"1\"></li>\n<li><label for=\"email_server_security\">Security:</label> <select onchange=\"serializeInputs('email_server')\" id=\"email_server_security\" name=\"security\">\n<option value=\"0\">None</option>\n<option value=\"tls\">TLS</option>\n<option value=\"ssl\">SSL</option>\n</select></li>\n<li><label for=\"email_server_user\">User:</label> <input onkeyup=\"serializeInputs('email_server')\" id=\"email_server_user\" name=\"user\" type=\"text\"></li>\n<li><label for=\"email_server_password\">Password:</label> <input onkeyup=\"serializeInputs('email_server')\" id=\"email_server_password\" name=\"password\" type=\"password\"></li>");
  break;
  case "phpmail":
  case "sendmail":
  default:
  $("ul#email_server").html("<li><label for=\"email_server_host\">Host:</label> <input onkeyup=\"serializeInputs('email_server')\" id=\"email_server_host\" name=\"host\" type=\"text\" value=\"localhost\"></li>");
 }

 serializeInputs('email_server');
}

function toggleInputState(p,q)
{
    if (p.is(':checked'))
    {
        $("input"+q).removeAttr("disabled");
    }
    else
    {
        $("input"+q).attr('disabled','disabled');
    }
}

function openAJAXModal(url,title)
{
 $("div#modal").load(url).dialog({
  modal:true,
  title: title,
  buttons: {
   "Next":function(){
    $("form#UserForm").submit();
   }
  }
 });
}

function iFetch(e,field){
    if (e.keyCode == 13){
        var filename=field.value;
        $.get("?section=content&action=fetch&uri="+ filename +"&ajax=1",function(data){
            $("div#FileInfo").append(data);
        });
    }
    else{
        //alert(e.keyCode);
    }
}

function iUpload(field,pkg){
    var re_pkg=/\.apkg|\.zip/i;
    var filename=field.value;

    /*Make sure the proper type is uploaded if we expect a package*/
    if (pkg == true && filename.search(re_pkg) == -1){
      alert("File must be either a MomoKO .apkg or a .zip file!");
      field.form.reset();
      return false;
    }

    field.form.submit();
    $("#file span#msg").remove();
    $("#file").append("<span id=\"msg\">Uploading...</span>");
    field.disabled=true;
}

function showAdd(){
	$("div#dialog-fill").load("?ajax=1&section=addin&action=new", function(data){
	$(this).dialog({
		autoOpen: true,
		title: "Add Addin",
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			Go: function(){
				doAdd();
				$(this).dialog("close");
 },
			Cancel: function(){
				$(this).dialog("close");
			}
		}
         });
	});
}

function showRemove(id,event){
	event.preventDefault();
	$("div#dialog-fill").load("?ajax=1&section=addin&action=delete&num="+id, function(data){
	$(this).dialog({
		autoOpen: true,
		title: "Remove Addin?",
		height:250,
		width:350,
		modal: true,
		buttons: {
			"Yes": function() {
				doRemove(id);
				$(this).dialog("close");
 },
			"No": function(){
				$(this).dialog("close");
			}
		}
	  });
	});
}

function doAdd(){
	var archive=$("input#addin-temp").val();
	var type=$("input#addin-type").val();
	var dir=$("input#addin-dir").val();
	var shortname=$("input#addin-shortname").val();
	var longname=$("input#addin-longname").val();
	var description=$("input#addin-description").val();

	$.post("?ajax=1&section=addin&action=new", { archive: archive,
	  enabled: true,
	  type: type,
	  dir: dir,
	  shortname: shortname,
	  longname: longname,
	  description: description
	}, function(data){
	    if (data.code == 200){
	     $("table#addins").append("<tr id=\""+data.num+"\">\n<td class=\"ui-widget-content\">"+data.info.shortname+"</td><td class=\"ui-widget-content\">"+data.info.longname+"</td><td class=\"ui-widget-content\">"+data.info.type+"</td><td class=\"ui-widget-content\"><a class=\"ui-icon ui-icon-trash\" style=\"display:inline-block\" onclick=\"showRemove('"+data.num+"',event)\" title=\"Delete\" href=\"javascript:void()\"></a></td>\n</tr>");
            $(".row-select tr").on("mouseenter", function(){
                $(this).children("td").addClass("ui-state-hover");
            });
            $(".row-select tr").on("mouseleave",function(){
                $(this).children("td").removeClass("ui-state-hover");
            });
            $(".row-select tr").on('click',function(){
                $(this).children("td").toggleClass("ui-state-highlight");
            });
	    }else{
         console.debug(data);
	     $("table#addins").append("<tr class=\"error\" id=\"newError\">\n<td colspan=\"4\">"+data.msg+"</td>>\n</tr>");
	    }
	},'json');	
}

function doRemove(id) {
	$.post("?ajax=1&section=addin&action=delete&num="+id, { confirm:"Yes"}, function(data){
 	   if (data.code == 200){
	    $("tr#"+id).remove();
 	   }else{
        console.debug(data);
	    $("tr#"+id).html("<td class=\"error\" colspan=\"4\">Error! "+data.msg+"</td>");
 	   }
	},'json');
}

/* Simple Cookie functions */
function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}
