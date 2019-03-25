<?php
namespace app\im\behavior;

class TestBehavior {
    public static function imBehaviorTest(&$params) {
        im_log("debug", "test behavior: ", $params);
    }
}