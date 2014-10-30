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