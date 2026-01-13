<?php
namespace Coupon;

use Database\DB;
use Cafe24\Cafe24;
use Log\Log;

class Coupon
{
	public $db;

	public function __construct() {
		$this->db = new DB();
	}
	
	public function getCouponInfo() {
        $cafe24 = new Cafe24();
        $accessToken = $cafe24->getToken();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://'.CAFE24_MALL_ID.'.cafe24api.com/api/v2/admin/coupons',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$accessToken,
                'Content-Type: application/json',
                'X-Cafe24-Api-Version: '.CAFE24_API_VERSION
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
            echo 'cURL Error #:' . $err;
        } else {
            $response = json_decode($response, 1);
        }

        foreach($response['coupons'] as $key => $val) {
            $coupon[$key]['coupon_name'] = $val['coupon_name'];
            $coupon[$key]['coupon_no'] = $val['coupon_no'];
        }

        return $coupon;
    }

    public function addCoupon($member_id, $coupon_code) {
        $cafe24 = new Cafe24();
        $accessToken = $cafe24->getToken();

        // Cafe24 API 요청 데이터 준비
        $data = json_encode([
            "shop_no" => 1,
            "request" => [
                "issued_member_scope" => "M",
                "member_id" => $member_id,
                "allow_duplication" => "T",
                "single_issue_per_once" => "T",
                "issued_by_event_type" => 'C',
            ]
        ]);
        
        // Cafe24 API 호출을 위한 설정
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://'.CAFE24_MALL_ID.'.cafe24api.com/api/v2/admin/coupons/'.$coupon_code.'/issues',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$accessToken,
                'Content-Type: application/json',
                'X-Cafe24-Api-Version: ' .CAFE24_API_VERSION
            ],
        ]);
        
        // API 요청 실행
        $response = curl_exec($curl);
        $response = json_decode($response, 1);

        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($err || $httpCode >= 400 || !$response['issues']) {
            $logger = new Log();
            $logger->error('카페24 API 요청 실패', ['request' => $data, 'error_code' => $httpCode, 'result' => $response, 'error_message' => $err]);
            return false;
        }

        sleep(1);
        
        return true;
    }
}