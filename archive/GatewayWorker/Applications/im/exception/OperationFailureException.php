<?php
namespace app\im\exception;

/**
 * 描述业务逻辑失败.
 * @author silence
 */
class OperationFailureException extends \Exception {
    public function __construct ($message = null, $code = null, $previous = null) {
        if ($message == null) {
            $message = "操作失败, 请稍后重试～";
        }
        parent::__construct($message, $code, $previous);
    }
}