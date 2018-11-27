<?php
/**
 * Created by PhpStorm.
 * User: wujie
 * Date: 2018/11/26
 * Time: 16:46
 */

namespace getui\src\httpRequest\push;


use getui\config\Config;
use getui\src\exception\RequestException;
use getui\src\template\Message;

class PushSign extends Base
{
    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->setUrl("push_single");
    }

    public function getRequestBody(): array
    {
        return array_merge([
            "message" => $this->getMessage(),
            "requestid" => $this->getRequestId(),
            "cid" => $this->getCid(),
        ], $this->getMessageContent());
    }

}