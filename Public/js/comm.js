(function(e,t){"use strict";function y(e){if(e._done)return;e(),e._done=1}function b(e){var t=e.split("/"),n=t[t.length-1],r=n.indexOf("?");return r!=-1?n.substring(0,r):n}function w(e){var t;if(typeof e=="object")for(var n in e)e[n]&&(t={name:n,url:e[n]});else t={name:b(e),url:e};var r=l[t.name];return r&&r.url===t.url?r:(l[t.name]=t,t)}function E(e,t){if(!e)return;typeof e=="object"&&(e=[].slice.call(e));for(var n=0;n<e.length;n++)t.call(e,e[n],n)}function S(e){return Object.prototype.toString.call(e)=="[object Function]"}function x(e){e=e||l;var t;for(var n in e){if(e.hasOwnProperty(n)&&e[n].state!=g)return!1;t=!0}return t}function T(e){e.state=d,E(e.onpreload,function(e){e.call()})}function N(e,n){e.state===t&&(e.state=v,e.onpreload=[],k({src:e.url,type:"cache"},function(){T(e)}))}function C(e,t){if(e.state==g)return t&&t();if(e.state==m)return p.ready(e.name,t);if(e.state==v)return e.onpreload.push(function(){C(e,t)});e.state=m,k(e.url,function(){e.state=g,t&&t(),E(f[e.name],function(e){y(e)}),x()&&o&&E(f.ALL,function(e){y(e)})})}function k(e,t){var r=n.createElement("script");r.type="text/"+(e.type||"javascript"),r.src=e.src||e,r.async=!1,r.onreadystatechange=r.onload=function(){var e=r.readyState;!t.done&&(!e||/loaded|complete/.test(e))&&(t.done=!0,t())},(n.body||i).appendChild(r)}function L(){o||(o=!0,E(u,function(e){y(e)}))}var n=e.document,r=e.navigator,i=n.documentElement,s,o,u=[],a=[],f={},l={},c=n.createElement("script").async===!0||"MozAppearance"in n.documentElement.style||e.opera,h=e.head_conf&&e.head_conf.head||"head",p=e[h]=e[h]||function(){p.ready.apply(null,arguments)},d=1,v=2,m=3,g=4;c?p.js=function(){var e=arguments,t=e[e.length-1],n={};return S(t)||(t=null),E(e,function(r,i){r!=t&&(r=w(r),n[r.name]=r,C(r,t&&i==e.length-2?function(){x(n)&&y(t)}:null))}),p}:p.js=function(){var e=arguments,t=[].slice.call(e,1),n=t[0];return s?(n?(E(t,function(e){S(e)||N(w(e))}),C(w(e[0]),S(n)?n:function(){p.js.apply(null,t)})):C(w(e[0])),p):(a.push(function(){p.js.apply(null,e)}),p)},p.ready=function(e,t){if(e==n)return o?y(t):u.push(t),p;S(e)&&(t=e,e="ALL");if(typeof e!="string"||!S(t))return p;var r=l[e];if(r&&r.state==g||e=="ALL"&&x()&&o)return y(t),p;var i=f[e];return i?i.push(t):i=f[e]=[t],p},p.ready(n,function(){x()&&E(f.ALL,function(e){y(e)}),p.feature&&p.feature("domloaded",!0)});if(e.addEventListener)n.addEventListener("DOMContentLoaded",L,!1),e.addEventListener("load",L,!1);else if(e.attachEvent){n.attachEvent("onreadystatechange",function(){n.readyState==="complete"&&L()});var A=1;try{A=e.frameElement}catch(O){}!A&&i.doScroll&&function(){try{i.doScroll("left"),L()}catch(e){setTimeout(arguments.callee,1);return}}(),e.attachEvent("onload",L)}!n.readyState&&n.addEventListener&&(n.readyState="loading",n.addEventListener("DOMContentLoaded",handler=function(){n.removeEventListener("DOMContentLoaded",handler,!1),n.readyState="complete"},!1)),setTimeout(function(){s=!0,E(a,function(e){e()})},300)})(window)

