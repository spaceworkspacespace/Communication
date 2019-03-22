<?php
namespace app\im\model;

use think\Model;

class UserModel extends Model {
    
    //一对多关联
    public function msgbox() {
        return $this->hasMany('MsgBoxModel');
    }
}