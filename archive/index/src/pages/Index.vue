<style scoped>
#x-index {
    height: 100%;
    overflow: auto;
    display: block;
    margin: 0;
    padding: 0;
    width: 100%;
    background-color: #fcc572;
}
.header-wrap {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 60px;
    border: 0px;
    background-color: #212121;
    margin: 0 auto;
}

#x-index div {
    display: block;
}

.header {
    position: relative;
    width: 980px;
    height: 100%;
    margin: 0 auto;
    padding: 0;
}

.i-nav li a {
    font-size: 20px;
    color: #ffffff;
    text-decoration: none;
}
.i-nav {
    overflow: hidden;
    margin: 0;
    padding: 0;
    line-height: 60px;
}
.i-nav li {
    float: left;
    margin-left: 2em;
}

.header .i-nav {
    position: relative;
}

#x-index ul {
    list-style: none;
}

.bg {
    width: 100%;
    height: 348px;
    background: #4cb4f8;
}

.ap {
    width: 85%;
    height: 500px;
    margin: 0 auto;
}

.box {
    width: 180px;
    /* height: 180px; */
    /* float: right; */
    /* margin: 80px 0; */
    /* margin-top: 100px; */
    /* margin-right: 100px; */
    padding: 15px;
    border-radius: 5px;
    background: #fff;
    overflow: hidden;
}

.title {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 30px;
}

.it {
    float: left;
    width: 80%;
    height: 42px;
    line-height: 42px;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 30px;
    margin-left: 16px;
    border: 0;
    background: #519af5;
}

.at {
    float: left;
    width: 80%;
    height: 42px;
    line-height: 42px;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 30px;
    margin-left: 16px;
    border: 0;
    background: #f2a634;
}

.bg-service {
    background: #fcc572;
    width: 100%;
    /* position: relative;
    height: 70%;
     */
    /* position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    top: 348px; */
}

.i-services h2 {
    color: #949492;
    font-size: 22px;
    text-align: center;
    line-height: 32px;
    margin-bottom: 10px;
}

.i-services .i-tips {
    text-align: center;
    line-height: 30px;
    color: #949492;
    font-size: 18px;
}

.i-container {
    /* margin-right: 5%; */
    position: relative;
    /* float: left;
    margin-left: 13%;
    margin-top: -12%; */
    overflow: hidden;
    border-radius: 9px;
    width: 180px;
    display: block;
    margin: 0 auto;
}

.two {
    margin-right: 5%;
    position: relative;
    float: left;
    margin-top: -12%;
}

.center {
    margin-right: 5%;
    position: relative;
    float: left;
    margin-top: -12%;
}

.bottom {
    margin-right: 5%;
    position: relative;
    float: left;
    margin-top: -12%;
}

.list {
    position: relative;
    margin-top: -12%;
}

.image {
    display: block;
    width: 100%;
    height: auto;
}

.overlay {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    background-color: rgb(199, 207, 207);
    overflow: hidden;
    width: 100%;
    height: 0;
    transition: 0.5s ease;
    opacity: 0.7;
}

.i-container:hover .overlay {
    bottom: 0;
    height: 100%;
}

.two:hover .overlay {
    bottom: 0;
    height: 100%;
}

.center:hover .overlay {
    bottom: 0;
    height: 100%;
}

.bottom:hover .overlay {
    bottom: 0;
    height: 100%;
}

.list:hover .overlay {
    bottom: 0;
    height: 100%;
}

.text {
    white-space: nowrap;
    color: white;
    font-size: 20px;
    position: absolute;
    overflow: hidden;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
}
</style>

<template>
    <div id="x-index" class="container-fluid">
        <div id="header" class="header-wrap" style="z-index: 999;">
            <div class="header">
                <ul class="i-nav">
                    <li>
                        <a class="indexl" href="javascript:;" style>LiaoTian</a>
                    </li>
                    <li>
                        <a class="link" href="javascript:;" style>首页</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="bg" style="margin-top: 60px; position: relative;">
            <div v-if="$store.state.user != null" 
                class="box position-absolute" 
                style="right: 5%; top: 25%;">
                <div class="title">欢迎您, {{$store.state.user.username}} !</div>
                <div>
                    <button lay-submit lay-filter class="it">
                        <a :href="links.userCenter" style="color:#FFF">个人中心</a>
                    </button>
                    <div>
                    <button lay-submit lay-filter class="at">
                        <a :href="links.logout" style="color:#FFF">退出登录</a>
                    </button>
                </div>
                </div>
            </div>
            <div v-else 
                class="box position-absolute" 
                style="right: 5%; top: 25%;">
                <div class="title">欢迎登录</div>
                <div>
                    <button lay-submit lay-filter class="it">
                        <a :href="links.login" style="color:#FFF">登录</a>
                    </button>
                </div>
                <div>
                    <button lay-submit lay-filter class="at">
                        <a :href="links.register" style="color:#FFF">注册</a>
                    </button>
                </div>
            </div>
        </div>
        <div class="bg-service py-5">
            <div class="i-services">
                <h2>聊天服务</h2>
                <p class="i-tips">沟通是一种能力，更是一门艺术！能在沟通中使人愉悦的人，一定能享受比别人更多的快乐和幸运...</p>
            </div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col d-flex" style="justify-content: center; flex-direction: row;">
                        <a
                            class="btn btn-primary px-5 py-3"
                            target="_blank"
                            :href="links.im"
                            style="font-size: 18px;"
                        >开始聊天</a>
                    </div>
                    <!-- <a class="mb-1 col-sm-6 col-md-3 offset-xl-1 col-xl-2" href="F:\Files\tool\index5.html" target="_blank">
                    <div class="i-container">
                        <img src="/static/images/a.png" alt="Avatar" class="image">
                        <div class="overlay">
                            <div class="text">快捷管理好友</div>
                        </div>
                    </div>
                </a>
                <a class="mb-1 col-sm-6 col-md-3 col-xl-2" href="F:\Files\tool\index5.html" target="_blank">
                    <div class="i-container">
                        <img src="/static/images/e8.png" alt="Avatar" class="image">
                        <div class="overlay">
                            <div class="text">支持群组管理</div>
                        </div>
                    </div>
                </a>
                <a class="mb-1 col-sm-6 col-md-3 col-xl-2" href="F:\Files\tool\index5.html" target="_blank">
                    <div class="i-container">
                        <img src="/static/images/f0.png" alt="Avatar" class="image">
                        <div class="overlay">
                            <div class="text">方便快捷管理消息</div>
                        </div>
                    </div>
                </a>
                <a class="mb-1 col-sm-6 col-md-3 col-xl-2" href="F:\Files\tool\index5.html" target="_blank">
                    <div class="i-container">
                        <img src="/static/images/c.png" alt="Avatar" class="image">
                        <div class="overlay">
                            <div class="text">支持个性动态设置</div>
                        </div>
                    </div>
                </a>
                <a class="mb-1 col-sm-6 col-md-3 col-xl-2" href="F:\Files\tool\index5.html" target="_blank">
                    <div class="i-container">
                        <img src="/static/images/b.png" alt="Avatar" class="image">
                        <div class="overlay">
                            <div class="text">支持更多应用管理</div>
                        </div>
                    </div>
                    </a>-->
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { LINKS } from '@/conf/link'
export default {
    beforeMount: function() {
        console.log(this.$store.state.user)
    },
    data: function () {
        return {
            links: LINKS.page
        }
    }
}
</script>
