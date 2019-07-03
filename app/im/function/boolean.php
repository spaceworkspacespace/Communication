<?php


/**
 * 便利方法. 通过表达式是否为 true, 确定要返回的值 (或运行的闭包). 类似于 mysql IF 函数
 * @param mixed $expr
 * @param mixed $truthy
 * @param mixed $falsey
 * @return mixed
 */
function boolean_select($expr, $truthy, $falsey) {
    $result = null;
    // 获取值
    if ($expr) {
        $result = $truthy;
    } else {
        $result = $falsey;
    }
    // 如果是闭包就执行
    if ($truthy instanceof \Closure ) {
        $result = $result();
    } 
    
    return $result;
}