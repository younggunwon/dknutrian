<?php

namespace Database;

class DB
{
	private $db;
	private $errorMessage;
	private $isConnected;
	public $queryCnt;

	public function __construct()
	{
		# 추후 mysqli 에러 발생시 리포트 확인용
		//mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
		try {
			$this->db = new \mysqli("localhost", DB_USER, DB_PASSWORD, DB_USER);
			$this->isConnected = true;
		} catch(Exception $e) {
			throw $e;
		}
	}

	public function query($sql) {
		$this->is_connected();
		$this->result = $this->db->query($sql);
		$this->queryCnt++;
		return $this->result;
	}

	public function query_fetch($sql) {
		$getData = [];
		$result = $this->query($sql);
		while ($data = $this->fetch($result)) {
			$getData[] = $data;
		}

		return gd_isset($getData, []);
	}

	/**
     * mysqli_result 데이터 결과 행을 반환
     * $type : assoc - 연관 배열처리 , 기본값
     * $type : array - 연관 색인 및 숫자 색인으로 된 배열
     * $type : object - 객체형으로 처리
     * $type : row - 숫자 색인 배열
     * $type : field - 컬럼 정보를 얻어서 객체형태 반환
     *
     * @param string $result resource result or query
     * @param string $type   type (option : assoc,array,object,row,field) (기본 assoc)
     *
     * @return object|mixed 반환된 값
     */
    public function fetch($result, $type = 'assoc')
    {
        // 디비 접속 여부 확인
        $this->is_connected();

		if (!is_object($result)) {
			return [];
		}

        if ($type == 'assoc') {
            return $result->fetch_assoc();
        }
        if ($type == 'array') {
            return $result->fetch_array(MYSQLI_BOTH);
        }
        if ($type == 'object') {
            return $result->fetch_object();
        }
        if ($type == 'row') {
            return $result->fetch_row();
        }
        if ($type == 'field') {
            return $result->fetch_field();
        }
    }

	/**
     * MySqli 디비에 접속여부를 확인함
     */
    public function is_connected()
    {
        if (!$this->isConnected) {
			throw new Exception('DB가 연결되지 않았습니다.');
        }
    }

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function insert_id(){
		$this->is_connected();
		$this->result = $this->db->insert_id;
		return $this->result;
	}

}

?>