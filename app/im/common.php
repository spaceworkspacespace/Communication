<?php 

/**
 * 输出 debug 日志到 im_log 的便捷方法
 */
function println() {
    $args = func_get_args();
    array_unshift($args, "debug");
    call_user_func("im_log", $args);
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

/**
 * 替换数组中的键名, 键名可以是数字或字符串. 方法会改变原数组.
 * @param array $ary 操作的数组
 * @param mixed $old 旧的键名, 可以是数组 [ 新键名=> 旧键名 ...], 进行批量替换
 * @param mixed $new 新的键名, 批量替换时不需要
 * @return array 替换失败的旧的键名.
 */
function array_key_replace(&$ary, $old, $new=null) {
    // 替换失败的键名的数组
    $failure = [];
    if (is_array($old)) { // 批量替换
        foreach ($old as $o => $n) {
            if(array_key_exists($n, $ary) ||
                !array_key_exists($o, $ary)) array_push($failure, $o);
            else {
                $ary[$n] = $ary[$o];
                unset($ary[$o]);
            }
        }
    } else if(is_string($new) || // 单个替换
        is_numeric($new)){
        if(array_key_exists($new, $ary) ||
            !array_key_exists($old, $ary)) array_push($failure, $old);
        else {
            $ary[$new] = $ary[$old];
            unset($ary[$old]);
        }
    } else {
        array_push($failure, $old);
    }
    return $failure;
}

/**
 * 从数组中选取指定的索引组成新数组. 方法不会改变原数组.
 * @param array $ary 数组
 * @param mixed ...$columns 子集的索引
 * @return array 子集
 */
function array_index_pick(array $ary, ...$indexes): array {
    $subset = [];
    foreach($indexes as $col) {
        if (array_key_exists($col, $ary))
            $subset[$col] = $ary[$col];
    }
    return $subset;
}

/**
 * 批量复制源数组中的索引表示的值到目标数组中. 数组的 index 的批量赋值. 如果目标数组中存在此索引, 值将被覆盖. 源数组中不存在索引, 将跳过.
 * @param array $source
 * @param array $dest
 * @param mixed ...$indexes
 * @return array
 */
function array_index_copy(array $source, array &$dest, ...$indexes) {
    foreach($indexes as $index) {
        if (array_key_exists($index, $source)) {
            $dest[$index] = $source[$index];
        }
    }
}