$(function () {
   $('body').tooltip({
	    selector: '[rel="tooltip"]',
	    container:'body'
	});

	$('body').popover({
	    selector: '[rel="popover"]'
	});
    $(document).ajaxStart(function() {
	  loading();
	});

	$(document).ajaxStop(function() {
	  finish();
	});
	
	$('.dropdown .input-group li a').on('click',function(){
	    $(this).parents('.input-group').find("input[type=text]").val($(this).text());
	    $(this).parents('.input-group').find("input[type=hidden]").val($(this).attr('id'));
	  });
/*
	$('.content').on('click', '[data-toggle=modal]',function (e) {
		//if($(this).hasClass('btn-deleted') || $(this).hasClass('btn-edit'))return;
	    e.preventDefault();
	    var target=$(this).data('target');
	    if(target ==null)return;
	    $(  target+ " .modal-body").load($(this).data("href"), function(response,status,xhr) { 
	    	if (status == "success") {
		        $(target).modal("show");
		    }
	    });
	    return false;
	});
*/
	$('.modal').on('hidden.bs.modal', function (e) {
	  $(this).removeData();
	});

	$('.modal button[type=submit]').on('click',function(){
		var modal = $(this).closest('.modal');
		var addr,params;
		if(modal.find("div.tab-pane.active").size()==1){
			params=modal.find('div.tab-pane.active form').serialize();
			addr=modal.find('div.tab-pane.active form').attr('action');
		}
		else{
			params=modal.find('form').serialize();
			addr=modal.find('form').attr('action');
		}
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			dataType:'json',
			data:params,
			success: function(msg){
				alert(msg.msg); 
				if(msg.status=='1'){
					modal.modal('hide');
					refresh_list();
				}
				else{

				}
			}
		});
	});

	$('form.ajax button[type=submit]').on('click',function(){
		var addr,params;
		params=$(this).parents('form').serialize();
		addr=$(this).parents('form').attr('action');
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			dataType:'html',
			data:params,
			success: function(msg){
				$('.table-content').html(msg);
			}
		}); 
		return false;
	});

	$('.form-ajax button[type=submit]').on('click',function(){
		var addr,params;
		params=$(this).parents('form').serialize();
		addr=$(this).parents('form').attr('action');
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			dataType:'html',
			data:params,
			success: function(msg){
				alert(msg.msg);
			}
		}); 
		return false;
	});

init();

$('.modal').on('shown.bs.modal', function (e) {
	init();
});



    /*============================================================================
	*
	*Pretty date
	*
	*=============================================================================
	*/
	var n = $(".prettydate").length;
	if(n>0){
		$.prettyDate.messages = {
			now: "刚刚",
			minute: "1分钟前",
			minutes: $.prettyDate.template("{0}分钟前"),
			hour: "1小时前",
			hours: $.prettyDate.template("{0}小时前"),
			yesterday: "昨天",
			days: $.prettyDate.template("{0}天前"),
			weeks: $.prettyDate.template("{0}星期前"),
			months: $.prettyDate.template("{0}个月前"),
			years: $.prettyDate.template("{0}年前")
		};
		$(".prettydate").prettyDate();
	}

	/*============================================================================
	*
	*Toolbar
	*
	*=============================================================================
	*/

	$('.content').on('click','.select-all',function(){
		checked = $(this).is(':checked');
		target = $(this).data('target');
		if(target){
    		checkbox_list = $("#"+target+" :checkbox");
    	}
    	else{
    		checkbox_list = $(this).closest('thead').siblings('tbody').find(':checkbox');
		}
		if(checked){
			checkbox_list.prop('checked','checked');
		}
		else{
			checkbox_list.removeAttr('checked');
		}
	});
	
	$(".content").on('click','.select-invert',function(){
	    var checkbox=$("#"+$(this).data('target')+" :checkbox");
	     for(var i=0;i<checkbox.length;i++){
	         checkbox[i].checked=!checkbox[i].checked;
	     }
	 });

	 $('.content').on('click','select-cancel',function(){
	 	$("#"+$(this).data('target')+" :checkbox").removeAttr("checked");
	 });  

	
	$('.content').on('click','.table-toolbar .btn-task,.btn-tool, .table-operate-btn .btn-edit,.table-operate-btn .btn-view,.table-operate-btn .btn-add',function(){
		$($(this).data('target')+' #title').text($(this).data('title'));
	});

	$('.content').on('click','.table-toolbar .btn-delete',function(){
		var params=getChecked();
		if(!params){
			alert('请选中要操作的行。');
			return false;
		}
		var addr=$(this).data('remote');
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			dataType:'json',
			data:{id:params},
			success: function(msg){
				alert(msg.msg);
				if(msg.status==1)
					remove_row(); 
					refresh_list();
			}
		});
		return false;
	});

	$('.content').on('click','.task_i_doit_now',function(){
		var addr=$(this).data('href');
		if(!addr)return false;
		$.ajax({
			url:addr,
			type:'get',
			cache : false,
			dataType:'json',
			success: function(msg){
				alert(msg.msg);
				if(msg.status=='1'){

					refresh_list();
				}
			}
		});
		$(this).children('div').toggleClass('s',true);
		$(this).data('href','');
		if($(this).hasClass('ido')){
			
		}
		return false;
	});

	$('.content').on('click','.btn-op,.btn-status a',function(){
		var addr=$(this).data('href');
		var id = $(this).data('value');
		if(!addr)return false;
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			data:{id:id},
			dataType:'json',
			success: function(msg){
				alert(msg.msg);
				if(msg.status=='1'){
					refresh_list();
				}
			}
		});
		return false;
	});
	$('.content').on('click','.table-operate-btn .btn-delete',function(){
		var addr=$(this).data('href');
		var params = $(this).data('value');
		if(!addr)return false;
		$.ajax({
			url:addr,
			type:'post',
			cache : false,
			data:{id:params},
			dataType:'json',
			success: function(msg){
				alert(msg.msg);
				if(msg.status=='1'){
					
					refresh_list();
				}
			}
		});
		return false;
	});
	
	$('.table-toolbar .btn-edit').on('click',function(e){
		e.preventDefault();
		var n=$('.content table input:checked').length;
		if(!n){
			alert('请选中要操作的行');
			return false;
		}
		else{
			if(n>1){
				alert('One row limit to operate at a time.');
				return false;
			}
		}
		if(n!=1){
			return false;
		}

		var n=$("#data-table input:checked").attr('id');
		var name=$("#data-table input:checked").data('id');
		var href=$(this).data("remote");
	    var target=$(this).data("target");
	     $( target +" .modal-body").html('');
	    $( target +" .modal-body").load(href+'?'+name+'='+n, function() { 
	         $(target).modal("show"); 
	    });
	});
	alerts=$('#alert');
});

