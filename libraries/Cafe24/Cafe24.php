<?php
namespace Cafe24;

use Database\DB;
use Log\Log;

class Cafe24
{
	public $db;
	public $clientId;
	public $clientSecret;
	public $redirectUri;
	public $mallId;
	public $version;

	public function __construct() {
		// 인증코드 요청 URL
		// https://{mall_id}.cafe24api.com/api/v2/oauth/authorize?response_type=code&client_id={client_id}&state={encode_csrf_token}&redirect_uri={encode_redirect_uri}&scope={scope}

		
		//https://dknutrition.cafe24.com/disp/common/oauth/authorize?response_type=code&client_id=rQfkxGAOEKTaTGbA4Q2MTG&state=&redirect_uri=https://dknutri02.mycafe24.com/api/redirect.php&scope=mall.read_application,mall.write_application,mall.read_privacy,mall.read_store,mall.write_store
		$this->db = new DB();
		$this->logger = new Log();
		
		# 클라이언트 정보 저장 후 DB 정보도 수정하기 /libraries/Database/DB.php
	}
	
	public function connectNewMall() {
		if($_GET['code']) {
			$authorization_code = $_GET['code'];
			$auth_header = base64_encode(CAFE24_CLIENT_ID.":".CAFE24_CLIENT_SECRET);

			$param = [];
			$param['url'] = "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/oauth/token";
			$param['header'] = array(
				"Authorization: Basic {$auth_header}",
				"Content-Type: application/x-www-form-urlencoded"
			);
			
			$param['data'] = http_build_query(array(
				'grant_type' => 'authorization_code',
				'code' => $authorization_code,
				'redirect_uri' => CAFE24_REDIRECT_URL
			));

			gd_debug($param);

			$result = $this->simpleCurl($param);
			$result = json_decode($result, 1);
			gd_debug($result);

			//$result['access_token'] = 'OvkU37nQIp0iSdvfECfKlF';
			//$result['refresh_token'] = 'lK1hmDiXNBikHaOIeqDQyH';
			//$result['expires_at'] = '2023-12-20T13:32:47.000';
			//$result['refresh_token_expires_at'] = '2024-01-03T11:32:47.000';

			# 토큰 저장 및 유효기간 저장하여 추후 사용 access_token refresh_token expires_at
			$result['expires_at'] = gd_date_format('Y-m-d H:i:s', $result['expires_at']);
			$result['refresh_token_expires_at'] = gd_date_format('Y-m-d H:i:s', $result['refresh_token_expires_at']);

			// wg_cafe24Token 테이블에 이미 값이 있으면 update, 없으면 insert 하도록 분기
			$sqlChk = "SELECT COUNT(*) as cnt FROM wg_cafe24Token WHERE mallId = '".CAFE24_MALL_ID."' AND clientId = '".CAFE24_CLIENT_ID."'";
			$rowChk = $this->db->query($sqlChk)->fetch_assoc();
			if ($rowChk['cnt'] > 0) {
				// update
				$sql = "
					UPDATE wg_cafe24Token
					SET accessToken = '".$result['access_token']."',
						refreshToken = '".$result['refresh_token']."',
						accessTokenExpiresDt = '".$result['expires_at']."',
						refreshTokenExpiresDt = '".$result['refresh_token_expires_at']."'
					WHERE mallId = '".CAFE24_MALL_ID."' AND clientId = '".CAFE24_CLIENT_ID."'
				";
			} else {
				// insert
				$sql = "INSERT INTO wg_cafe24Token(mallId, clientId, accessToken, refreshToken, accessTokenExpiresDt, refreshTokenExpiresDt) VALUES('".CAFE24_MALL_ID."', '".CAFE24_CLIENT_ID."', '".$result['access_token']."', '".$result['refresh_token']."', '".$result['expires_at']."', '".$result['refresh_token_expires_at']."')";
			}
			$this->db->query($sql);
			gd_debug($sql);
		}
	}

