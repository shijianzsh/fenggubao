<?php
/**
 * 广发银企直联 异常类
 */
class CgbPayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
