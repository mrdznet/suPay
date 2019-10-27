
function copyContent() {
        var u = navigator.userAgent;
        var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端

        // 动态创建 input 元素
        var text = document.createElement("input");
        // 获得需要复制的内容
        text.setAttribute("value", document.getElementById("textsOne").innerText);
        // 添加到 DOM 元素中
        document.body.appendChild(text);

        // var text = document.getElementById("urlcontent");
        if (isiOS) {//区分iPhone设备 
            text.setSelectionRange(0, text.value.length);
            if (document.execCommand('copy', false, null)) {
                 layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 2 //2秒后自动关闭
				  });
            } else {
            	 layer.open({
				    content: '复制失败，您使用的手机暂不支持复制，请升级软件.'
				    ,skin: 'msg'
				    ,time: 2 //2秒后自动关闭
				  });
               
            }
        } else {
            text.select();//选中文本
            document.execCommand("copy");//执行浏览器复制命令    
            layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 2 //2秒后自动关闭
				  });
        }
        document.body.removeChild(text);
    }
			function copyContentOne() {
        var u = navigator.userAgent;
        var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端

        // 动态创建 input 元素
        var text = document.createElement("input");
        // 获得需要复制的内容
        text.setAttribute("value", document.getElementById("textsTwo").innerText);
        // 添加到 DOM 元素中
        document.body.appendChild(text);

        // var text = document.getElementById("urlcontent");
        if (isiOS) {//区分iPhone设备 
            text.setSelectionRange(0, text.value.length);
            if (document.execCommand('copy', false, null)) {
                 layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
            } else {
            	 layer.open({
				    content: '复制失败，您使用的手机暂不支持复制，请升级软件.'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
                
            }
        } else {
            text.select();//选中文本
            document.execCommand("copy");//执行浏览器复制命令    
            layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
        }
        document.body.removeChild(text);
    }
			function copysTwo() {
        var u = navigator.userAgent;
        var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端

        // 动态创建 input 元素
        var text = document.createElement("input");
        // 获得需要复制的内容
        text.setAttribute("value", document.getElementById("textsThr").innerText);
        // 添加到 DOM 元素中
        document.body.appendChild(text);

        // var text = document.getElementById("urlcontent");
        if (isiOS) {//区分iPhone设备 
            text.setSelectionRange(0, text.value.length);
            if (document.execCommand('copy', false, null)) {
                  layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
            } else {
            	 layer.open({
				    content: '复制失败，您使用的手机暂不支持复制，请升级软件.'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
               
            }
        } else {
            text.select();//选中文本
            document.execCommand("copy");//执行浏览器复制命令    
            layer.open({
				    content: '复制成功'
				    ,skin: 'msg'
				    ,time: 1 //2秒后自动关闭
				  });
        }
        document.body.removeChild(text);
    }
function btns() {

    // //获取订单号
    //     var orderNo = document.getElementById("orderNo").value;
    // //获取存款姓名
    //     var player_name = document.getElementById("user").value;
    var users = document.getElementById("user").value.length;
    if (users == 0) {
        layer.open({
            content: '请输入存款人姓名'
            , skin: 'msg'
            , time: 1 //2秒后自动关闭
        });
    } else {

        layer.open({
            content: '转账成功秒到账'
            , skin: 'msg'
            , time: 1 //2秒后自动关闭
        });

        //执行打款人提交流程

        // $(document).ready(function(){
        //     var userNameOld  = "{$player_name}";
        //     if(userNameOld.length>0){
        //         $("#user").val(userNameOld);
        //     }
        // });
            var users= document.getElementById("user").value.length;
            var orderNo= document.getElementById("orderNo").value;
            var player_name= document.getElementById("user").value;
            var url = $("#commentForm").attr("action");
            // alert(url);
            $.ajax({
                dataType : 'json',
                type : 'POST',
                // url : "{:url('bank_pay/ajaxputname')}",
                url : url,
                async : true,
                data : {
                    "orderNo" : orderNo,
                    "player_name" : player_name,

                },
                success : function(res){
                    if(100 == res.code){
                        layer.open({
                            content: '提交成功，请放心充值！'
                            ,skin: 'msg'
                            ,time: 1 //2秒后自动关闭
                        });
                        // alert('提交成功，请放心充值！');
                    }else{
                        layer.open({
                            content: '提交失败！'+res.msg
                            ,skin: 'msg'
                            ,time: 1 //2秒后自动关闭
                        });
                        // alert('提交失败！'+res.msg)
                    }

                },
                error : function(XMLHttpRequest, textStatus, errorThrown) {
                    alert(XMLHttpRequest.status + "," + textStatus);
                }
            });
    }
}

		
	