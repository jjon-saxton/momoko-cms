$(function(){
 $("button.answer#false").click(function(event){
  event.preventDefault();
  history.back();
 });
 $("button.linkbrowse").click(function(event){
    event.preventDefault();
    var caller=event.currentTarget.id;
    alert(caller);
	$("div#modal").load("?section=content&action=gethref&ajax=1",function(){
	 $("#vtabs").tabs().addClass('ui-tabs-vertical ui-helper-clearfix');
	 }).on('mouseenter',"div.selectable",function(){
		 $(this).addClass("ui-state-hover");
		 }).on('mouseleave',"div.selectable",function(){
			$(this).removeClass("ui-state-hover");
		 }).on('click',"div.selectable",function(){
		var location=$(this).find("a#location").attr('href');
		 $("input#mediabox-"+caller).val(location);
		 $("div#modal").dialog('close');
	 });
	$("div#modal").dialog({
		 height: 500,
		 width: 800,
		 modal: true,
		title: "Browse Site"
	});
 });
 $(".dashboard").each(function(){
  $(this).addClass("ui-widget");
  $(this).addClass("ui-widget-content");
 });
 $(".dashboard th").each(function(){
 
  $(this).addClass("ui-state-default");
 
  });
 $(".dashboard td").each(function(){
 
  $(this).addClass("ui-widget-content");
 
  });
 $(".row-select tr").hover(
     function()
     {
      $(this).children("td").addClass("ui-state-hover");
     },
     function()
     {
      $(this).children("td").removeClass("ui-state-hover");
     }
    );
 $(".row-select tr").click(function(){
   
   $(this).children("td").toggleClass("ui-state-highlight");
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
	    if (data.status == 200){
	     alert(data.longname+" Added!"); //TODO add or change effected row!
	    }else{
	     alert("Error! "+data.message);
	    }
	},'json');	
}

function doRemove(id) {
	$.post("?ajax=1&section=addin&action=delete&num="+id, { confirm:"Yes"}, function(data){
 	  if (data.status == 200){
	    $("tr#"+data.num).remove();
 	  }else{
	    alert("Error!" +data.message);
 	  }
	},'json');
}
