<?php

/**
 * 判断字符串是否以指定字串开始.
 * @param string $str
 * @param string $prefix
 * @param int $offset
 * @return boolean
 */
function str_starts_with(string $str, string $prefix, int $offset=0) {
    if ($offset > 0) {
        $str = substr($str, $offset);
    }
    if (preg_match("/^$prefix/", $str)) {
        return true;
    }
    return false;
}

/**
 * 将模板字符串中的 {name} 替换为指定内容
 * @param string $str 字符串
 * @param mixed $data 数组或函数, 如果是数组将使用数组上同名的属性值, 函数将传递名称作为参数, 要求返回属性值.
 * @return string
 */
function str_template(string $str, $data): string {
    $match = [];
    preg_match_all("/\{(\w+)\}/", $str, $match);
    // 没有任何匹配
    if (count($match) === 0) {
        return $str;
    }
    // 迭代第一个, 也就是只要捕获组中的内容
    $v = [];
    if (is_array($data)) { // 传递数组
        $v = array_map(function($m) use ($data){
            if (isset($data[$m])) {
                return $data[$m];
            }
            return $m;
        }, $match[1]);
    } else if (is_string($data)) { // 传递函数名称
        $v = array_map("$data", $match[1]);
    } else { // 传递匿名函数
        $v = array_map($data, $match[1]);
    }
    return str_replace($match[0], $v, $str);
}