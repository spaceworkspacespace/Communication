<?php 
namespace app\im\controller;

use think\Controller;

class errorController extends Controller {
    
    function index() {
        $this->redirect(url("/static/error/index"));
    }
    
    function _empty() {
        $this->redirect(url("/static/error/index"));
    }
    
}