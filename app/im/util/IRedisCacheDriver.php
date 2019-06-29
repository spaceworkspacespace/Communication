<?php
namespace app\im\util;

interface IRedisCacheDriver {
    
    /**
     * 事务操作
     * @return null | Array
     */
    function exec();
    
    /**
     * 事务操作
     */
    function multi() ;
    
    /**
     * 事务操作.
     * @param Array<string> ...$keys
     */
    function watch(...$keys) ;
    
    
}