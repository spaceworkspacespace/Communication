<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \GatewayWorker\Register;
use GatewayWorker\Lib\Gateway;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';

// register 必须是text协议
$register = new Register('text://0.0.0.0:'.config("gateway.register_port"));
Gateway::$registerAddress = "127.0.0.1:".config("gateway.register_port");
// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

