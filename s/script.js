var img_list = new Array();
var idx_list = 0;
var trefresh = 5;

function refreshImg(){
	var obj = img_list[idx_list];
	obj.href = $(obj).attr('data')+'?'+parseInt(Math.random()*100000);
	$(obj).find('img').attr('src',obj.href);
	if(++idx_list >= img_list.length) idx_list = 0;
	setTimeout("refreshImg()",1000*trefresh);
}

document.addEventListener("DOMContentLoaded", () => {
	//setTimeout("refreshImg();", 1000 * trefresh);
	Fancybox.bind("[data-fancybox]", {
		Hash: false,
		Thumbs: false,
		compact: false,
		wheel: "slide",
		closeButton: false,
		contentClick: "close",
		contentDblClick: "close",

		Toolbar: {
			display: { left: [], middle: [], right: [] },
		}	
	});
});
  
// $(document).ready(function() {
// 	$('a.fancy').each(function(){img_list.push(this)});
// 	setTimeout("refreshImg()",1000*trefresh);
// 	$("a.fancy").fancybox({ buttons: [], clickContent: "close", mobile: { dblclickContent: "close" } });
// });
