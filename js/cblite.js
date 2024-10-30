function cblite_select_image(im)
{
	im = jQuery(im);
	jQuery('.cblite-thumb').css('opacity',0.5);
	im.css('opacity',1);
	jQuery('.cblite-main').attr('src',im.attr('src').replace('/75_','/300_'));
}
function cblite_toggle_text_node(a)
{
	var $a = jQuery(a);
	$a.prev().is(':visible') ? $a.html('&raquo;') : $a.html('&laquo;');	
	$a.prev().fadeToggle();
}


jQuery(document).ready(function(){
	jQuery(".cblite-tree").treeview({collapsed:true});
});