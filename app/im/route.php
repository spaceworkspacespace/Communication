<?php

use think\Route;


Route::controller("test", "im/test");
// 联系人操作
Route::group("im", function () {
    Route::controller("contact", "contact");
    Route::controller("chat", "chat");
    Route::controller("message", "msgbox");
    Route::controller("user", "user");
    Route::controller("comm", "comm");
    
    Route::any('comment/:name',"comment/:name");
    Route::any('favorite/:name',"favorite/:name");
    Route::any('index/:name',"index/:name");
    Route::any("login/:name", "login/:name");
    Route::any("register/:name", "register/:name");
    Route::any("profile/:name", "profile/:name");
});



    // 加域名路由之前
    // Route::group("im", function() {
    //     // 联系人操作
    //     Route::controller("contact", "im/contact");
    //     Route::controller("chat", "im/chat");
    //     Route::controller("message", "im/msgbox");
    //     Route::controller("user", "im/user");
    //     Route::controller("comm", "im/comm");
    // });
    // Route::controller("test", "im/test");
    
    
    
    // Route::any("test", "im/test");
    
    // im/friend/query/:id
    // return [
    //     "[im]" => [
    // //         "[group]" => [
    // //             "query/:id" => ["im/index/info", ["method"=> "POST"], ["id"=> "\d+"]]
    // //         ],
    //         "friend/query/:id/:name" => ["im/index/info", ["complete_match"=>true]]
    // //         "[friend]" => [
    // //             "query/:id" => ["im/index/info", ["complete_match"=>true]]
    // //         ],
    // //         "__miss__" => "/"
    //     ]
    // //     "__miss__"=>"/index"
    // ];