	public function setScript() {
		$accessToken = $this->getToken();
		
		// 샵 조회 (스킨번호 조회를 위해)
		// $response = $this->simpleCafe24Api([
		// 	'url' => 'https://'.CAFE24_MALL_ID.'.cafe24api.com/api/v2/admin/shops',
		// 	'method' => 'GET',
		// ], true);
		// gd_debug($response);
		// exit;

		// # 스크립트 조회 (스크립트 삭제 or 업데이트를 위해)
		// $response = $this->simpleCafe24Api([
		// 	'url' => 'https://'.CAFE24_MALL_ID.'.cafe24api.com/api/v2/admin/scripttags',
		// 	'method' => 'GET',
		// ], true);
		// gd_debug($response);
		// exit;
		
		// # 조회된 스크립트 삭제(다시 업데이트하기 위해)
		// foreach($response['scripttags'] as $key => $val) {
		// 	$result = $this->simpleCafe24Api([
		// 		'url' => "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/admin/scripttags/".$val['script_no'],
		// 		'method' => 'DELETE',
		// 	], true);
		// 	gd_debug($result);
		// }
		// exit;

		#script 등록
		$src = "https://dknutri02.mycafe24.com/js/discount.js";
		$response = $this->simpleCafe24Api([
			'url' => "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/admin/scripttags",
			'method' => 'POST',
			'data' => json_encode(array(
				"shop_no" => 1,
				"request" => array(
					"src" => $src,
					"display_location" => ["ORDER_ORDERFORM"],
					"skin_no" => [2,3],
					"integrity" => "", 
					// ingegrity는 https://www.srihash.org/ 사이트에서 들어간 스크립트 src 넣고 sha384로 변환하면 됨 
					// ex) https://2018salt.com/data/skin/front/wg230920/cafe24/testScript.js?vs=20231103181226.1 
					//  => sha384-jF+nGn0/4PaFcrzJ5g04+MDLqVefnOjJrgEZ3hAt9lhqGxaeOMGcPTAi51zs1v4G
				)
			))
		], true);
		gd_debug($response);
		exit;
	}

	public function simpleCurl($param = []) {
		$ch = curl_init($param['url']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $param['header']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;

	}

	public function getToken() {
		$sql = "SELECT * FROM wg_cafe24Token WHERE mallId = '".CAFE24_MALL_ID."' AND clientId = '".CAFE24_CLIENT_ID."'";
		$tokenInfo = $this->db->query_fetch($sql)[0];
		
		$accessToken = $tokenInfo['accessToken'];
		$refreshToken =  $tokenInfo['refreshToken'];
		
		# 리프레시 토큰으로 새 토큰 발급하여 새로운 토큰과 새로운 리프레시 토큰 저장
		if($tokenInfo['accessTokenExpiresDt'] <= date('Y-m-d H:i:s')) {
			$auth_header = base64_encode(CAFE24_CLIENT_ID.":".CAFE24_CLIENT_SECRET);

			$param = [];
			$param['url'] = "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/oauth/token";
			$param['header'] = array(
				"Authorization: Basic {$auth_header}",
				"Content-Type: application/x-www-form-urlencoded"
			);
			
			$param['data'] = http_build_query(array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refreshToken,
			));

			//gd_debug($param);

			$result = $this->simpleCurl($param);
			//gd_debug($result);
			$result = json_decode($result, 1);

			$result['expires_at'] = gd_date_format('Y-m-d H:i:s', $result['expires_at']);
			$result['refresh_token_expires_at'] = gd_date_format('Y-m-d H:i:s', $result['refresh_token_expires_at']);
			
			if($result['access_token'] && $result['refresh_token']) {
				$sql = "
					UPDATE wg_cafe24Token
					SET accessToken = '".addslashes($result['access_token'])."',
					refreshToken = '".addslashes($result['refresh_token'])."',
					accessTokenExpiresDt = '".$result['expires_at']."',
					refreshTokenExpiresDt = '".$result['refresh_token_expires_at']."'
					WHERE mallId = '".CAFE24_MALL_ID."'
					AND clientId = '".CAFE24_CLIENT_ID."'
				";
				$this->db->query($sql);
			}

			$accessToken = $result['access_token'];
			$refreshToken =  $result['refresh_token'];
			//gd_debug($sql);

			$sql = "SELECT * FROM wg_cafe24Token WHERE mallId = '".CAFE24_MALL_ID."' AND clientId = '".CAFE24_CLIENT_ID."'";
			$tokenInfo = $this->db->query_fetch($sql)[0];
			//gd_debug($tokenInfo);	
		}

		return $accessToken;
	}

