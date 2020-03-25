// pages/my/my.js
const app = getApp()
var api = require("../../common/api.js")
Page({

    /**
     * 页面的初始数据
     */
    data: {
        islogin: false,
        memberinfo: [],
        https_path:api.https_path,
        wait_pay_num:0,
        wait_shipping_num:0,
        wait_sign_num: 0,
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function(options) {
        const That = this
        api.islogin() //判断是否已经登录，没有登录跳转去登录
        That.getmemberinfo()
    },

    //获取用户资料
    getmemberinfo: function() {
        const That = this
      api.fetchPost(api.https_path + '/member/api.users/getcenterinfo', {}, function(err, res) {
            if (res.code == 1) {
                if (res.userInfo.headimgurl.indexOf("http") >= 0) {
                    console.log('包含此字符串')
                }else{
                    res.userInfo.headimgurl = api.https_path + res.userInfo.headimgurl
                }
                That.setData({
                    memberinfo: res.userInfo,
                    islogin: true,
                    wait_pay_num: res.orderStats.wait_pay_num,
                    wait_shipping_num: res.orderStats.wait_shipping_num,
                    wait_sign_num: res.orderStats.wait_sign_num,
                })
            } else {
                api.error_msg(res.msg)
            }
            console.log(res)
        })
    },


    goSetting() {
        wx.navigateTo({
            url: '/pages/set/set',
        })
    },
    godologin(){
      wx.redirectTo({
        url: '/pages/authorizeLogin/authorizeLogin',
      })
    },

    /**
     * 生命周期函数--监听页面初次渲染完成
     */
    onReady: function() {

    },

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function() {

    },

    /**
     * 生命周期函数--监听页面隐藏
     */
    onHide: function() {

    },

    /**
     * 生命周期函数--监听页面卸载
     */
    onUnload: function() {

    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function() {

    },

    /**
     * 页面上拉触底事件的处理函数
     */
    onReachBottom: function() {

    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function() {

    }
})