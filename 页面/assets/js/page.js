(function (doc, win) {
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if(clientWidth > 750){
                clientWidth = 750;
            }
            if (!clientWidth) return;
            docEl.style.fontSize = 75 * (clientWidth / 375) + 'px';
        };

    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);

  var color = [
    {
      main: '#2ac5ff'
    },
    {
      main: '#5FB878'
    },
    {
      main: '#03152A'
    }
  ];

  var curMainColor = color[0].main;

  var theme = function () {
    var body = document.getElementsByTagName("body")[0];
    var style = document.createElement('style');
    style.setAttribute("type", "text/css");
    style.setAttribute("id", "themeColor");
    var styleStr =
      '.weui-btn_primary{background-image: linear-gradient(250deg, ' + curMainColor + ' 0%, ' + curMainColor + ' 100%), linear-gradient(' + curMainColor + ', ' + curMainColor + ');}' +
      '.weui-navbar__item.weui-bar__item--on{color: ' + curMainColor + '}' +
      '.weui-navbar__item.weui-bar__item--on:after{background-color: ' + curMainColor + '; box-shadow: none;}' +
      '.text-blue{ color: ' + curMainColor + '}' +
      '.inline-btn{ border-color: ' + curMainColor + '; background-color:#fff;}' +
      '.code_block .copybtn_box .inline-btn{color: ' + curMainColor + '}' +
      '.weui-dialog__btn{color: ' + curMainColor + '}' +
      '.my_qbtopbg,.page-hd_imgbg,.qbtopbg{ background-color: ' + curMainColor + '}' +
      '.qb_addbtn::after, .qb_addbtn::before{background-color: ' + curMainColor + '}' +
      '.bottom-tabbar a.active .label{ color: ' + curMainColor + '}' +
      '.weui-switch:checked, .weui-switch-cp__input:checked ~ .weui-switch-cp__box{ border-color: ' + curMainColor + '; background-color: ' + curMainColor + '}' +
      '.icon_cp_b{ color: ' + curMainColor + '}' +
      '.bottom-tabbar a.active .icon:before{ color: ' + curMainColor + '}' +
      '.inline-btn.tj_btn{  background-color: '+curMainColor+'}';
    style.innerHTML = styleStr;
    body.appendChild(style);
  }
  window.onload = function () {
   //  theme();
   var oldScrollTop = getScrollTop() || 0; 
   document.body.addEventListener('focusout',function(){ //IOS软键盘关闭事件
     var ua = window.navigator.userAgent;
     if (ua.indexOf('iPhone') > 0 || ua.indexOf('iPad') > 0) { //键盘收起页面空白问题
         document.body.scrollTop = oldScrollTop;
         document.documentElement.scrollTop = oldScrollTop;
     }
   });
  }

})(document, window);
function getScrollTop(){  
  var scrollTop=0;  
  if(document.documentElement&&document.documentElement.scrollTop){  
      scrollTop=document.documentElement.scrollTop;  
  }else if(document.body){  
      scrollTop=document.body.scrollTop;  
  }  
  return scrollTop;  
};
//弹出提示
function _toast(text,time){
    var time=time||1500
    $('body').append('<div class="ZP-toast" ><div style="top:50%">'+text+'</div></div>')    
    setTimeout(function(){
      $('.ZP-toast').remove()
    },time)
}
