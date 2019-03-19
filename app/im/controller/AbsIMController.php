<?php
namespace app\im\controller;
use think\Request;

abstract class AbsIMController {
   
    protected function success($msg = '', $data = '', array $header = []) {
        
    }
}