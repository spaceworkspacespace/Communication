<?php 
namespace app\im\controller;

use think\Request;
use traits\controller\Jump;

class ContactController  {
    use Jump;
    
    protected  $request = null;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }
    
    public function getTest() {
        
        return 1;
    }
    
    /**
     * 上传头像, 群组和用户的.
     * @param mixed $file
     */
    public function postAvatar() {
        $file = $this->request->file("file");
        if (!$file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH, "public", DIRECTORY_SEPARATOR, "upload"]);
        $info = $file->validate(['ext'=>'jpg,png'])->rule("md5")->move($folder);
        if (!$info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/", $info->getSaveName()]);
        $this->error("", "/", $url, 0);
    }
    
    public function getGroup(){
        
    }
    
    /**
     * 新建一个分组
     * @param string $groupname
     * @param mixed $avatar
     * @param string $description
     */
    public function postGroup(string $groupname, $avatar, string $description) {
        
    }
    
    /**
     * 删除分组
     */
    public function deleteGroup() {
        
    }
    
    /**
     * 为自己添加分组
     */
    public function postLinkGroup() {
        
    }
    
    /**
     * 为自己删除分组
     */
    public function postUnlinkGroup() {
        
    }
    
    /**
     * 为自己添加好友
     */
    public function postLinkFriend() {
        
    }
    
    /**
     * 为自己删除好友
     */
    public function postUnlinkFriend() {
        
    }
}