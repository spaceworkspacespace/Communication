<?php 

namespace app\im\model;

use think\Model;

class IMModel extends Model {
    protected $connection = [
        'prefix' => 'im_'
    ];
    
}