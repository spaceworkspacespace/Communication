<?php
namespace app\im\controller;

use think\Controller;

class CommController extends Controller{
    
    /**
     * 上传头像, 群组和用户的.
     *
     * @param mixed $file
     */
    public function postAvatar()
    {
        $file = $this->request->file("file");
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->validate([ 'ext' => 'jpg,png'])->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $fileName = isset($file->getInfo()["name"])?
        $file->getInfo()["name"]:$info->getSaveName();
        
        $_SESSION["im"]["avatar"]=$url;
        
        $this->error("", "/", [
            "src"=>$url,
            "name"=>$fileName,
            "size"=>$info->getSize()
        ], 0);
    }
    
    
    /**
     * 聊天图片, 群组和用户的.
     * @param mixed $file
     */
    public function postPicture()
    {
//         im_log("debug", $_POST["_ajax"]);
        $file = $this->request->file("file");
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->validate([ 'ext' => 'jpg,png,gif'])->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $fileName = isset($file->getInfo()["name"])?
        $file->getInfo()["name"]:$info->getSaveName();
        
        $_SESSION["im"]["picture"]=$url;
        
        $this->error("", "/", [
            "src"=> $url,
            "size"=>$info->getSize(),
            "name"=>$fileName
        ], 0);
    }
    
    /**
     * 聊天文件, 群组和用户的.
     * @param mixed $file
     */
    public function postFile()
    {
        $file = $this->request->file("file");
        //         im_log("debug", $file->getInfo()["name"]);
        if (! $file) {
            $this->success("未选择任何文件.", "/", null, 0);
            return;
        }
        $folder = implode([ROOT_PATH,"public", DIRECTORY_SEPARATOR, "upload" ]);
        $info = $file->rule("md5") ->move($folder);
        if (! $info) {
            $this->success($file->getError(), "/", null, 0);
            return;
        }
        $url = implode(["/upload/",$info->getSaveName() ]);
        $fileName = isset($file->getInfo()["name"])?
        $file->getInfo()["name"]:$info->getSaveName();
        
        $_SESSION["im"]["file"]=$url;
        
        $this->error("", "/", [
            "size"=>$info->getSize(),
            "src"=>$url,
            "name"=>$fileName], 0);
    }
    
}