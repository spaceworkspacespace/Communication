<?php
return [
    "action_begin" => [
        "app\\im\\behavior\\InitBehavior"
    ],
    "module_init"=>[
        "app\\im\\behavior\\InitBehavior"
    ],
    "im_behavior_test" => [
        "app\\im\\behavior\\TestBehavior"
    ],
    "keys_update" => [],
    "gateway_send" => [
        "app\\im\\behavior\\MsgCryptoBehavior",
    ]
];