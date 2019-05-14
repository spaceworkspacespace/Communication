<?php
namespace app\im\service;

class SingletonServiceFactory {
    private static $chatService = null;
    private static $contactService = null;
    private static $gatewayService = null;
    private static $messageService = null;
    private static $pushService = null;
    private static $userService = null;
    
    public static function getChatService(): IChatService {
        if (static::$chatService == null) {
            static::$chatService = new ChatService();
        }
        return static::$chatService;
    }
    
    public static function getContactService():IContactService {
        if (static::$contactService == null) {
            static::$contactService = new ContactService();
        }
        return static::$contactService;
    }
    
    public static function getGatewayService(): IGatewayService {
        if (static::$gatewayService == null) {
            static::$gatewayService = new GatewayService();
        }
        return static::$gatewayService;
    }
    
    public static function getMessageService(): IMessageService {
        if (static::$messageService == null) {
            static::$messageService = new MessageService();
        }
        return static::$messageService;
    }
    
    public static function getPushService():IPushService {
        if (static::$pushService == null) {
            static::$pushService = new PushService();
        }
        return static::$pushService;
    }
    
    public static function getUserService(): IUserService {
        if (static::$userService == null) {
            static::$userService = new UserService();
        }
        return static::$userService;
    }
}