<template>
    <div id="x-contactmgr" class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm" style="z-index: 10; margin: 0 -15px;">
            <router-link class="navbar-brand" :to="$router.currentRoute.matched[0].path">联系人管理</router-link>
            <button
                class="navbar-toggler"
                type="button"
                data-toggle="collapse"
                data-target="#contact-type"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div id="contact-type" class="collapse navbar-collapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <router-link
                            class="nav-link"
                            :to="$router.currentRoute.matched[0].path + '/find'"
                        >搜索</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            class="nav-link"
                            :to="$router.currentRoute.matched[0].path + '/friend'"
                        >好友</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            class="nav-link"
                            :to="$router.currentRoute.matched[0].path + '/group'"
                        >群聊</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            class="nav-link"
                            :to="$router.currentRoute.matched[0].path + '/groupnew'"
                        >新建群聊</router-link>
                    </li>
                </ul>
                <!-- <form class="form-inline my-2 my-lg-0">
                    <input
                        class="form-control mr-sm-2"
                        type="search"
                        placeholder="搜索联系人"
                        aria-label="Search"
                    >
                </form>-->
            </div>
        </nav>
        <!-- <div class="my-3"></div> -->
        <div class="x-fill x-flex" style="top: 56px;z-index: 9;">
            <div v-if="keyword"></div>
            <router-view v-else></router-view>
        </div>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            friend: [],
            currentGroup: 0,
            keyword: "", // 搜索关键字
        };
    },
    beforeMount: function () {

    }
}
</script>

<style>
#x-contactmgr {
    height: 100%;
    width: 100%;
    position: relative;
}
</style>