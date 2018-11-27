<?php
/**
 * Created by PhpStorm.
 * User: wujie
 * Date: 2018/11/26
 * Time: 16:06
 */

namespace getui\src\httpRequest\push;

use getui\config\Config;
use getui\src\cache\CacheModel;
use getui\src\httpRequest\HttpRequest;
use getui\src\template\Message;

abstract class Base
{
    use RequestParams;
    use templateRequest;
    use CacheModel;

    protected $url;
    protected $requestBody;

    protected $method = HttpRequest::METHOD_POST;
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HttpRequest
     */
    protected $httpModel;

    /**
     * @var array
     */
    private $message;

    /**
     * 消息类型
     *
     * @var string
     */
    protected $msgtype = Message::MSG_TYPE_NOTIFICATION;

    /**
     * @var string 用户id
     */
    private $cid;

    /**
     * @var string 请求参数
     */
    private $requestId;

    /**
     * @var string 渗透参数
     */
    protected $transmission;

    /**
     * @return mixed
     */
    public function getTransmission()
    {
        $data = $this->transmission;
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return $data;
    }

    /**
     * @param mixed $transmission
     * @return $this
     */
    public function setTransmission($transmission)
    {
        $this->transmission = $transmission;
        return $this;
    }

    /**
     * @param mixed $requestBody
     * @return $this
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
        return $this;
    }


    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getHttpModel()
    {
        return $this->httpModel;
    }


    /**
     * @return string
     */
    public function getMsgtype(): string
    {
        return $this->msgtype;
    }

    /**
     * @return string
     */
    public function getCid(): string
    {
        return $this->cid;
    }

    /**
     * @param Config $config
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param null $httpModel
     * @return $this
     */
    public function setHttpModel($httpModel)
    {
        $this->httpModel = $httpModel;
        return $this;
    }

    /**
     * @param string $cid
     * @return $this
     */
    public function setCid(string $cid)
    {
        $this->cid = $cid;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        if ($this->requestId) {
            return "ky:" . $this->requestId;
        }
        $this->requestId = uniqid("ky:");
        return $this->requestId;
    }

    /**
     * @param string $requestId
     */
    public function setRequestId(string $requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @param string $msgtype
     * @return $this
     */
    public function setMsgtype(string $msgtype)
    {
        $this->msgtype = $msgtype;
        return $this;
    }

    /**
     * @param Message $message
     * @return $this
     */
    public function setMessage(Message $message)
    {
        $this->message = $message->getEntity();
        return $this;
    }


    /**
     * 请求 message
     *
     * @return mixed
     */
    public function getMessage()
    {
        if ($this->message) {
            return $this->message;
        }
        $message = new Message();
        $message->setAppKey($this->config->getAppKey());
        $message->setMsgtype($this->getMsgtype());
        $this->message = $message->getEntity();
        return $this->message;
    }

    public function getMessageContent(): array
    {
        if ($this->getMsgtype() == Message::MSG_TYPE_TRANSMISSION) {
            if ($this->getPushInfo()) {
                return $this->getPushInfo();
            }
            return $this->getTyansmission();
        }
        return [$this->getMsgtype() => array_merge($this->getMessageContentCommon(), $this->getOtherParams())];
    }

    public function getOtherParams()
    {
        if (!$this->getTransmission()) {
            return [];
        }
        switch ($this->getMsgtype()) {
            case Message::MSG_TYPE_NOTIFICATION:
                if ($this->getNotification()) {
                    return $this->getNotification();
                }
                $otherParams = $this->getNotify();
                break;
            case Message::MSG_TYPE_LINK;
                if ($this->getLink()) {
                    return $this->getLink();
                }
                $otherParams = $this->getLink();
                break;
            case Message::MSG_TYPE_NITYPOPLOAD:
                $otherParams = $this->getNotypopload();
                break;
            default:
                $otherParams = [];
        }
        return $otherParams;
    }

    public function getNotify()
    {
        if (!$this->getTransmission()) {
            return [];
        }
        return [
            "transmission_type" => true,
            "transmission_content" => $this->getTransmission()
        ];
    }

    public function getLink()
    {
        return ["url" => $this->getTransmission()];
    }

    public function getNotypopload()
    {
        throw new RequestException("暂时不需要");
    }

    public function getTyansmission()
    {
        $params = [];
        if ($this->getDurationBegin()) {
            $params["duration_begin"] = $this->getDurationBegin();
        }
        if ($this->getDurationEnd()) {
            $params["duration_end"] = $this->getDurationEnd();
        }
        $data['transmission'] = array_merge($this->getNotify(), $params);
        $data["push_info"] = [
            "aps" => [
                "alert" => [
                    "title" => $this->getTitle(),
                    "body" => $this->getText()
                ],
                "autoBadge" => "+1",
                "content-available" => 1
            ]
        ];
        return $data;
    }


    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->httpModel = null;
        $this->setRequestId("");
    }


    public function request()
    {
        $this->httpModel = new HttpRequest();
        if ($this->getCacheModel()) {
            $this->httpModel->setCacheModel($this->getCacheModel());
        }
        $this->httpModel = $this->httpModel->setConfig($this->getConfig())->request($this->method, $this->url, $this->getRequestBody());
    }

    public function getResult()
    {
        return $this->httpModel->getResultDataBody();
    }

    abstract function getRequestBody(): array;

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function setMethod($method = HttpRequest::METHOD_POST)
    {
        $this->method = $method;
    }

}