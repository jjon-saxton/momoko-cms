<?php

switch ($_GET['action'])
{
 case 'build':
 switch ($_GET['dialog'])
 {
  case 'map':
  $nav=new MomokoNavigation($GLOBALS['USR'],"display=simple");
  $navlist=$nav->getModule('html');
  print <<<HTML
<style type="text/css">
#toolbar, #MapList {
        float:left;
	width: 100%;
    }
    #MapList ul { list-style-type: none; margin: 0; padding: 0; min-height: 10px }
    #MapList ul li { margin: 3px; padding: 0.4em; }
    #MapList ul .ui-icon { display: inline-block; }
</style>
<script language=javascript type="text/javascript">
$(function(){
	$("#MapList .subnav").parent()
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
			connectWith: 'ul',
			placeholder: 'ui-state-highlight',
			handle: ".handle" 
		})
    		.find( "li" )
        		.addClass( "ui-state-default ui-corner-all" )
        		.prepend( "<span class='handle ui-icon ui-icon-carat-2-n-s'></span>" )
			.click(function(event){
				event.stopPropagation();
				if ($(this).hasClass('ui-state-highlight')){
					$(this).removeClass('ui-state-highlight');
					$(this).removeAttr('id');
	 			}
				else{
					$('.ui-state-highlight').removeAttr('id');
					$('.ui-state-highlight').removeClass('ui-state-highlight');
					$(this).addClass('ui-state-highlight');
					$(this).attr('id','selected_item');
				}
			})
			.find("a")
				.click(function(event){ event.preventDefault(); });
	$("span#new").buttonset();
	$("button#np").button({
		text:false,
		icons:{
			primary:'ui-icon-document'
		}
	}).click(function(){
		$("#ItemEditDialog").load("//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/ajax.php?include=navhelper&action=build&dialog=page").dialog({
			title:"New Page",
			buttons:{
				"Save":function(){
					var href=$("input#uri").val();
					var title=$("input#title").val();
					$("ul#Map").append('<li type="page" class="page ui-state-default ui-corner-all"><span class="handle ui-icon ui-icon-carat-2-n-s"></span><a href="'+href+'">'+title+'</a></li>');
					$(this).dialog('close');
				},
				"Cancel":function(){
					$(this).dialog('close');
				}
			}
		});
	});
	$("button#nf").button({
		text:false,
		icons:{
			primary:'ui-icon-folder-open'
		}
	}).click(function(){
		$("#ItemEditDialog").load("//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/ajax.php?include=navhelper&action=build&dialog=folder").dialog({
			title:"New Folder",
			buttons:{
				"Save":function(){
					var href=$("input#uri").val();
					var title=$("input#title").val();
					$("ul#Map").append('<li type="site" class="site ui-state-default ui-corner-all"><span class="handle ui-icon ui-icon-carat-2-n-s"></span><span class="droparrow ui-icon ui-icon-carat-1-e"></span><a href="'+href+'">'+title+'</a><ul id="'+title+'" class="subnav"></ul></li>');
$("#MapList .subnav").parent()
	$("#MapList span.droparrow").click(function(event){
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
			connectWith: 'ul',
			placeholder: 'ui-state-highlight',
			handle: ".handle" 
		})
    		.find("a")
				.click(function(event){ event.preventDefault(); });
					$(this).dialog('close');
				},
				"Cancel":function(){
					$(this).dialog('close');
				}
			}
		});
	});
	$("button#edit").button({
		text:false,
		icons:{
			primary:'ui-icon-pencil'
		}
	}).click(function(){
		if ($("#selected_item").length < 1){
			alert ("Please Select an item below!");
		}
		else{
			var type=$("#selected_item").attr('type');
			$("#ItemEditDialog").load("//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/ajax.php?include=navhelper&action=build&dialog="+type,function(){
					var title=$("#selected_item a").html();
					var uri=$("#selected_item a").attr('href');
					$("input#uri").val(uri);
					$("input#title").val(title);
				}).dialog({
				autoLoad:false,
				title:"Edit "+type,
				buttons:{
					"Save":function(){
						var new_title=$("input#title").val();
						var new_uri=$("input#uri").val();
						$("#selected_item a").replaceWith("<a href='"+new_uri+"'>"+new_title+"</a>");
						$(this).dialog('close');
					},
					"Cancel":function(){
						$(this).dialog('close');
					}
				}
			});
		}
	});
	$("button#re").button({
		text:false,
		icons:{
			primary:'ui-icon-trash'
		}
	}).click(function(){
		if ($("#selected_item").length < 1){
			alert ("Please Select an item below!");
		}
		else{
			$("#ItemRemoveDialog").dialog({
				title: "Remove Item?",
				buttons:{
					"Yes":function(){
						$("#selected_item").remove();
						$(this).dialog('close');
					},
					"No":function(){
						$(this).dialog('close');
					}
				}
			});
		}
	});
});
</script>
<div id="toolbar" class="ui-widget-header ui-corner-all">
<span id="new">
<button id="np">New Page</button>
<button id="nf">New Section</button>
</span>
<button id="edit">Edit Item</button>
<button id="re">Remove Item</button>
</div>
<div id="MapList" class="box ui-widget-content">
<ul id="Map">
{$navlist}
</ul>
<div id="ItemEditDialog" style="display:none">
<p>Loading...</p>
</div>
<div id="ItemRemoveDialog" style="display:none">
<p>Are you sure you want to remove this item from the site map? If the selected item is a section, all sub-pages and sections will also be removed. This action will not be saved until you select 'save'.</p>
</div>
</div>
HTML;
  break;
  case 'page':
  echo <<<HTML
