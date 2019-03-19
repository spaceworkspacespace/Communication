<?php

namespace app\im\util;

require_once implode([__DIR__, DIRECTORY_SEPARATOR, "ISocketLogDriver.php"]);

use GatewayClient\Gateway;

class SocketLogDriverImpl implements ISocketLogDriver{
    private $config = [];
    
    public function save(array $log = [], $append = false): bool
    {
        foreach ($log as $level => $logs) {
            foreach($logs as $str) {
                if (is_string($str)) Gateway::sendToUid($this->config["remote_log_uid"], json_encode([
                    "type" => "SEND",
                    "data" => [
                        "level"=>$level,
                        "message"=>$str
                    ]
                ]));
            }
        }
        return true;
    }

    public function __construct(array $config = [])
    {
        $this->config = $config;
        Gateway::$registerAddress = $config['remote'];
    }
}