$(function(){ 
 $("#users th").each(function(){
 
  $(this).addClass("ui-state-default");
 
  });
 $("#users td").each(function(){
 
  $(this).addClass("ui-widget-content");
 
  });
 $("#users tr").hover(
     function()
     {
      $(this).children("td").addClass("ui-state-hover");
     },
     function()
     {
      $(this).children("td").removeClass("ui-state-hover");
     }
    );
 $("#users tr").click(function(){
   
   $(this).children("td").toggleClass("ui-state-highlight");
  });
             
  $( "#create-user" )
      .button()
      .click(function(){ showNew() }); 
});

function showNew(){
	$("div#dialog-fill").load("?dialog=new", function(data){
	$(this).dialog({
		autoOpen: true,
		title: "Add User",
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Create User": function(){
				doNew();
				$(this).dialog("close");
 },
			Cancel: function(){
				$(this).dialog("close");
			}
		}
         });
	});
}

function showEdit(id,event) {
	event.preventDefault();
	$("div#dialog-fill").load("?dialog=edit", function(data){
        $(this).dialog({
            autoOpen: true,
	    title: "Edit User #"+id,
            height: 300,
            width: 350,
            modal: true,
            buttons: {
		"Save Changes": function(){
			doEdit(id);
			$(this).dialog("close");
 },
		Cancel: function(){
			$(this).dialog("close");
		}
            }
	 });
        });
	$.get("?action=get&u="+id+"&ajax=1", function(data){
		$("input#name").val(data.name);
		$("input#email").val(data.email);
		$("input#groups").val(data.groups);
	},'json');
}

function showDelete(id,event){
	event.preventDefault();
	$("div#dialog-fill").load("?dialog=confirm", function(data){
	$(this).dialog({
		autoOpen: true,
		title: "Delete User?",
		height:250,
		width:350,
		modal: true,
		buttons: {
			"Yes": function() {
				doDelete(id);
				$(this).dialog("close");
 },
			"No": function(){
				$(this).dialog("close");
			}
		}
	  });
	});
}

function doNew(){
	var name=$("input#name").val();
	var email=$("input#email").val();
	var password=$("input#password").val();
	var groups="users,automated";

	$.post("?action=put&ajax=1",{
		name: name,
		email: email,
		groups: groups,
		password: password
}, function(data){
	$("#users tr:last").after("<tr id="+data.num+"><td>"+data.num+"</td><td>"+data.name+"</td><td>"+data.groups+"</td><td>"+data.actions+"</td></tr>");
},'json');
}

function doEdit(id)
{
	var name=$("input#name").val();
	var email=$("input#email").val();
	var groups=$("input#groups").val();

	$.post("?action=put&u="+id+"&ajax=1",{
		name: name,
		email: email,
		groups: groups
}, function(data){
	$("tr#"+id).hide('slow');
	$("tr#"+id).html("<td>"+data.num+"</td><td>"+data.name+"</td><td>"+data.groups+"</td><td>"+data.actions+"</td>");
	$("tr#"+id).show('slow');
},'json');
}

function doDelete(id) {
	$.post("?action=drop&u="+id+"&ajax=1", { send:"Yes"}, function(data){
 	  $("tr#"+id).remove();
	},'json');
}
