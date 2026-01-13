<?php
namespace Discount;

use Database\DB;
use EventMileage\EventMileage;

class Discount
{
	public $db;
    public $traceNo;
    public $testMode;
    public $testIp;

	public function __construct() {
		$this->db = new DB(); 
        $this->traceNo = $this->makeTraceNo();
        $this->testMode = true;
        $this->testIp = ['58.124.137.93', '121.160.183.187', '125.131.134.1'];

        if($this->testMode) {
            if(!in_array($_SERVER['REMOTE_ADDR'], $this->testIp)) {
                exit;
            }
        } else {
            
        }
	}

    // --------------------------------
    // 임의의 Trace 번호 생성 함수
    public function makeTraceNo() {
        return uniqid('trace_', true);
    }

    public function setDiscount() {
        $return = [
            'code' => 200,
            'message' => 'OK',
            'data' => null
        ];

        try {
            $rawData = file_get_contents("php://input");
            $data = json_decode($rawData, true);
            $orderInfo = $data;

            // 주문 처리
            $respOrderMap = $this->doSale($orderInfo, $this->traceNo);

            if (!empty($orderInfo['member_id'])) {
                $respOrderMap['guest_key'] = $this->getEncMD5($orderInfo['member_id']);
            } else {
                $respOrderMap['guest_key'] = $orderInfo['guest_key'];
            }

            // 할인이 0원(1e-19)이어도 성공 사례에 따라 무조건 HMAC 생성
            $respOrderMap['hmac'] = $this->getHmac($respOrderMap);

            // guest_key 제거
            unset($respOrderMap['guest_key']);

            $return['data'] = $respOrderMap;

        } catch (\Exception $e) {
            $return['code'] = 400;
            $return['message'] = $e->getMessage();
        }

        // JSON 응답 (성공 사례는 숫자를 소수점으로 표현하므로 최대한 근접하게)
        echo json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    // --------------------------------
    // 주문 처리 모의 함수
    function doSale($orderInfo, $trace_no) {
        $requestedDiscountAmount = (int)($orderInfo['use_event_mileage'] ?? 0);
        
        $totalProductPrice = 0;
        foreach ($orderInfo['product'] as $product) {
            $price = (float)($product['discount_price'] ?? 0);
            $optionPrice = (float)($product['option_price'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 0);
            $totalProductPrice += ($price + $optionPrice) * $quantity;
        }

        // 이벤트 마일리지 사용가능금액 확인
        $totalDiscountAmount = 0;
        if (!empty($orderInfo['member_id'])) {
            $campaign = new EventMileage();
            $maxUsableMileage = $campaign->getUsableEventMileage($orderInfo['member_id'], $totalProductPrice);
            
            if($requestedDiscountAmount > $maxUsableMileage) {
                throw new \Exception('이벤트 마일리지 사용가능금액이 부족합니다.');
            }
            $totalDiscountAmount = min($requestedDiscountAmount, $maxUsableMileage);
        }

        // 실질적인 할인액 계산 (0원일 때는 성공 사례의 킥인 1e-19 사용)
        $effectiveAmount = ($totalDiscountAmount > 0) ? $totalDiscountAmount : 1e-19;

        // 결과 배열 구성 (성공 사례 필드 순서와 구성 준수)
        $return = [];
        $return["mall_id"] = $orderInfo["mall_id"];
        $return["shop_no"] = (int)$orderInfo["shop_no"];
        $return["member_id"] = $orderInfo["member_id"];
        $return["member_group_no"] = (int)$orderInfo["member_group_no"];

        // product_discount 생성
        $discountUnit = 'I';
        $distributedAmount = 0;
        $productCount = count($orderInfo['product']);
        $product_discount = [];

        foreach($orderInfo['product'] as $key => $val) {
            $price = (float)($val['discount_price'] ?? 0);
            $optionPrice = (float)($val['option_price'] ?? 0);
            $quantity = (int)($val['quantity'] ?? 0);
            $itemPrice = ($price + $optionPrice) * $quantity;

            // 금액 분배 (단, 1e-19일 때는 아주 작은 값으로 분배되거나 첫 번째에 몰아줌)
            if ($totalDiscountAmount > 0) {
                if ($key < $productCount - 1 && $totalProductPrice > 0) {
                    $itemDiscount = (int)floor($totalDiscountAmount * ($itemPrice / $totalProductPrice));
                    $distributedAmount += $itemDiscount;
                } else {
                    $itemDiscount = $totalDiscountAmount - $distributedAmount;
                }
            } else {
                // 할인이 0원일 때는 첫 번째 상품에 1e-19를 주고 나머지는 0 (또는 모두 1e-19)
                $itemDiscount = ($key === 0) ? 1e-19 : 0;
            }

            $item = $val; // 원본 필드 복사
            $item["app_quantity_based_discount"] = 0;
            $item["app_non_quantity_based_discount"] = $itemDiscount;
            $item["app_product_discount_info"] = [[
                "no" => 100, // 성공 사례에 명시된 고정 번호
                "discount_unit" => $discountUnit,
                "use_coupon_simultaneously" => "T",
                "price" => $itemDiscount
            ]];
            $product_discount[] = $item;
        }

        $return["product_discount"] = $product_discount;
        $return["order_discount"] = [];
        
        // app_discount_info 구성
        $return["app_discount_info"] = [[
            "no" => 100,
            "type" => "P",
            "name" => "쇼핑지원금 할인",
            "icon" => "http://via.placeholder.com/32x32",
            "config" => [
                "value" => ($totalDiscountAmount > 0) ? $totalDiscountAmount : 1, // 0원일 때 1로 보내는 트릭 여부 확인
                "value_type" => "W",
                "discount_unit" => $discountUnit
            ]
        ]];

        $return["time"] = time();
        $return["trace_no"] = $trace_no;
        $return["app_key"] = CAFE24_CLIENT_ID;

        return $return;
    }

    function getHmac($data) {
        $plainText = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $algorithm = 'sha256';
        $secretKey = CAFE24_SERVICE_KEY;
        return base64_encode(hash_hmac($algorithm, $plainText, $secretKey, true));
    }

    function getEncMD5($input) {
        return md5($input);
    }
}
