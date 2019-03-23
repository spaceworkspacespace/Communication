<?php
namespace app\im\validate;

use think\Validate;

class GroupValidate extends Validate {
    protected $rule = [
//         "groupname" => "/^.{1,54}$/",
        "groupname"=> "require|max:18",
//         "description" => "/^.{0,360}$/",
        "description" => "require|max:120",
        "avatar" => "/^.{0,200}$/"
    ];
    
    protected $message = [
        "groupname"=>"名称必须长度必须是 1~18 字符 !",
        "description"=>"描述过长 !",
        "avatar" => "图片长度过长 !"
    ];
}