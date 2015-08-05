$(function(){

	$('.modal').on('shown.bs.modal', function (e) {
		init();
	});

	function init(){
		$('.modal .selected').each(function (){
			$(this).val($(this).data('value'));
		});
		$('.modal select').each(function (){
			$(this).val($(this).attr('value'));
		});

	/*
		$("select").select2({
	    });
	*/
		$('[data-select2-open]').click(function(){
	        $($(this).parent().parent().children('select')).select2('open');
	    });

		$('body').on('click', '[data-toggle=modal]',function (e) {
		    e.preventDefault();
		    var target=$(this).data('target');
		    var href = $(this).data("href");
		    if(href==null || href == '') {
		    	href = $(this).attr('href');
		    }
		    if(target ==null)return;
		    $(target+ " .modal-body").load(href, function(response,status,xhr) { 
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
		}
	$('.form_datetime').datepicker({
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 0,
		forceParse: 0,
        showMeridian: 1,
        format:'yyyy-mm-dd',
    });
	$('.form_date').datepicker({
        language:  'zh-CN',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 0,
		minView: 2,
		forceParse: 0,
		format:'yyyy-mm-dd',
    });
	$('.form_time').datepicker({
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
})