$('#alert').on('close.bs.alert', function () {
  $(this).hide();
  return false;
})

function init(){
	$('.selected').each(function (){
		$(this).val($(this).data('value'));
	});

/*
	$("select").select2({
    });
*/
	$('[data-select2-open]').click(function(){
        $($(this).parent().parent().children('select')).select2('open');
    });

	$('.content').on('click', '[data-toggle=modal]',function (e) {
	    e.preventDefault();
	    var target=$(this).data('target');
	    if(target ==null)return;
	    $(target+ " .modal-body").load($(this).data("href"), function(response,status,xhr) { 
	    	if (status == "success") {
		        $(target).modal("show");
		    }
	    });
	    return false;
	});

	$('.dropdown .input-group li a').on('click',function(){
	    $(this).parents('.input-group').find("input[type=text]").val($(this).text());
	    $(this).parents('.input-group').find("input[type=hidden]").val($(this).attr('id'));
	  });

	$('.form_datetime').datetimepicker({
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 2,
		forceParse: 0,
        showMeridian: 1
    });
	$('.form_date').datetimepicker({
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 2,
		minView: 2,
		forceParse: 0
    });
	$('.form_time').datetimepicker({
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 1,
		minView: 0,
		maxView: 1,
		forceParse: 0
    });

}

function refresh_page(){
	var href=$('.next').attr('href');
    var next=$('#next-page');
    if (typeof href !== 'undefined' && href !== false) {
        next.attr('href',href);
        next.removeAttr('disabled');
    }
    else{
        $('#next-page').removeAttr('href');
        $('#next-page').attr('disabled','disabled');
    }

    href=$('.prev').attr('href');
    var prev=$('#prev-page');
    if (typeof href !== 'undefined' && href !== false) {
        prev.attr('href',href);
        prev.removeAttr('disabled');
    }
    else{
        prev.removeAttr('href');
        prev.attr('disabled','disabled');
    }
}
function refresh_list(){
	var obj=$('#cur_page').length;
    if( obj== 0){
    	var link=$('#jump-btn').data('href');
		$('#jump_link').attr('href',link);
		$('#jump_link').click();
    }
    else{
      $('#cur_page').click();
    }
}
function clear_form_data(id){ 
	$(id+' form').each(function(index){
		$(id+' form')[index].reset();
	});
}
function refer_quote(source,data){
	$('#'+source+" input").each(function(){
		var field = $(this).data("target");
		var result=data[field];
		$(this).val(data[field]);
	});
	$('#modal-refer').modal('hide');
}
function remove_row(){
	$(".data-table input:checked").parent().parent().parent().html('');
}

function getChecked(){
	var list=$("#data-table input:checked").map(function() {
		return $(this).attr('id');
	}).get().join(',');
	return list;
}

function setValue(obj){ 
    for(var p in obj){  
       if(typeof(obj[p])=="function"){        
        }else{      
            $("form #"+p).val(obj[p]);                 
        }        
    }
}

function get_list(addr,params,input_id,fun){
$.ajax({
	url:addr,
	type:'post',
	cache : false,
	dataType:'json',
	data:{id:params},	
	success: function(data){
		append_items(data,input_id);
		fun(data);
	}
});
}
function append_items(data,id){
	$(id).html('');
	var item=$(id);
	for ( var i = 0; i < data.length; i++) {  
        item.append("<li><a href='#' value='"+data[i]['id']+"'>"+ data[i]['name']+ "</a></li>");  
    }  
}
var alerts;
function alert(msg){
	var alert=$('#alert');
	alert.children('#msg').text(msg);
	alert.removeClass('hide');
	alert.fadeIn(500).delay(4000).fadeOut(1000);
}
function loading(){
$('.loading').removeClass('hide');

}
function finish(){
	$('.loading').addClass('hide');
}