	public function simpleCafe24Api($param = [], $debug = false) {
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $this->getToken();
		$version = CAFE24_API_VERSION;
		
		// 디버그 모드일 때 요청 정보 출력
		if($debug) {
			gd_debug("=== 카페24 API 요청 디버그 ===");
			gd_debug("URL: " . $param['url']);
			gd_debug("Method: " . $param['method']);
			gd_debug("Request Data: " . $param['data']);
			gd_debug("Access Token: " . substr($access_token, 0, 20) . "...");
		}
		
		$url = $param['url'];
		$ch = curl_init($url);

		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
			'X-Cafe24-Api-Version: ' . $version
		);

		if($debug) {
			gd_debug("Headers: " . print_r($headers, true));
		}

		// CURL 옵션 설정 순서 변경 및 디버그 추가
		if($debug) {
			gd_debug("CURL 설정 전 데이터 확인:");
			gd_debug("Method: " . $param['method']);
			gd_debug("Data length: " . strlen($param['data']));
			gd_debug("Data content: " . $param['data']);
		}
		
		// Content-Length 헤더 추가
		if($param['data'] && in_array($param['method'], ['POST', 'PUT'])) {
			$headers[] = 'Content-Length: ' . strlen($param['data']);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if($param['data']) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
		}

		if($param['method'] == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		}

		if($param['method'] == 'PUT') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		}

		if($param['method'] == 'DELETE') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}

		if (!defined('CURLINFO_CUSTOMREQUEST')) {
			define('CURLINFO_CUSTOMREQUEST', 1048671);
		}
		if (!defined('CURLINFO_POSTFIELDS')) {
			define('CURLINFO_POSTFIELDS', 1048687);
		}
		// 혹시 모를 다음 에러 방지용
		if (!defined('CURLINFO_CONTENT_TYPE')) {
			define('CURLINFO_CONTENT_TYPE', 1048594);
		}

		// 추가 CURL 옵션 설정
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		
		// CURL 요청 실행 전 최종 확인
		if($debug) {
			gd_debug("=== CURL 실행 전 최종 설정 확인 ===");
			gd_debug("URL: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
			gd_debug("Method: " . curl_getinfo($ch, \CURLINFO_CUSTOMREQUEST));
			gd_debug("Post fields: " . curl_getinfo($ch, CURLINFO_POSTFIELDS));
		}
		
		$response = curl_exec($ch);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $headerSize);
		// X-Api-Call-Limit 헤더에서 현재 API 호출 횟수와 최대 허용 횟수 추출
		$apiCallLimit = 0;
		$apiCallLimitMax = 0;
		if (preg_match('/X-Api-Call-Limit:\s*(\d+)\/(\d+)/', $header, $matches)) {
			$apiCallLimit = (int)$matches[1];
			$apiCallLimitMax = (int)$matches[2];
		}
		
		// API 호출 한도 초과 시 로그 남기기
		if ($apiCallLimit > 0 && $apiCallLimitMax > 0 && $apiCallLimit >= $apiCallLimitMax) {
			$this->logger->error('카페24 API 호출 한도 초과', ['request' => $param['data'], 'error_code' => $httpCode, 'result' => $response, 'error_message' => $err]);
			return false;
		}

		$body = substr($response, $headerSize);
		$response = json_decode($body, 1);
		$err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if($debug) {
			gd_debug("=== 카페24 API 응답 디버그 ===");
			gd_debug("HTTP Code: " . $httpCode);
			gd_debug("Response Headers: " . $header);
			gd_debug("Response Body: " . $body);
			gd_debug("Decoded Response: " . print_r($response, true));
			gd_debug("CURL Error: " . $err);
		}
		
        if ($err || $httpCode >= 400 || $response['error']) {
            $this->logger->error('카페24 API 요청 실패', ['url' => $url, 'request' => $param['data'], 'error_code' => $httpCode, 'result' => $response, 'error_message' => $err]);
			sleep(1);
			
            return false;
        }

		
		sleep(1);

		return $response;
	}
}