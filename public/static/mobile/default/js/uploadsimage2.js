//--------------------处理上传包括兼容ios----------------------------//
var _imgNum2 = 0;
var _uplocalfunction2 = function(){}; //上传完毕调用的函数
var _listenSelector2 = '.uploadInput2' ; //监听选择器
var _delSelector2='.uploadFile';//删除图片选择器


//删除上传图片
$(_delSelector2).on('click','.closeImg',function(){

	var type = typeof($(this).data('type')) == undefined ? 'more' : $(this).data('type');
	console.log(type);
	if(type == 'one'){
		$(this).parent().remove();
	    fd2.delete("qrcodefile");
	    $('#upload_qrcode_box').css('visibility','');
	}else{
		$(this).parent().remove();
		fd2.delete("imgfile["+$(this).data('imgnum')+"]");
	}
})

 $(_listenSelector2).on('change',function(event){
 	uploadFile2(this,event)
 })

//安卓和HTML上传方式选择上传图片
function uploadFile2(_this,e){
	compress2(e, _uplocalfunction2)
}

//压缩方法
function compress2(event, callback) {
	if ( typeof (FileReader) === 'undefined') {
		console.log("当前浏览器内核不支持base64图标压缩");
		//调用上传方式  不压缩
	} else {
		try {
			var file = event.currentTarget.files[0];
            var file_type = file.type;
			if(!/image\/\w+/.test(file.type)){
                file_type = 'image/jpg';
			}
           
			var reader = new FileReader();
			reader.onload = function (e) {
			var image = $('<img/>');
			image.load(function () {
				console.log("开始压缩");
                var canvas = document.createElement('canvas');
                var width = this.width;
                var height = this.height;

                //如果图片大于80万像素，计算压缩比并将大小压至80万以下
                var ratio;
                if ((ratio = width * height / 800000) > 1) {
                    ratio = Math.sqrt(ratio);
                    width /= ratio;
                    height /= ratio;
                }
                canvas.width = width;
                canvas.height = height;

				var context = canvas.getContext('2d');
			    context.clearRect(0, 0, width, height);
				context.drawImage(this, 0, 0, width, height);
				var data = canvas.toDataURL(file_type);
					//压缩完成执行回调
			     	callback(data);
				});
				image.attr('src', e.target.result);
			};
			reader.readAsDataURL(file);
		} catch(e) {
			console.log("压缩失败!");
			//调用上传方式  不压缩
		}
	}
}

//ios上传图片
function AppReturnBase64Image(image) {
    if(typeof(_uplocalfunction2) == 'function'){
         _uplocalfunction2(image)
    }else{
        _alert('未定义方法')
    }
   
}