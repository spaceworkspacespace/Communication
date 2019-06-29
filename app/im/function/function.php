<?php
const F_P_ = "function placeholder"; 

function function_compose(...$runs) {
    return function($args) use ($runs) {
        $result = $args;
        $length = count($runs);

        for($i=$length-1; $i>=0; $i--) {
            $result = $runs[$i]($result);
        }
        return $result;
    };
}

/**
 * 函数的柯里化.
 * @param mixed $runnable 可执行的函数, 可以是名称或闭包
 * @param int $length 指定参数长度
 * @return \Closure 闭包
 */
function function_curry($runnable, $length=null) {
    $reflection = new \ReflectionFunction($runnable);
    if (is_null($length)) {
        $length = count($reflection->getParameters());
    }
    // 参数长度小于等于 0 时直接返回函数
    if ($length <=0) {
        return $reflection->getClosure();
    }
    // 外面这一层函数是用来固定状态
    $stage = function ($length, $received, $fn, $next) {
        // 里面这一层是暴露给用户调用
        return function(...$params) use ($length, $received, $fn, $next) {
            $cursor = 0;
            foreach ($params as $value) {
                for ($i=$cursor; $i<$length; $i++) {
                    // tprintln($i, isset($received[$i]), $value);
                    if (!isset($received[$i])) {
                        // 设置游标, 下次查找从这里开始
                        $cursor=$i+1;
                        // 有效的值
                        if ($value !== F_P_) {
                            $received[$i] = $value;
                        }
                        break;
                    }
                }
            }
            // 参数数量是否满足调用
            if (count($received) === $length) {
                // 排序一下数组, 避免没有和参数位置为对应上
                ksort($received);
                return $fn->invokeArgs($received);
            }
            return $next($length, $received, $fn, $next);
        };
    };
    return $stage($length, [], $reflection, $stage);
}

function function_tap($run) {
    return function($arg) use ($run) {
        if (is_string($run)) {
            call_user_func($run, $arg);
        } else {
            $run($arg);
        }
        return $arg;
    };
}