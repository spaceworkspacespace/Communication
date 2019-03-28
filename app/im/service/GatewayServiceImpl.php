<?php
namespace app\im\service;

use GatewayClient\Gateway;
use think\Hook;

class GatewayServiceImpl implements IGatewayService
{

    // private static $_instance = null;
    // private $config = null;

    // public function __construct($config) {
    // $this->config = $config;
    // Gateway::$registerAddress = $config["remote"];
    // }

    // public static function instance($config = null) {
    // if (is_null(self::$_instance)) {
    // self::$_instance = new self($config || []);
    // } else if (is_array($config)) {
    // self::$_instance->update($config);
    // }
    // return self::$_instance;
    // }

    // public function update(array $config) {
    // $this->config = array_merge($this->config, $config);
    // }
    public static function msgToClient($clientId, $data): void
    {
        $uid = Gateway::getUidByClientId($clientId);
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToClient($clientId, $data);
    }

    public static function msgToUid($uid, $data): void
    {
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }

    public static function askToClient($clientId, $data): void
    {
        $uid = Gateway::getUidByClientId($clientId);
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::ASK_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToClient($clientId, $data);
    }

    public static function askToUid($uid, $data): void
    {
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::ASK_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }

    public static function updateToUid($uid, $data): void
    {
        $data = [
            "id" => "u-$uid",
            "payload" => [
                "type" => self::UPDATE_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToUid($uid, $data);
    }

    public static function msgToGroup($group, $data): void
    {
        $data = [
            "id" => "g-$group",
            "payload" => [
                "type" => self::MESSAGE_TYPE,
                "data" => $data
            ]
        ];
        Hook::listen("gateway_send", $data);
        Gateway::sendToGroup($group, $data);
    }
}