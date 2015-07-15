$(function(){
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