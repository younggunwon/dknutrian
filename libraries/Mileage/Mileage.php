<?php
namespace Mileage;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Storage\Storage;
use Page\Page;
use Log\Log;

class Mileage
{
	public $db;
	public $cafe24;
	public $log;

	public function __construct() {
		$this->db = new DB();
		$this->cafe24 = new Cafe24();
		$this->log = new Log();
	}

        // Cafe24 API 포인트 지급 (increase/decrease) 공통
    public function addMileage($memberId, $amount, $type, $reason = '') {
        $payload = [
            'shop_no' => 1,
            'request' => [
                'member_id' => $memberId,
                'amount' => (string)$amount,
                'type' => $type, // increase | decrease
                'reason' => $reason,
            ],
        ];
        $url = 'https://' . CAFE24_MALL_ID . '.cafe24api.com/api/v2/admin/points';
        $response = $this->cafe24->simpleCafe24Api([
            'url' => $url,
            'method' => 'POST',
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        // API 요청/응답 로그 기록 (파일 로그)
        $this->log->info('마일리지 API 요청', [
            'member_id' => $memberId,
            'amount' => $amount,
            'type' => $type,
            'reason' => $reason,
            'request' => $payload,
            'response' => $response
        ]);
		
        return [$payload, $response];
    }

    // 마일리지 로그 목록 조회 (필터링 포함)
    public function getMileageLogList($filters = []) {
        $where = [];
        
        if (isset($filters['action']) && in_array($filters['action'], ['award','resend','cancel'], true)) {
            $where[] = "action='".addslashes($filters['action'])."'";
        }
        if (isset($filters['status']) && in_array($filters['status'], ['success','failed'], true)) {
            $where[] = "status='".addslashes($filters['status'])."'";
        }
        
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';
        
        // 목록 조회 (최근순 200건)
        return $this->db->query_fetch("SELECT * FROM wg_mileageLog {$whereSql} ORDER BY id DESC LIMIT 200");
    }

    // 마일리지 로그 재전송 처리
    public function retryMileageLog($logId) {
        $rows = $this->db->query_fetch("SELECT * FROM wg_mileageLog WHERE id = {$logId}");
        $log = $rows[0] ?? null;
        
        if (!$log) {
            return ['success' => false, 'message' => '로그를 찾을 수 없습니다.'];
        }

        // 실패한 지급만 재전송 허용
        if ($log['status'] !== 'failed' || !in_array($log['action'], ['award','resend'], true)) {
            return ['success' => false, 'message' => '재전송할 수 없는 상태입니다.'];
        }

        $orderId = $log['order_id'];
        $orderItemCode = $log['order_item_code'];
        $memberId = $log['member_id'];
        $points = (float)$log['points'];
        
        $reason = '실패 재시도: ' . ($log['reason'] ?: '현금결제 추가 적립 재시도');
        list($payload, $resp) = $this->addMileage($memberId, $points, 'increase', $reason);
        $status = (isset($resp['error']) && $resp['error']) ? 'failed' : 'success';
        
        // 재전송 로그 기록
        $this->db->query("INSERT INTO wg_mileageLog(order_id, order_item_code, member_id, points, action, status, reason, request_json, response_json, regDt) VALUES('".addslashes($orderId)."', '".addslashes($orderItemCode)."', '".addslashes($memberId)."', '".$points."', 'resend', '".$status."', '".addslashes($reason)."', '".addslashes(json_encode($payload, JSON_UNESCAPED_UNICODE))."', '".addslashes(json_encode($resp, JSON_UNESCAPED_UNICODE))."', NOW())");
        
        if ($status === 'success') {
            // wg_order 기준으로 변경 (기존 wg_orderGoods 대신)
            if (!empty($orderId)) {
                $this->db->query("UPDATE wg_order SET mileage_send_flag='y' WHERE order_id='".addslashes($orderId)."'");
            }
        }
        
        return ['success' => true, 'status' => $status];
    }

    // 마일리지 로그 취소 처리 (차감)
    public function cancelMileageLog($logId) {
        $rows = $this->db->query_fetch("SELECT * FROM wg_mileageLog WHERE id = {$logId}");
        $log = $rows[0] ?? null;
        
        if (!$log) {
            return ['success' => false, 'message' => '로그를 찾을 수 없습니다.'];
        }

        // 이미 성공한 지급만 취소 허용
        if ($log['status'] !== 'success' || !in_array($log['action'], ['award','resend'], true)) {
            return ['success' => false, 'message' => '취소할 수 없는 상태입니다.'];
        }

        $orderId = $log['order_id'];
        $orderItemCode = $log['order_item_code'];
        $memberId = $log['member_id'];
        $points = (float)$log['points'];
        
        // 동일 주문에 성공 취소 이력 있으면 제한
        $dup = $this->db->query_fetch("SELECT id FROM wg_mileageLog WHERE order_id='".addslashes($orderId)."' AND action='cancel' AND status='success' LIMIT 1");
        if ($dup) {
            return ['success' => false, 'message' => '이미 취소된 주문입니다.'];
        }
        
        $reason = '현금결제 적립 취소';
        list($payload, $resp) = $this->addMileage($memberId, $points, 'decrease', $reason);
        $status = (isset($resp['error']) && $resp['error']) ? 'failed' : 'success';
        
        // 취소 로그 기록
        $this->db->query("INSERT INTO wg_mileageLog(order_id, order_item_code, member_id, points, action, status, reason, request_json, response_json, regDt) VALUES('".addslashes($orderId)."', '".addslashes($orderItemCode)."', '".addslashes($memberId)."', '".$points."', 'cancel', '".$status."', '".addslashes($reason)."', '".addslashes(json_encode($payload, JSON_UNESCAPED_UNICODE))."', '".addslashes(json_encode($resp, JSON_UNESCAPED_UNICODE))."', NOW())");
        
        if ($status === 'success') {
            // wg_order 기준으로 변경 (기존 wg_orderGoods 대신)
            if (!empty($orderId)) {
                $this->db->query("UPDATE wg_order SET mileage_send_flag='n' WHERE order_id='".addslashes($orderId)."'");
            }
        }
        
        return ['success' => true, 'status' => $status];
	}

	// 상품 혜택 재적용
	public function reapplyGoodsBenefit($sno) {
		$sql = "SELECT * FROM wg_goods_benefit_history WHERE sno = {$sno}";
		$benefit = $this->db->query_fetch($sql)[0] ?? null;

		if (!$benefit) {
			return false;
		}

		// wg_goods_benefit_history의 success_flag 검사
		if($benefit['success_flag'] == 'y') {
			return false;
		}

		// wg_orderGoods의 mileage_send_flag 검사
		if (!empty($benefit['order_id']) && !empty($benefit['order_item_code'])) {
			$sql = "SELECT mileage_send_flag FROM wg_orderGoods WHERE order_id = '".addslashes($benefit['order_id'])."' AND order_item_code = '".addslashes($benefit['order_item_code'])."' LIMIT 1";
			$orderGoods = $this->db->query_fetch($sql)[0] ?? null;
			if ($orderGoods && $orderGoods['mileage_send_flag'] == 'y') {
				return false;
			}
		}

		$memberId = $benefit['member_id'];
		$benefitValue = $benefit['benefit_value'];
		$reason = 'P0000GYJ 구매적립 재적용';

		if($benefit['benefit_type'] == 'point') {
			list($payload, $resp) = $this->addMileage($memberId, $benefitValue, 'increase', $reason);
			$status = (isset($resp['error']) && $resp['error']) ? false : true;
		} else {
			// 현재는 point만 지원
			return false;
		}

		if($status) {
			// wg_goods_benefit_history의 success_flag 업데이트
			$sql = "UPDATE wg_goods_benefit_history SET success_flag = 'y' WHERE sno = {$sno}";
			$this->db->query($sql);

			// wg_orderGoods의 mileage_send_flag 업데이트
			if (!empty($benefit['order_id']) && !empty($benefit['order_item_code'])) {
				$sql = "UPDATE wg_orderGoods SET mileage_send_flag='y' WHERE order_id='".addslashes($benefit['order_id'])."' AND order_item_code='".addslashes($benefit['order_item_code'])."'";
				$this->db->query($sql);
			}

			return true;
		} else {
			return false;
		}
	}
}