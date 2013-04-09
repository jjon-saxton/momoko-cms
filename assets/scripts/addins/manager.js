$(function(){ 
 $("#addins th").each(function(){
 
  $(this).addClass("ui-state-default");
 
  });
 $("#addins td").each(function(){
 
  $(this).addClass("ui-widget-content");
 
  });
 $("#addins tr").hover(
     function()
     {
      $(this).children("td").addClass("ui-state-hover");
     },
     function()
     {
      $(this).children("td").removeClass("ui-state-hover");
     }
    );
 $("#addins tr").click(function(){
   
   $(this).children("td").toggleClass("ui-state-highlight");
  });
             
  $( "#add-addin" )
      .button()
      .click(function(){ showAdd() }); 
});

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
	$("div#dialog-fill").load("?action=add&ajax=1", function(data){
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
	$("div#dialog-fill").load("?action=update&num="+id+"&ajax=1", function(data){
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
	/*$.get("?action=get&u="+id+"&ajax=1", function(data){
		$("input#name").val(data.name);
		$("input#email").val(data.email);
		$("input#groups").val(data.groups);
	},'json');*/
}

function showRemove(id,event){
	event.preventDefault();
	$("div#dialog-fill").load("?action=remove&num="+id+"&ajax=1", function(data){
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
  $("tr#"+id+" > td#enabled").load("?action=enable&num="+id+"&ajax=1");
}

function doAdd(){
	var archive=$("input#addin").val();
	var incp=$("input#addin-incp").val();
	var enabled=$("input#addin-enabled").val();
	var dir=$("input#addin-dir").val();
	var shortname=$("input#addin-name").val();
	var longname=$("input#addin-title").val();
	var description=$("input#addin-description").val();

	$.post("?action=add&ajax=1",{
		archive: archive,
		incp: incp,
		enabled: enabled,
		dir: dir,
		shortname: shortname,
		longname: longname,
		description: description
}, function(data){
	if (data.error){
	  alert (data.error);
	}
	else{
	  $("#addins tr:last").after("<tr id="+data.num+"><td>"+data.dir+"</td><td>"+data.incp+"</td><td>"+data.enabled+"</td><td>"+data.shortname+"</td><td>"+data.longname+"</td><td>"+data.description+"</td><td>&nbsp;<!-- actions --></td></tr>");
	}
},'json');
}

function doUpdate(id)
{
	var dir=$("input#name").val();
	var shortname=$("input#addin-name").val();
	var longname=$("input#addin-title").val();
	var description=$("input#addin-description").val();

	$.post("?action=update&num="+id+"&ajax=1",{
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
	$.post("?action=remove&num="+id+"&ajax=1", { send:"Yes"}, function(data){
 	  if (data.succeed){
	    $("tr#"+data.num).remove();
 	  }else{
	    alert(data.error);
 	  }
	},'json');
}
