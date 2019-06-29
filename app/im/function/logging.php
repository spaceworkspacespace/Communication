<?php


/**
 * 输出 debug 日志到 im_log 的便捷方法
 */
function println() {
    $args = func_get_args();
    array_unshift($args, "debug");
    call_user_func_array("im_log", $args);
    /*
     $args = func_get_args();
     // 将数据简化为字符串类型
     $strAry = array_map(function($mixed) {
     $str = "";
     if (is_array($mixed)) {
     $str = implode([$str, json_encode($mixed)]);
     } else if (is_bool($mixed)) {
     if ($mixed) $str = "true";
     else $str = "false";
     } else if (is_null($mixed)) {
     $str = "null";
     } else  {
     $str = strval($mixed);
     }
     return $str;
     }, $args);
     // 拼接数组为单个字符串
     $str = array_reduce($strAry, function($carry, $item) {
     if (empty($carry))
     return $item;
     else
     return implode([$carry ,  ", ", $item]);
     }, "");
     // 打印
     trace($str, "debug");
     */
}

function tprintln() {
    foreach(func_get_args() as $v) {
        var_dump($v);
        echo "<br />";
    }
    echo "<hr />";
}

/**
 * 日志函数, 第一个参数为日志等级 (ThinkCMF 中所用的等级, 这函数只是一个使用 ThinkCMF 日志功能的便利函数) 其他参数将被转换成字符串类型的日志内容.
 * 默认会在消息前面加上日志的时间和日志标记 "IM".
 * @return boolean 当日志失败是返回 false.
 */
function im_log() {
    $args = func_get_args();
    if (count($args)<2) return false;
    $level = $args[0];
    $args = array_slice($args, 1);
    
    // 将数据简化为字符串类型
    $strAry = array_map(function($mixed) {
        $str = "";
        if (is_array($mixed)) {
            $str = implode([$str, json_encode($mixed)]);
        } else if (is_bool($mixed)) {
            if ($mixed) $str = "true";
            else $str = "false";
        } else if (is_null($mixed)) {
            $str = "null";
        } else if (is_object($mixed)) {
            if (method_exists($mixed, "__toString"))
                $str = (string)$mixed;
            $str = implode([get_class($mixed), ": ", $str]);
        } else {
            $str = strval($mixed);
        }
        return $str;
    }, $args);
        // 拼接数组为单个字符串
        $str = implode($strAry);
        $msg = implode(["[ ",date(DateTimeInterface::ISO8601)," ] IM: ", $str]);
        // 记录日志.
        trace( $msg, $level);
}

