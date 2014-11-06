$(function(){
 $("button").button();
 $("button.answer#false").click(function(event){
  event.preventDefault();
  history.back();
 });
 $("button#MapSave").click(function(){
  var raw=$("div#MapList").html()
  $("input#map").val(raw);
  $("form#MapForm").submit();
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
             
  $( "#add-addin" )
      .button()
      .click(function(){ showAdd() }); 
      
$("#MapList .subnav").hide().parent()
		.prepend("<span class='droparrow ui-icon ui-icon-carat-1-e'></span>");
	$("#MapList span.droparrow").click(function(event){
		event.stopPropagation();
		$(this).parent().find("ul.subnav").toggle("slow");
		if ($(this).hasClass('ui-icon-carat-1-e'))
		{
			$(this).removeClass('ui-icon-carat-1-e');
			$(this).addClass('ui-icon-carat-1-se');
		}
		else
		{
			$(this).removeClass('ui-icon-carat-1-se');
			$(this).addClass('ui-icon-carat-1-e');
		}
	});
	$( "#MapList ul" )
    		.sortable({
			placeholder: 'ui-state-highlight',
		})
    		.find( "li" )
        		.addClass( "ui-state-default ui-corner-all" )
			.click(function(event){
				event.stopPropagation();
				if ($(this).hasClass('ui-state-highlight')){
					$(this).removeClass('ui-state-highlight');
	 			}
				else{
					$('.ui-state-highlight').removeClass('ui-state-highlight');
					$(this).addClass('ui-state-highlight');
				}
			})
			.find("a")
				.click(function(event){ event.preventDefault(); });
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

function iUpload(field){
    var re_img=/\.apkg|\.zip/i;
    var filename=field.value;

    /*Check file type*/
    if (filename.search(re_img) == -1){
      alert("File must be either a MomoKO .apkg or a .zip file!");
      field.form.reset();
      return false;
    }

    field.form.submit();
    $("li#file").append("<span id=\"msg\"> Uploading...</span>");
    field.disabled=true;
}

function showAdd(){
	$("div#dialog-fill").load("?q=addin/manager/&action=add&ajax=1", function(data){
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

function showUpdate(id,event) {
	event.preventDefault();
	$("div#dialog-fill").load("?q=addin/manager/&action=update&num="+id+"&ajax=1", function(data){
        $(this).dialog({
            autoOpen: true,
	    title: "Update Addin",
            height: 300,
            width: 350,
            modal: true,
            buttons: {
		Apply: function(){
			doUpdate(id);
			$(this).dialog("close");
 },
		Cancel: function(){
			$(this).dialog("close");
		}
            }
	 });
        });
	/*$.get("?q=addin/manager/&action=get&u="+id+"&ajax=1", function(data){
		$("input#name").val(data.name);
		$("input#email").val(data.email);
		$("input#groups").val(data.groups);
	},'json');*/
}

function showRemove(id,event){
	event.preventDefault();
	$("div#dialog-fill").load("?q=addin/manager/&action=remove&num="+id+"&ajax=1", function(data){
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

function toggleEnabled(id,event){
  event.preventDefault();
  $("tr#"+id+" > td#enabled").load("?q=addin/manager/&action=enable&num="+id+"&ajax=1");
}

function doAdd(){
	var archive=$("input#addin").val();
	var incp=$("input#addin-incp").val();
	var enabled=$("input#addin-enabled").val();
	var dir=$("input#addin-dir").val();
	var shortname=$("input#addin-name").val();
	var longname=$("input#addin-title").val();
	var description=$("input#addin-description").val();

	$.post("?q=addin/manager/&action=add&ajax=1", { archive: archive,
	  incp: incp,
	  enabled: enabled,
	  dir: dir,
	  shortname: shortname,
	  longname: longname,
	  description: description
	}, function(data){
	  alert(data.longname+" Added!");
	},'json');	
}

function doUpdate(id)
{
	var dir=$("input#name").val();
	var shortname=$("input#addin-name").val();
	var longname=$("input#addin-title").val();
	var description=$("input#addin-description").val();

	$.post("?q=addin/manager/&action=update&num="+id+"&ajax=1",{
		dir: dir,
		shortname: shortname,
		longname: longname,
		description: description
}, function(data){
	$("tr#"+id).hide('slow');
	$("tr#"+id).html("<td>"+data.num+"</td><td>"+data.name+"</td><td>"+data.groups+"</td><td>"+data.actions+"</td>");
	$("tr#"+id).show('slow');
},'json');
}

function doRemove(id) {
	$.post("?q=addin/manager/&action=remove&num="+id+"&ajax=1", { send:"Yes"}, function(data){
 	  if (data.succeed){
	    $("tr#"+data.num).remove();
 	  }else{
	    alert(data.error);
 	  }
	},'json');
}