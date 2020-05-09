<?php

namespace Think\Upload\Driver\OSS\Result;

use Think\Upload\Driver\OSS\Core\OssException;

/**
 * Class AppendResult
 * @package OSS\Result
 */
class AppendResult extends Result
{
    /**
     * 结果中part的next-append-position
     *
     * @return int
     * @throws OssException
     */
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["x-oss-next-append-position"])) {
            return intval($header["x-oss-next-append-position"]);
        }
        throw new OssException("cannot get next-append-position");
    }
}