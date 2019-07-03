<?php


/**
 * 取组合.
 * @param mixed $array 组合数据来源数组.
 * @param mixed $m 组合元素的个数
 * @return mixed 组合的数组
 */
function array_combination($array, $m) {
    // 如果选取数量大于数组长度, 将选取数量设置为长度.
    if (count($array) < $m) {
        $m = count($array);
    }
    // 判断输入值..
    if ($m <= 0) {
        return [];
    }
    
    $multidimensional  = array_map_with_index(array_slice($array, 0, count($array)-$m+1), function($value, $index) use($array, $m) {
        $nextArray = array_slice($array, $index+1);
        $nextM = $m -1;
        $currentValue = [$value];
        
        if ($nextM === 0) {
            return $currentValue;
        } else {
            return array_map_with_index(array_combination($nextArray, $nextM), function($value) use($currentValue) {
                return array_merge($currentValue, $value);
            });
        }
    });
    // 当 m 大于 1 时, 数组中的每个元素都为二维数组. 1 个元素有多个子结果对应.
    if ($m > 1) {
        return array_reduce($multidimensional, function($carry, $value) {
            return array_merge($carry, $value);
        }, []);
    }
    return $multidimensional;
}

/**
 * 求 a 关于 u 的相对补集.
 * @param array $u
 * @param array $a
 * @return array
 */
function array_complementary($u, $a) {
    return array_filter($u, function($value) use ($a) {
        return array_search($value, $a) !== false? false: true;
    });
}

/**
 * 迭代所有元素, 如果回调应用在所有元素上返回值都为 true, 则结果为 true, 否则 false.
 * @param array $array
 * @param string|\Closure $predicate
 * @return boolean
 */
function array_every(array $array, $predicate) {
    $result = true;
    foreach ($array as $key => $value) {
        if (is_string($predicate)) {
            $result = call_user_func($predicate, $value, $key, $array);
        } else {
            $result = $predicate($value, $key, $array);
        }
        // 只要有一个返回结果为 false, 立即结束函数运行
        if (!$result) {
            break;
        }
    }
    return (boolean) $result;
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

// 只是会覆盖原来的值
function array_key_replace_force(&$ary, $old, $new=null) {
    // 替换失败的键名的数组
    $failure = [];
    if (is_array($old)) { // 批量替换
        foreach ($old as $o => $n) {
            if(!array_key_exists($o, $ary)) array_push($failure, $o);
            else {
                $ary[$n] = $ary[$o];
                unset($ary[$o]);
            }
        }
    } else if(is_string($new) || // 单个替换
        is_numeric($new)){
            if(!array_key_exists($old, $ary)) array_push($failure, $old);
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
    if (count($indexes) == 0) return;
    if (is_array($indexes[0])) $indexes = $indexes[0];
    
    foreach($indexes as $index) {
        if (array_key_exists($index, $source)) {
            $dest[$index] = $source[$index];
        }
    }
}

/**
 * 用严格相等查找元素在数组中的位置, 不存在则返回 -1
 * @param array $array
 * @param mixed $search
 * @param int $startIndex
 */
function array_index_of(array $array, $search, int $startIndex=0) {
    for ($i=$startIndex; $i < count($array); $i++) {
        if ($array[$i] === $search) {
            return $i;
        }
    }
    return -1;
}

/**
 * 迭代数组.
 * @param array $array 要迭代的数组
 * @param mixed $iteratee (value, key, array) => false | any.  如果返回 false (严格相等) 将中断迭代.
 * @return 没有返回值...
 */
function array_for_each(array $array, $iteratee) {
    $result = null;
    foreach($array as $key => $value) {
        if(is_string($iteratee)) {
            $result = call_user_func($iteratee, $value, $key, $array);
        } else {
            $result = $iteratee($value, $key, $array);
        }
        if ($result === false) break;
    }
}

/**
 * 迭代数组的 key
 * @param array $array
 * @param mixed $callback (value, key, array)
 */
function array_map_keys(array $array, $callback) {
    $result = [];
    foreach($array as $key => $value) {
        $newKey = '';
        if (is_string($callback)) {
            $newKey = call_user_func($callback, $value, $key, $array);
        } else {
            $newKey = $callback($value, $key, $array);
        }
        $result[$newKey] = $value;
    }
    return $result;
}

/**
 * 带 index 和操作数组的 map
 * @param array $array
 * @param mixed $callback (value, index, array)
 */
function array_map_with_index(array $array, $callback) {
    $result = [];
    foreach($array as $key => $value) {
//         println($value, $key);
        if (is_string($callback)) {
            $result[$key] = call_user_func($callback, $value, $key, $array);
        } else {
            $result[$key] = $callback($value, $key, $array);
        }
    }
    return $result;
}

/**
 * 取排列.
 * @param mixed $array 排列数据来源数组.
 * @param mixed $m 排列元素的个数
 * @return mixed 排列的数组
 */
function array_permutation($array, $m) {
    // 如果选取数量大于数组长度, 将选取数量设置为长度.
    if (count($array) < $m) {
        $m = count($array);
    }
    // 判断输入值..
    if ($m <= 0) {
        return [];
    }
    
    $multidimensional  = array_map_with_index($array, function($value, $index, $array) use($m) {
        $nextArray = $array;
        array_splice($nextArray, $index, 1);
        $nextM = $m -1;
        $currentValue = [$value];
        
        if ($nextM === 0) {
            return $currentValue;
        } else {
            return array_map_with_index(array_permutation($nextArray, $nextM), function($value) use($currentValue) {
                return array_merge($currentValue, $value);
            });
        }
    });
    // 当 m 大于 1 时, 数组中的每个元素都为二维数组. 1 个元素有多个子结果对应.
    if ($m > 1) {
        return array_reduce($multidimensional, function($carry, $value) {
            return array_merge($carry, $value);
        }, []);
    }
    return $multidimensional;
}

/**
 * 带 index 和操作数组的 reduce
 * @param array $array
 * @param mixed $accumulator (accumulator, currentValue, index, array)
 * @param mixed $initialValue
 * @return string|mixed
 */
function array_reduce_with_index(array $array, $accumulator, $initialValue = null) {
    $result = $initialValue;
    foreach($array as $index=>$value) {
        if (is_string($accumulator)) {
            $result = call_user_func($accumulator, "$result", $value, $index, $array);
        } else {
            $result = $accumulator($result, $value, $index, $array);
        }
    }
    return $result;
}

/**
 * 选择器...
 * @param array $array
 * @param mixed $closures Array<{ [key: string]: (value, key, array)=>any }>
 * @param bool $null_valid 回调返回 null 是否中止迭代 (如果为 true, 当遇到返回为 null 的回调, 选择器的结果将会为 null).
 * @param mixed $defaultValue  默认结果, 当最终结果为 null, 即返回该值.
 * @return null | mixed 选择器执行的结果.
 */
function array_select(array $array, $closures, $null_valid=true, $defaultValue=null) {
    $result = null;
    foreach($array as $key => $value) {
        if (isset($closures[$key])) {
            $callback = $closures[$key];
            if (is_string($callback)) {
                $result = call_user_func($callback, $value, $key, $array);
            } else {
                $result = $callback($value, $key, $array);
            }
            // 当 null 为有效值或调用结果不为 null 是即可结束调用.
            if ($null_valid 
                || !is_null($result)) {
                break;
            }
        }
    }
    // 默认值处理
    if (is_null($result) && !is_null($defaultValue)) {
        $result = $defaultValue;
    }
    return $result;
}
