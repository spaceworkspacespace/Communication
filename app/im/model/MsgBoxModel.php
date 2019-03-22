<?php

namespace app\im\model;
use think\Model;

class MsgBoxModel extends Model
{
    protected $connection = [
        'prefix' => 'im_'
    ];
    
    //多对一关联
    public function user()
    {
        return $this->belongsTo('UserModel','sender_id');
    }
}