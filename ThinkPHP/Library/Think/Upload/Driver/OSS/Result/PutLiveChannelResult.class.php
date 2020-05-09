<?php

namespace Think\Upload\Driver\OSS\Result;

use Think\Upload\Driver\OSS\Model\LiveChannelInfo;

class PutLiveChannelResult extends Result
{
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $channel = new LiveChannelInfo();
        $channel->parseFromXml($content);
        return $channel;
    }
}
