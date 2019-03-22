<?php 

namespace app\im\controller;

use think\Controller;
use think\Db;

class MsgboxController extends Controller {
    
    /**
     * 跟我接收人id查询消息
     * @param unknown $page 页码
     * @param unknown $receiver_id  接收人id
     * @return string
     */
    public function data($page,$receiver_id) {
        
        //查询我的消息
        $mydata = Db::table('im_msg_box')
        ->where('receiver_id = '.$receiver_id)
        ->select()
        ->toArray();
        
        //定义承载外键的数组
        $sender_ids = [];
        //定义循环变量
        $i = 0;
        
        //将获取到的时间转换为YMD格式
        $mydata = array_map(function($value) {
            $value['send_date'] = date("Y-m-d",strtotime($value['send_date']));
            return $value;
        }, $mydata);
        
        //获取外键
        foreach ($mydata as $value) {
            $sender_ids[$i] = $value['sender_id'];
            $i++;
        }
        
        //根据外键查询发送人信息
        $udata = Db::table('cmf_user')
        ->where('id','in',$sender_ids)
        ->select();
        
        $list = array("code" => 0,"page" => intval($page),"data" => $mydata,"udata" => $udata);
        //转换为json并返回
        return json_encode($list);

    }
    
    /**
     * //拒绝接受好友申请
     * @param unknown $sender_id 请求人id
     * @param unknown $receiver_id  接收人id
     * @return number|string
     */
    public function refuse($sender_id,$receiver_id) {
        
        $res = Db::table('im_msg_box')
        ->where([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ])
        ->update(['agree' => 'n']);
        
        return $res;
    }
    
    /**
     * 同意接受好友申请
     * @param unknown $sender_id 请求人id
     * @param unknown $receiver_id 接收人id
     * @return number|string
     */
    public function agree($sender_id,$receiver_id) {
        
        $res = Db::table('im_msg_box')
        ->where([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ])
        ->update(['agree' => 'y']);
        
        return $res;
    }
}