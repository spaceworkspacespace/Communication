<?php

use think\Route;

Route::group("im", function() {
    // 联系人操作
    Route::controller("contact", "im/contact");
});

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