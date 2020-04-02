KindEditor.plugin('diy_video', function (K) {
    var self = this, name = 'diy_video';
    self.clickToolbar(name, function () {
        var lang = self.lang(name + '.'),
            html = ['<div style="padding:10px 20px;">',
                '<textarea class="ke-textarea" style="width:408px;height:260px;" placeholder="代码格式如下：\r\r<iframe height=498 width=510 src=http://player.youku.com/embed/XMzE4ODg3MjgyOA== frameborder=0 allowfullscreen></iframe>"></textarea>',
                '</div>'].join(''),
            dialog = self.createDialog({
                name: name,
                width: 450,
                title: self.lang(name),
                body: html,
                yesBtn: {
                    name: self.lang('yes'),
                    click: function (e) {
                        var code = textarea.val(),
                            html = '' + code + '';
                        if (K.trim(code) === '') {
                            alert("请输入视频代码！");
                            textarea[0].focus();
                            return;
                        }
                        self.insertHtml(html).hideDialog().focus();
                    }
                }
            }),
            textarea = K('textarea', dialog.div);
        textarea[0].focus();
    });
});
KindEditor.lang({
    diy_video: '视频代码'
});