<style>
	label { display:block; }
</style>
<link rel="stylesheet" href="//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/css/elfinder.min.css" type="text/css" media="screen">
<script language="javascript" type="text/javascript" src="//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/js/elfinder.min.js"></script>
<script language="javascript" type="text/javascript">
$(function() {
    var opt = {      // Must change variable name
    url : "//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/php/connector.php",
    lang : 'en',
    defaultView : 'list',
    getFileCallback : function(url) {
	$("input#uri").val(url);
	$("div#browseDialog").dialog('close');
    },    // Must change the form field id
    }

    $("div#browseDialog").dialog({
	autoOpen:false,
	title:"File/Page Chooser",
	minWidth:'450',
	minHeight: '250'
    });

    $('button#browse').button({
	text:false,
	icons: { primary: 'ui-icon-folder-open' }
    }).click(function(event) {
 	event.preventDefault();
    	$('div#elfinder').elfinder(opt);
	$("div#browseDialog").dialog('open');
    })

});
</script><div id="PageForm">
<form>
<label for="uri">Location</label><input type=text required=required id="uri" name="location"><button id="browse">Browse...</button>
<label for="title">Title</label><input type=text required=required id="title" name="title">
<input type=hidden name="type" value="page">
</form>
</div>
<div id="browseDialog">
<div id="elfinder"></div>
</div>
HTML;
  break;
  case 'folder':
  case 'site':
  case 'section':
  echo <<<HTML
<style>
	label { display:block; }
</style>
<link rel="stylesheet" href="//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/css/elfinder.min.css" type="text/css" media="screen">
<script language="javascript" type="text/javascript" src="//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/js/elfinder.min.js"></script>
<script language="javascript" type="text/javascript">
$(function() {
    var opt = {      // Must change variable name
    url : "//{$GLOBALS['CFG']->domain}{$GLOBALS['CFG']->location}/assets/scripts/elfinder/php/connector.php",
    lang : 'en',
    defaultView : 'list',
    getFileCallback : function(url) {
	$("input#uri").val(url);
	$("div#browseDialog").dialog('close');
    },    // Must change the form field id
    }

    $("div#browseDialog").dialog({
	autoOpen:false,
	title:"File/Page Chooser",
	minWidth:'450',
	minHeight: '250'
    });

    $('button#browse').button({
	text:false,
	icons: { primary: 'ui-icon-folder-open' }
    }).click(function(event) {
 	event.preventDefault();
    	$('div#elfinder').elfinder(opt);
	$("div#browseDialog").dialog('open');
    })

});
</script>
<div id="FolderForm">
<form>
<label for="uri">Location</label><input type=text id="uri" name="location"><button id="browse">Browse...</button>
<label for="title">Title</label><input type=text required=required id="title" name="title">
<input type=hidden name="type" value="site">
</form>
</div>
<div id="browseDialog">
<div id="elfinder"></div>
</div>
HTML;
  break;
 }
 break;
 case 'post':
 include $GLOBALS['CFG']->basedir.'/assets/php/class.htmlParser.php';
 $parser=new htmlParser($_POST['raw_dom']);
 $html=$parser->toArray();
 $nav=new MomokoNavigation($GLOBALS['USR'],'display=none');
 $nav->HTMLArraytoMap($html);
 if ($nav->writeMap())
 {
  $return['processed']='suceeded';
 }
 else
 {
  $return['processed']='failed';
 }
 header("Content-type: application/json");
 echo json_encode($return);
 break;
 case 'set_map':
 include $GLOBALS['CFG']->basedir.'/assets/php/class.htmlParser.php';
 //set map stuff here
 header("Content-type: text/plain");
 $nav=new MomokoNavigation(null,null);
 $html=<<<HTML
<ul class="Map">
<li type=site class="site"><a href="site">site</a>
<ul class="subnav">
<li type=page class="page"><a href="test3">test3</a></li>
</ul></li>
<li type=page class="page"><a href="test" index=index>test</a></li>
<li type=page class="page"><a href="test2">test2</a></li>
</ul>
HTML;
 $parser=new htmlParser($html);
 $list=$parser->toArray();
 $nav=new MomokoNavigation(null,'display=none');
 $nav->HTMLArraytoMap($list);
 if ($nav->writeMap())
 {
  echo 'okay!';
 }
 else
 {
  echo 'not okay :(';
 }
 //echo $dom->saveXML();
 //TODO get the map to update based on setting ^^;
}

?>
