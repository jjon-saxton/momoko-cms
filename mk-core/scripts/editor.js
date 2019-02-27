function showKOBrowser(e)
{
  $("#modal .modal-title").html("Content Browser...");
  $("#modal .modal-body").load("mk-dash.php?section=content&action=gethref&ajax=1",function(){
            $("div.page").attr("data-dismiss",'modal');
  }).on('click',"div.selectable",function(){
   var location=$(this).find("a#location").attr('href');
   $("#linkInput").val(location);
  });
  $("#modal").modal('show');
}