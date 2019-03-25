<?php

return [
    "app_init"=> [
        
    ],
    "im_behavior_test"=>[
        "app\\im\\behavior\\TestBehavior"        
    ],
    "keys_update" => [],
    "gateway_send" => ["app\\im\\behavior\\MsgCryptoBehavior"],
];