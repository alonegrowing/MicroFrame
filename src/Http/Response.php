<?php

namespace MicroFrame\Http;

use App\Headlines\Models\ErrorCode;
class Response 
{
	
	public $response;
	
	public function __construct(\Swoole\Http\Response $response) 
	{
		$this->response = $response;
	}

    /**
     * 输出成功的Json
     * @param array $data
     * @param string $msg
     * @return string
     */
	public function renderSuccess($data = array(), $msg = '') 
	{
		$result = [
				'dm_error' => ErrorCode::SUCCESS,
				'error_msg' => ErrorCode::$ERRMSG[ErrorCode::SUCCESS],
				'data' => $data,
		];
		if (!empty($msg)) {
			$result['error_msg'] = $msg;
		}
		$this->response->end(json_encode($result));
	}
	
	/**
	 * 输出失败的Json
	 * @param int $code
	 * @param array $data
	 * @param string $msg
	 * @return string
	 */
	public function renderError($code,  $msg = '', $data = array()) 
	{
		$result = [
				'dm_error' => $code,
				'error_msg' => ErrorCode::$ERRMSG[$code],
				'data' => $data,
		];
		if (!empty($msg)) {
			$result['error_msg'] = $msg;
		}
		$this->response->end(json_encode($result));
	}

	public function showError( $data = array()) {
        $result = [
            'dm_error' => ErrorCode::NO_HANDLER,
            'error_msg' => ErrorCode::$ERRMSG[ErrorCode::NO_HANDLER],
            'data' => $data,
        ];
        if (!empty($msg)) {
            $result['error_msg'] = $msg;
        }
        $this->response->end(json_encode($result));
    }
}