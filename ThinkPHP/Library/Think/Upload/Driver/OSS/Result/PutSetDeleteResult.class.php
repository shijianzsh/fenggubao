<?php

namespace Think\Upload\Driver\OSS\Result;


/**
 * Class PutSetDeleteResult
 * @package OSS\Result
 */
class PutSetDeleteResult extends Result
{
    /**
     * @return array()
     */
    protected function parseDataFromResponse()
    {
        $body = array('body' => $this->rawResponse->body);
        return array_merge($this->rawResponse->header, $body);
    }
}
