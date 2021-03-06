//新增，控制增减评审详情
$(function(){
    $('#qa').on('change',function () {
        if($(this).val() == 'QA')
        {
            $('#qaAudit').show();
        }else{
            $('#qaAudit').hide();
        }
    });
    $('.add').live('click',function(){
        var tr = $(this).parent().parent().clone();
         tr.find('input[type="text"]').attr('value','');
         tr.find('select').attr('value','');
         tr.find('textarea').attr('value','');
        //return alert(tr.find('input[id="auditID1"]'));
        var num = $('.text-center').length;
        tr.find('input[name="auditID[]"]').attr('id','auditID'+num);
        tr.find('textarea[name="noDec[]"]').attr('id','noDec'+num);
        tr.find('select[name="noType[]"]').attr('id','noType'+num);
        tr.find('select[name="serious[]"]').attr('id','serious'+num);
        tr.find('textarea[name="cause[]"]').attr('id','cause'+num);
        tr.find('textarea[name="measures[]"]').attr('id','measures'+num);
        //追加新的tr元素
        $(this).parent().parent().after(tr);
    });
    $('.del').live('click',function(){
        if(($('.text-center').length) == 2){
            var tr = $(this).parent().parent().clone();
            tr.find('input[type="text"]').attr('value','');
            tr.find('select').attr('value','');
            tr.find('textarea').attr('value','');
            //追加新的tr元素
            $(this).parent().parent().after(tr);
        }
        //事件处理程序
        $(this).parent().parent().remove();
    });
    $('#submit').on('click',function () {
        if($('#qa').val() == 'QA'){
            var i = 1;
            var num = $('.text-center').length;
             //return alert(num);
            for(i=1;i<num;i++){
                if($('#auditID'+i).val() == ''){
                    alert('编号不能为空');
                }
                 if($('#noDec'+i).val() == ''){
                    alert('不符合描述不能为空');
                }
                 if($('#noType'+i).val() == ''){
                    alert('不符合类型不能为空');
                }
                 if($('#serious'+i).val() == ''){
                    alert('严重度不能为空');
                }
                 if($('#cause'+i).val() == ''){
                    alert('原因分析不能为空');
                }
                if($('#measures'+i).val() == ''){
                    alert('纠正预防措施不能为空');
                }
            }
        }
    })
});

//1923 针对测试类型的任务，设计任务描述模板
/* Set the assignedTos field. */
function setOwners(result)
{
    if(result == 'test')
    {
        $(document.getElementsByTagName("iframe")[0].contentWindow.document.body).html("<p>[测试范围]</p><p>[测试策略]</p><p>[测试验证]</p><p>[测试风险]</p><p>[参考资料]</p>");
    }
    else
    {
        $(document.getElementsByTagName("iframe")[0].contentWindow.document.body).html("");
    }

    if(result == 'affair')
    {
        $('#assignedTo').attr('multiple', 'multiple');
        $('#assignedTo').chosen('destroy');
        $('#assignedTo').chosen(defaultChosenOptions);
        $('#selectAllUser').removeClass('hidden');
    }
    else if($('#assignedTo').attr('multiple') == 'multiple')
    {
        $('#assignedTo').removeAttr('multiple');
        $('#assignedTo').chosen('destroy');
        $('#assignedTo').chosen(defaultChosenOptions);
        $('#selectAllUser').addClass('hidden');
    }

    if(result == 'script')
    {
        $('.scriptTask').show();
    }else{
        $('.scriptTask').hide();
    }
}

function copyStoryTitle()
{
    var storyTitle = $('#story option:selected').text();
    startPosition = storyTitle.indexOf(':') + 1;
    endPosition   = storyTitle.lastIndexOf('(');
    storyTitle = storyTitle.substr(startPosition, endPosition - startPosition);

    $('#name').attr('value', storyTitle);
    $('#estimate').val($('#storyEstimate').val());
    $('#desc').val($('#storyDesc').val());

    $('.pri-text span:first').removeClass().addClass('pri' + $('#storyPri').val()).text($('#storyPri').val());
    $('select#pri').val($('#storyPri').val());

    //创建任务点击同需求；任务描述不复制需求描述
    /*$(window.editor.desc.edit.doc).find('span.kindeditor-ph').remove();
    window.editor.desc.html($('#storyDesc').val());*/
}