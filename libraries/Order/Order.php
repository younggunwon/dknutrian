<?php
namespace Order;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Storage\Storage;
use Page\Page;
use Exception;
use Member\Member;
use Mileage\Mileage;
use DateTime;
use Log\Log;
class Order
{

	public $db;
	public $accessToken;
	public $apiKey;
	public $cafe24;
	public $log;


	public function __construct()
	{
		$this->cafe24   = new Cafe24();
		$this->accessToken = $this->cafe24->getToken();

		$this->db = new DB();
		$this->log = new Log();
		$this->apiKey = 'eaaebaf13dd39e5c410cc1b97848546148650455491bc83eeb35343d23b56331';
	}

	public function updateOrder()
	{
		ini_set('memory_limit', '512M');

		$member = new Member();
		$sql = "INSERT INTO wg_apiLog(apiType, requestData, responseData) VALUES('updateOrder', 'start', '')";
		$this->db->query($sql);

		$orderData = [];
		$today = date('Y-m-d');
		// 2개월 전부터 시작 (각 달의 1일)
		$current_start_date = date('Y-m-01', strtotime('first day of -1 day'));
		
		// 중복확인을 위한 데이터 가져오기
		$sql = " SELECT order_item_code, order_id, order_status, sno FROM wg_orderGoods ";
		$orderGoods = $this->db->query_fetch($sql);
		foreach($orderGoods as $goods) {
			$oldOrderGoods[$goods['order_id']][$goods['order_item_code']]['status'] = $goods['order_status'];
			$oldOrderGoods[$goods['order_id']][$goods['order_item_code']]['sno'] = $goods['sno'];
		}
		unset($orderGoods);

		$sql = " SELECT order_id FROM wg_order ";
		$order = $this->db->query_fetch($sql);
		foreach($order as $o) {
			$oldOrder[$o['order_id']] = true;
		}
		unset($order);
		

		while (strtotime($current_start_date) < strtotime($today)) {
			// 한 달씩만 조회 (시작일이 속한 달의 마지막 날까지)
			$current_end_date = date('Y-m-t', strtotime($current_start_date));
			
			// 오늘 날짜를 넘지 않도록 제한
			if (strtotime($current_end_date) > strtotime($today)) {
				$current_end_date = $today;
			}

			foreach (range(0, 1000) as $val) {
				// 주문상품
				
				$offset = $val * 10;
				$url = "https://" . CAFE24_MALL_ID . ".cafe24api.com/api/v2/admin/orders?start_date={$current_start_date}&end_date={$current_end_date}&limit=10&offset={$offset}&embed=items,buyer";
				$response = $this->cafe24->simpleCafe24Api([
					'url' => $url,
					'method' => 'GET'
				]);
				
				/* gd_debug(count($response['orders'])); */

				if(!isset($response['orders']) || !count($response['orders'])) {
					 //gd_debug($response);
					break;
				}

				if(isset($response['orders'])) {
					foreach ($response['orders'] as $orderData) {
						$paymentDate = null; // 매 주문마다 초기화
						if($orderData['payment_date']) {
							$paymentDate = (new \DateTime($orderData['payment_date']))->format('Y-m-d H:i:s');
						}

						// 원본 아이템 데이터를 보관 (중복 인코딩 방지용)
						$originalItems = $orderData['items'] ?? [];

						$arrData = [
							'order_id' => $orderData['order_id'] ?? null,
							'member_id' => $orderData['member_id'] ?? null,
							'items' => $orderData['items'] ?? null,
							'payment_date' => $paymentDate ?? null,
						];

						$fieldsToJson = ['additional_option_values', 'original_item_no', 'options'];
						
						foreach($arrData['items'] as $key => $item) {
						foreach ($fieldsToJson as $field) {
							if (isset($item[$field]) && is_array($item[$field])) {
								$arrData['items'][$key][$field] = json_encode($item[$field], JSON_UNESCAPED_UNICODE);
							}
						}
						// 테이블 필드 정보 가져오기 (루프 밖으로 이동 가능하면 더 좋지만, 현재 구조를 유지하며 최적화)
						static $tableFields = null;
						if ($tableFields === null) {
							$sql = "DESCRIBE wg_orderGoods";
							$tableFields = $this->db->query_fetch($sql);
						}

						$fieldNames = array_column($tableFields, 'Field');
						$fieldTypes = array_column($tableFields, 'Type', 'Field');
						$fieldNulls = array_column($tableFields, 'Null', 'Field');
						$fieldDefaults = array_column($tableFields, 'Default', 'Field');

						$arrWhere = [];
						$order_item_code = '';

						// 이미 쿼리에서 직접 지정하고 있는 필드들은 자동 생성 목록에서 제외
						$excludeFields = ['order_id', 'member_id', 'regDt', 'modDt'];

						foreach ($tableFields as $f) {
							$fn = $f['Field'];
							
							if (in_array($fn, $excludeFields)) continue;

							$ft = strtolower($f['Type']);
							$fv = $arrData['items'][$key][$fn] ?? null;

							// 배열 데이터인 경우 JSON 문자열로 변환
							if (is_array($fv)) {
								$fv = json_encode($fv, JSON_UNESCAPED_UNICODE);
							}

							// 특정 필드 예외 처리
							if ($fn == 'purchaseconfirmation_date') {
								$fv = $paymentDate;
							}

							if ($fn == 'order_item_code') {
								$order_item_code = $fv;
							}

							// 날짜 형식 변환 (ISO 8601 -> MySQL format)
							if ($fv && is_string($fv) && (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false)) {
								if (strpos($fv, 'T') !== false) {
									try {
										$fv = (new \DateTime($fv))->format('Y-m-d H:i:s');
									} catch (\Exception $e) {
										// 변환 실패 시 그대로 유지
									}
								}
							}

							// 값이 없을 때 처리
							if ($fv === null || $fv === '') {
								// 숫자형(decimal, int, float 등)인 경우 0으로 처리
								if (strpos($ft, 'decimal') !== false || strpos($ft, 'int') !== false || strpos($ft, 'float') !== false || strpos($ft, 'double') !== false) {
									$fv = 0;
								} elseif (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false || strpos($ft, 'date') !== false) {
									// 날짜/시간 타입인 경우 NULL 허용 여부에 따라 처리
									if ($fieldNulls[$fn] == 'YES') {
										$fv = null;
									} else {
										$fv = '0000-00-00 00:00:00';
									}
								} else {
									$fv = '';
								}
							}

							// 쿼리 조립
							if (isset($arrData['items'][$key][$fn]) || $fn == 'purchaseconfirmation_date' || ($f['Null'] == 'NO' && $f['Default'] === null)) {
								if ($fv === null) {
									$arrWhere[] = $fn . " = NULL";
								} else {
									$arrWhere[] = $fn . " = " . "'" . addslashes($fv) . "'";
								}
							}
						}

						$strWhere = implode(', ', $arrWhere);


							$isDifferent = false;
							
						
					
							$existingData = $oldOrderGoods[$arrData['order_id']] ?? [];
							$existingStatus = $existingData[$item['order_item_code']]['status'] ?? null;
							if($existingStatus != $item['order_status']) {
								$isDifferent = true;
							}
							
							if($existingStatus) {
								if($isDifferent) {
									// update
									$sql = "
										UPDATE wg_orderGoods SET 
										order_id = '" . $arrData['order_id'] . "',
										member_id = '" . $arrData['member_id'] . "',
										order_status = '".$item['order_status']."',
										status_code = '".$item['status_code']."',
										status_text = '".$item['status_text']."',
										modDt = now(),
										purchaseconfirmation_date = " . ($arrData['payment_date'] ? "'" . $arrData['payment_date'] . "'" : "NULL") . "
										WHERE sno = ".$existingData[$item['order_item_code']]['sno']." 
										AND order_id = '" . $arrData['order_id'] . "'
										AND order_item_code = '".$item['order_item_code']."'
									";
									$this->db->query($sql);
								}
							} else {
								// insert
								$sql = "
									INSERT wg_orderGoods SET 
									order_id = '" . $arrData['order_id'] . "',
									member_id = '" . $arrData['member_id'] . "',
									".$strWhere.",
									regDt = now()
									
								";
								$this->db->query($sql);
							}
							
							// 티켓 관련 로직 제거됨
							
						}
					}
					
				}

				if (isset($response['orders'])) {
					foreach ($response['orders'] as $key => $orderData) {
						$currentData = isset($oldOrder[$orderData['order_id']]);
						
						$orderData['shipping_fee_detail'] = json_encode($orderData['shipping_fee_detail']);
						$payment_method = implode('||', $orderData['payment_method']);

						// 추가 정보 추출 (쇼핑지원금, 총상품금액)
						$use_event_mileage = 0;
						$total_goods_price = 0;
						if (isset($orderData['additional_order_info_list']) && is_array($orderData['additional_order_info_list'])) {
							foreach ($orderData['additional_order_info_list'] as $info) {
								if (trim($info['name']) == '쇼핑지원금') {
									$use_event_mileage = (int)str_replace(',', '', $info['value']);
								} elseif (trim($info['name']) == '총상품금액') {
									$total_goods_price = (int)str_replace(',', '', $info['value']);
								}
							}
						}

						// wg_order 테이블 필드 정보 가져오기
						static $orderTableFields = null;
						if ($orderTableFields === null) {
							$sql = "DESCRIBE wg_order";
							$orderTableFields = $this->db->query_fetch($sql);
						}

						$orderFieldNames = array_column($orderTableFields, 'Field');
						$orderFieldTypes = array_column($orderTableFields, 'Type', 'Field');

						// 데이터 매핑 및 보정
						$mappedData = [
							'order_id' => $orderData['order_id'],
							'member_id' => $orderData['member_id'],
							'shipping_status' => $orderData['shipping_status'],
							'payment_amount' => $orderData['actual_order_amount']['payment_amount'] ?? 0,
							'shipping_fee_detail' => $orderData['shipping_fee_detail'],
							'items' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', json_encode($originalItems, JSON_UNESCAPED_UNICODE)),
							'order_date' => $orderData['order_date'],
							'payment_date' => $orderData['payment_date'],
							'points_spent_amount' => $orderData['actual_order_amount']['points_spent_amount'] ?? 0,
							'payment_method' => $payment_method,
							'bank_code' => $orderData['bank_code'] ?? '',
							'bank_account_no' => $orderData['bank_account_no'] ?? '',
							'bank_account_owner_name' => $orderData['bank_account_owner_name'] ?? '',
							'initial_order_amount' => $orderData['actual_order_amount']['order_price_amount'] ?? 0,
							'use_event_mileage' => $use_event_mileage,
							'total_goods_price' => $total_goods_price,
							'actual_order_amount' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', json_encode($orderData['actual_order_amount'] ?? [], JSON_UNESCAPED_UNICODE)),
						];

						$updateParts = [];
						$insertCols = [];
						$insertVals = [];

						foreach ($orderTableFields as $f) {
							$fn = $f['Field'];
							if ($fn == 'sno' || $fn == 'regDt') continue;
							
							$fv = $mappedData[$fn] ?? null;
							$ft = strtolower($f['Type']);

							// 날짜 형식 변환
							if ($fv && (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false)) {
								if (strpos($fv, 'T') !== false) {
									try { $fv = (new \DateTime($fv))->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
								}
							}

							// 값이 없을 때 보정 (필수 필드인데 기본값이 없는 경우)
							$is_null = false;
							$allows_null = (strtoupper($f['Null']) == 'YES');

							if ($fv === null || $fv === '') {
								if (strpos($ft, 'decimal') !== false || strpos($ft, 'int') !== false || strpos($ft, 'float') !== false) {
									$fv = 0;
								} elseif (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false) {
									if ($allows_null) {
										$fv = null;
										$is_null = true;
									} else {
										$fv = '0000-00-00 00:00:00';
									}
								} else {
									$fv = '';
								}
							}

							// 쿼리에 포함할지 결정: 매핑된 데이터가 있거나, 필수 필드인 경우
							if (isset($mappedData[$fn]) || ($f['Null'] == 'NO' && $f['Default'] === null)) {
								$escapedFv = $is_null ? "NULL" : "'" . addslashes($fv) . "'";
								$updateParts[] = "{$fn} = {$escapedFv}";
								$insertCols[] = $fn;
								$insertVals[] = $escapedFv;
							}
						}

						if ($currentData) {
							$sql = "UPDATE wg_order SET " . implode(', ', $updateParts) . " WHERE order_id = '{$orderData['order_id']}';";
							$this->db->query($sql);
						} else {
							$sql = "INSERT INTO wg_order (" . implode(', ', $insertCols) . ", regDt) VALUES (" . implode(', ', $insertVals) . ", now());";
							$this->db->query($sql);
							gd_debug($sql);
						}
					}
				}
			}

			$current_start_date = date('Y-m-d', strtotime('+1 day', strtotime($current_end_date)));
		}
		
		//$this->awardCashPaymentMileage();
	}
	
	public function updateOrderOne() {
		// API Key 인증 체크
		$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
		if ($apiKey !== '23c92e8d-778e-4600-bccb-50eb58d556bf') {
			header('HTTP/1.1 401 Unauthorized');
			$this->log->error('Unauthorized webhook access', ['received_key' => $apiKey]);
			echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
			exit;
		}

		// 웹훅 데이터 수신
		$rawData = file_get_contents('php://input');
		$data = json_decode($rawData, true);

		// 웹훅 수신 데이터 로그 기록
		$this->log->info('updateOrderOne webhook raw data', ['rawData' => $rawData, 'decodedData' => $data]);

		sleep(10); // 주문 변경 완료될때까지 대기
		
		if (gd_isset($data['resource']['order_id'])) {
					
			$cafe24 = new Cafe24();
			$access_token = $cafe24->getToken();

			// 주문상품 저장
			$param['url'] = "https://" . CAFE24_MALL_ID . ".cafe24api.com/api/v2/admin/orders/".$data['resource']['order_id']."?embed=items";

			$param['header'] = array(
				"Authorization: Bearer {$access_token}",
				"Content-Type: application/json",
				"X-Cafe24-Api-Version: " . CAFE24_API_VERSION
			);

			$ch = curl_init($param['url']);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $param['header']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);
			$response = json_decode($response, 1);
			
			if($response['order']) {
				$arrData = [
					'order_id' => $response['order']['order_id'] ?? null,
					'member_id' => $response['order']['member_id'] ?? null,
					'items' => $response['order']['items'] ?? null,
					'additional_order_info_list' => $response['order']['additional_order_info_list'] ?? null,
				];

				// wg_orderGoods 테이블 필드 정보 가져오기 (최초 1회)
				static $goodsTableFields = null;
				if ($goodsTableFields === null) {
					$sql = "DESCRIBE wg_orderGoods";
					$goodsTableFields = $this->db->query_fetch($sql);
				}
				$goodsFieldNames = array_column($goodsTableFields, 'Field');
				$goodsFieldTypes = array_column($goodsTableFields, 'Type', 'Field');

				$fieldsToJson = ['additional_option_values', 'original_item_no', 'options'];
				
				foreach($arrData['items'] as $key => $item) {
					foreach ($fieldsToJson as $field) {
						if (isset($item[$field]) && is_array($item[$field])) {
							$item[$field] = json_encode($item[$field], JSON_UNESCAPED_UNICODE);
						}
					}

					$updateParts = [];
					$insertCols = [];
					$insertVals = [];
					$order_item_code = $item['order_item_code'] ?? '';

					foreach ($goodsTableFields as $f) {
						$fn = $f['Field'];
						if ($fn == 'sno' || $fn == 'regDt' || $fn == 'modDt') continue;

						$fv = $item[$fn] ?? null;

						// 배열 데이터인 경우 JSON 문자열로 변환
						if (is_array($fv)) {
							$fv = json_encode($fv, JSON_UNESCAPED_UNICODE);
						}

						$ft = strtolower($f['Type']);

						// 날짜 형식 변환
						if ($fv && (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false)) {
							if (strpos($fv, 'T') !== false) {
								try { $fv = (new \DateTime($fv))->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
							}
						}

						// 값 보정
						$is_null = false;
						$allows_null = (strtoupper($f['Null']) == 'YES');
						if ($fv === null || $fv === '') {
							if (strpos($ft, 'decimal') !== false || strpos($ft, 'int') !== false || strpos($ft, 'float') !== false) {
								$fv = 0;
							} elseif (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false) {
								if ($allows_null) { $fv = null; $is_null = true; } else { $fv = '0000-00-00 00:00:00'; }
							} else {
								$fv = '';
							}
						}

						// 쿼리 조립
						if (isset($item[$fn]) || ($f['Null'] == 'NO' && $f['Default'] === null) || $fn == 'order_id' || $fn == 'member_id') {
							// order_id, member_id는 $arrData에서 가져옴
							if ($fn == 'order_id') $fv = $arrData['order_id'];
							if ($fn == 'member_id') $fv = $arrData['member_id'];

							$escapedFv = $is_null ? "NULL" : "'" . addslashes($fv) . "'";
							$updateParts[] = "{$fn} = {$escapedFv}";
							$insertCols[] = $fn;
							$insertVals[] = $escapedFv;
						}
					}

					// 중복확인
					$sql = "SELECT sno FROM wg_orderGoods WHERE order_id = '" . addslashes($arrData['order_id']) . "' AND order_item_code = '" . addslashes($order_item_code) . "'";
					$resultSno = $this->db->query_fetch($sql)[0]['sno'] ?? null;
					
					if($resultSno) {
						$sql = "UPDATE wg_orderGoods SET " . implode(', ', $updateParts) . ", modDt = now() WHERE sno = " . $resultSno;
						$this->db->query($sql);

						// 로그 기록
						$logSql = "INSERT INTO wg_apiLog(apiType, requestData, responseData) VALUES('orderOneApi_update', '" . addslashes($sql) . "', '" . addslashes(json_encode($arrData)) . "')";
						$this->db->query($logSql);
					} else {
						$sql = "INSERT INTO wg_orderGoods (" . implode(', ', $insertCols) . ", regDt) VALUES (" . implode(', ', $insertVals) . ", now())";
						$this->db->query($sql);
					}
				}
			}

			// 주문저장
			$param['url'] = "https://" . CAFE24_MALL_ID . ".cafe24api.com/api/v2/admin/orders/".$data['resource']['order_id']."";

			$param['header'] = array(
				"Authorization: Bearer {$access_token}",
				"Content-Type: application/json",
				"X-Cafe24-Api-Version: " . CAFE24_API_VERSION
			);

			$ch = curl_init($param['url']);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $param['header']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);
			$response = json_decode($response, 1);

			if (gd_isset($response['order'])) {
				$orderData = $response['order'];

				if($orderData['shipping_fee_detail']) $orderData['shipping_fee_detail'] = json_encode($orderData['shipping_fee_detail']);
				$payment_method = implode('||', $orderData['payment_method']);

				// 추가 정보 추출 (쇼핑지원금, 총상품금액)
				$use_event_mileage = 0;
				$total_goods_price = 0;
				if (isset($orderData['additional_order_info_list']) && is_array($orderData['additional_order_info_list'])) {
					foreach ($orderData['additional_order_info_list'] as $info) {
						if (trim($info['name']) == '쇼핑지원금') {
							$use_event_mileage = (int)str_replace(',', '', $info['value']);
						} elseif (trim($info['name']) == '총상품금액') {
							$total_goods_price = (int)str_replace(',', '', $info['value']);
						}
					}
				}

				// wg_order 테이블 필드 정보 가져오기
				static $orderOneTableFields = null;
				if ($orderOneTableFields === null) {
					$sql = "DESCRIBE wg_order";
					$orderOneTableFields = $this->db->query_fetch($sql);
				}

				$orderFieldNames = array_column($orderOneTableFields, 'Field');
				$orderFieldTypes = array_column($orderOneTableFields, 'Type', 'Field');

				// 데이터 매핑 및 보정 (webhook 응답 구조에 맞춤)
				$mappedData = [
					'order_id' => $orderData['order_id'],
					'member_id' => $orderData['member_id'],
					'shipping_status' => $orderData['shipping_status'],
					'payment_amount' => $orderData['payment_amount'] ?? 0,
					'shipping_fee_detail' => $orderData['shipping_fee_detail'],
					'items' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', json_encode($orderData['items'] ?? [], JSON_UNESCAPED_UNICODE)),
					'order_date' => $orderData['order_date'],
					'payment_date' => $orderData['payment_date'],
					'points_spent_amount' => $orderData['points_spent_amount'] ?? 0,
					'payment_method' => $payment_method,
					'bank_code' => $orderData['bank_code'] ?? '',
					'bank_account_no' => $orderData['bank_account_no'] ?? '',
					'bank_account_owner_name' => $orderData['bank_account_owner_name'] ?? '',
					'initial_order_amount' => $orderData['order_price_amount'] ?? 0,
					'use_event_mileage' => $use_event_mileage,
					'total_goods_price' => $total_goods_price,
					'actual_order_amount' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', json_encode($orderData['actual_order_amount'] ?? [], JSON_UNESCAPED_UNICODE)),
				];

				$updateParts = [];
				$insertCols = [];
				$insertVals = [];

				foreach ($orderOneTableFields as $f) {
					$fn = $f['Field'];
					if ($fn == 'sno' || $fn == 'regDt') continue;
					
					$fv = $mappedData[$fn] ?? null;
					$ft = strtolower($f['Type']);

					// 날짜 형식 변환
					if ($fv && (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false)) {
						if (strpos($fv, 'T') !== false) {
							try { $fv = (new \DateTime($fv))->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
						}
					}

					// 값이 없을 때 보정
					$is_null = false;
					$allows_null = (strtoupper($f['Null']) == 'YES');

					if ($fv === null || $fv === '') {
						if (strpos($ft, 'decimal') !== false || strpos($ft, 'int') !== false || strpos($ft, 'float') !== false) {
							$fv = 0;
						} elseif (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false) {
							if ($allows_null) {
								$fv = null;
								$is_null = true;
							} else {
								$fv = '0000-00-00 00:00:00';
							}
						} else {
							$fv = '';
						}
					}

					// 쿼리에 포함할지 결정
					if (isset($mappedData[$fn]) || ($f['Null'] == 'NO' && $f['Default'] === null)) {
						$escapedFv = $is_null ? "NULL" : "'" . addslashes($fv) . "'";
						$updateParts[] = "{$fn} = {$escapedFv}";
						$insertCols[] = $fn;
						$insertVals[] = $escapedFv;
					}
				}

				$sql = "SELECT order_id FROM wg_order WHERE order_id = '{$orderData['order_id']}'";
				$exists = $this->db->query_fetch($sql);

				if($exists) {
					$sql = "UPDATE wg_order SET " . implode(', ', $updateParts) . " WHERE order_id = '{$orderData['order_id']}';";
					$this->db->query($sql);
				} else {
					$sql = "INSERT INTO wg_order (" . implode(', ', $insertCols) . ", regDt) VALUES (" . implode(', ', $insertVals) . ", now());";
					$this->db->query($sql);
				}

				// 주문 처리 완료 후 해당 주문의 마일리지 자동 지급
				if (!empty($orderData['order_id']) && !empty($orderData['member_id'])) {
					// 쇼핑지원금 차감 처리 적용
					$eventMileage = new \EventMileage\EventMileage();
					$eventMileage->processPendingOrderMileage($orderData['order_id']);
				}
			}
		}
	}

	public function getOrderList() {
		//페이징
		$getValue['page'] = gd_isset($_GET['page'], 1);
		if(!$_GET['pageNum']) {
			$_GET['pageNum'] = '50';
		}
		$pageNum = $_GET['pageNum'];
		$this->page = new Page($getValue['page']);
		$this->page->page['list'] = $pageNum; // 페이지당 리스트 수
		$this->page->block['cnt'] = 5; // 블록당 리스트 개수
		$this->page->setPage();
		$this->page->setUrl($_SERVER['QUERY_STRING']);
		//관리자 페이징
		if($_GET['mode'] != 'searchStore') {
			$limit = 'LIMIT '.$this->page->recode['start'] . ',' . $pageNum;
		}
		$limit = gd_isset($limit,'');

		if(count($arrWhere) == 0) {
			$arrWhere[] = 1;
		}

		if(count($arrWhere) == 0) {
			$arrWhere[] = 1;
		}
		
		if($_GET['field']) {
			$field = $_GET['field'];
		} else {
			$field = '*';
		}
		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_order o
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					order_id desc
				'.$limit.'
		';

		$goodsList = $this->db->query_fetch($sql);
		$result['goodsList'] = $goodsList;

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(order_id) as cnt
				FROM 
					wg_order o
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					order_id desc
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		$sql = '
				SELECT 
					count(order_id) as cnt
				FROM 
					wg_order o
				ORDER BY
					order_id desc
		';
		$totalCnt = $this->db->query_fetch($sql)[0];
		$result['totalCnt'] = $totalCnt['cnt'];

		$pageHtml = '';
		$pageHtml .= '
			<a onclick="move_page(1)" class="first">첫 페이지</a>
			<a onclick="move_page('.$page->design['prevPage'].')">이전 페이지</a>
			<ol>
		';
		
		foreach(range($this->page->page['start'], $this->page->page['end']) as $val) {
			if($this->page->page['now'] == $val) {
				$class = 'this';
			} else {
				$class = '';
			}
			$pageHtml .= '<li class="xans-record-"><a onclick="move_page('.$val.')" class="'.$class.'">'.$val.'</a></li>';
		}

		$pageHtml .= '
			</ol>
			<a onclick="move_page('.$this->page->design['nextPage'].')">다음 페이지</a>
			<a onclick="move_page('.$this->page->design['lastPage'].')" class="last">마지막 페이지</a>
		';

		$result['pageHtml'] = $pageHtml;

		return $result;
	}

	public function refundOrder()
	{
		// API Key 인증 체크
		$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
		if ($apiKey !== '23c92e8d-778e-4600-bccb-50eb58d556bf') {
			header('HTTP/1.1 401 Unauthorized');
			$this->log->error('Unauthorized refund webhook access', ['received_key' => $apiKey]);
			echo json_encode(['success' => false, 'message' => 'Invalid API Key']);
			exit;
		}

		// 웹훅 데이터 수신
		$rawData = file_get_contents('php://input'); 
		$data = json_decode($rawData, true);
		sleep(10); // 주문 변경 완료될때까지 대기

		if (gd_isset($data['resource']['order_id'])) {
			$url = "https://" . CAFE24_MALL_ID . ".cafe24api.com/api/v2/admin/orders/".$data['resource']['order_id']."?embed=items";
			$response = $this->cafe24->simpleCafe24Api([
				'url' => $url,
				'method' => 'GET'
			]);

			if($response['order']) {
				$arrData = [
					'order_id' => $response['order']['order_id'] ?? null,
					'member_id' => $response['order']['member_id'] ?? null,
					'items' => $response['order']['items'] ?? null,
					'additional_order_info_list' => $response['order']['additional_order_info_list'] ?? null,
				];

				// wg_orderGoods 테이블 필드 정보 가져오기 (최초 1회)
				static $refundGoodsTableFields = null;
				if ($refundGoodsTableFields === null) {
					$sql = "DESCRIBE wg_orderGoods";
					$refundGoodsTableFields = $this->db->query_fetch($sql);
				}
				$fieldNames = array_column($refundGoodsTableFields, 'Field');

				$fieldsToJson = ['additional_option_values', 'original_item_no', 'options'];

				foreach($arrData['items'] as $key => $item) {
					foreach ($fieldsToJson as $field) {
						if (isset($item[$field]) && is_array($item[$field])) {
							$item[$field] = json_encode($item[$field], JSON_UNESCAPED_UNICODE);
						}
					}

					$updateParts = [];
					$insertCols = [];
					$insertVals = [];
					$order_item_code = $item['order_item_code'] ?? '';

					foreach ($refundGoodsTableFields as $f) {
						$fn = $f['Field'];
						if ($fn == 'sno' || $fn == 'regDt' || $fn == 'modDt') continue;

						$fv = $item[$fn] ?? null;

						// 배열 데이터인 경우 JSON 문자열로 변환
						if (is_array($fv)) {
							$fv = json_encode($fv, JSON_UNESCAPED_UNICODE);
						}

						$ft = strtolower($f['Type']);

						// 날짜 형식 변환
						if ($fv && (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false)) {
							if (strpos($fv, 'T') !== false) {
								try { $fv = (new \DateTime($fv))->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
							}
						}

						// 값 보정
						$is_null = false;
						$allows_null = (strtoupper($f['Null']) == 'YES');
						if ($fv === null || $fv === '') {
							if (strpos($ft, 'decimal') !== false || strpos($ft, 'int') !== false || strpos($ft, 'float') !== false) {
								$fv = 0;
							} elseif (strpos($ft, 'datetime') !== false || strpos($ft, 'timestamp') !== false) {
								if ($allows_null) { $fv = null; $is_null = true; } else { $fv = '0000-00-00 00:00:00'; }
							} else {
								$fv = '';
							}
						}

						// 쿼리 조립
						if (isset($item[$fn]) || ($f['Null'] == 'NO' && $f['Default'] === null) || $fn == 'order_id' || $fn == 'member_id') {
							if ($fn == 'order_id') $fv = $arrData['order_id'];
							if ($fn == 'member_id') $fv = $arrData['member_id'];

							$escapedFv = $is_null ? "NULL" : "'" . addslashes($fv) . "'";
							$updateParts[] = "{$fn} = {$escapedFv}";
							$insertCols[] = $fn;
							$insertVals[] = $escapedFv;
						}
					}

					// 중복확인
					$sql = "SELECT sno FROM wg_orderGoods WHERE order_id = '" . addslashes($arrData['order_id']) . "' AND order_item_code = '" . addslashes($order_item_code) . "'";
					$resultSno = $this->db->query_fetch($sql)[0]['sno'] ?? null;
	
					if($resultSno) {
						$sql = "UPDATE wg_orderGoods SET " . implode(', ', $updateParts) . ", modDt = now() WHERE sno = " . $resultSno;
						$this->db->query($sql);

						$logSql = "INSERT INTO wg_apiLog(apiType, requestData, responseData) VALUES('orderRefund_update', '" . addslashes($sql) . "', '" . addslashes(json_encode($arrData)) . "')";
						$this->db->query($logSql);
					} else {
						$sql = "INSERT INTO wg_orderGoods (" . implode(', ', $insertCols) . ", regDt) VALUES (" . implode(', ', $insertVals) . ", now())";
						$this->db->query($sql);
					}
				}

				// 쇼핑지원금 복구 처리 (함수 내부에서 'y'로 최종 변경됨)
				$eventMileage = new \EventMileage\EventMileage();
				$eventMileage->restoreMileageByRefund($data['resource']['order_id']);
			}
		}

	}

	public function downloadExcel() {
		try {
			// 데이터 조회 (페이징 없이)
			$_GET['mode'] = 'excel_download';
			$orderList = $this->getOrderList();

			// CSV 헤더
			$filename = 'order_list_' . date('Y-m-d_H-i-s') . '.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			// UTF-8 BOM
			echo "\xEF\xBB\xBF";

			$output = fopen('php://output', 'w');
			$header = ['주문일','주문번호','회원아이디','결제금액','결제일자','배송상태'];
			fputcsv($output, $header);

			foreach($orderList['goodsList'] as $row){
				$line = [
					$row['order_date'] ?? '',
					$row['order_id'] ?? '',
					$row['member_id'] ?? '',
					$row['payment_amount'] ?? '',
					$row['payment_date'] ?? '',
					$row['shipping_status'] ?? '',
				];
				fputcsv($output, $line);
			}

			fclose($output);
			exit;

		} catch (\Exception $e) {
			echo "CSV 다운로드 중 오류가 발생했습니다: ".$e->getMessage();
			exit;
		}
	}

	// 조건 만족 주문에 대해 마일리지 지급 (wg_order 기준)
	//public function awardCashPaymentMileage() {
	//	$mileage = new Mileage();
	//	// 결제수단 cash, 주문상품 모두 N50(구매확정) 이고, 아직 지급하지 않은 주문
	//	$sql = "
	//		SELECT o.order_id, o.member_id, o.actual_order_amount, mg.cashExtraRate, m.groupNo
	//		FROM wg_order o
	//		LEFT JOIN wg_member m ON m.memId = o.member_id
	//		LEFT JOIN wg_memberGrade mg ON mg.groupNo = m.groupNo
	//		WHERE o.payment_method LIKE '%cash%'
	//		AND o.order_id IN (
	//			SELECT order_id FROM wg_orderGoods x
	//			GROUP BY order_id
	//			HAVING SUM(CASE WHEN x.order_status = 'N50' THEN 0 ELSE 1 END) = 0
	//		)
	//		AND (o.mileage_send_flag IS NULL OR o.mileage_send_flag = 'n')
	//	";
	//	$list = $this->db->query_fetch($sql);

	//	foreach ($list as $row) {
	//		$actualOrderAmount = json_decode($row['actual_order_amount'], true);
	//		$shippingFee = ($actualOrderAmount['shipping_fee'] - $actualOrderAmount['shipping_fee_discount_amount']) > 0 ? ($actualOrderAmount['shipping_fee'] - $actualOrderAmount['shipping_fee_discount_amount']) : 0;
	//		$paymentAmount = $actualOrderAmount['payment_amount'] - $shippingFee;
	//		if ($paymentAmount <= 0) { continue; }

	//		// 회원 등급별 적립율 (사전 조회 맵 사용)
	//		$memberId = $row['member_id'];
	//		$rate = (float)($row['cashExtraRate'] ?? 0);
	//		$points = round($paymentAmount * ($rate / 100), 2);

	//		if ($points <= 0) {
	//			continue;
	//		}

	//		if($row['groupNo'] == 7 && $paymentAmount < 500000) {
	//			continue;
	//		}

	//		$reason = '현금결제 추가 적립 (' . $rate . '%)';
	//		list($payload, $resp) = $mileage->requestPoints($memberId, $points, 'increase', $reason);

	//		$status = (isset($resp['error']) && $resp['error']) ? 'failed' : 'success';
	//		$this->db->query("INSERT INTO wg_mileageLog(order_id, member_id, points, action, status, reason, request_json, response_json, actual_order_amount, regDt) VALUES('".addslashes($row['order_id'])."', '".addslashes($memberId)."', '".$points."', 'award', '".$status."', '".addslashes($reason)."', '".addslashes(json_encode($payload, JSON_UNESCAPED_UNICODE))."', '".addslashes(json_encode($resp, JSON_UNESCAPED_UNICODE))."', '".addslashes(json_encode($actualOrderAmount))."', NOW())");

	//		if ($status === 'success') {
	//			$this->db->query("UPDATE wg_order SET mileage_send_flag='y' WHERE order_id='".addslashes($row['order_id'])."'");
	//		}
	//	}
	//}

	// P0000GYJ 상품 구매 시 수량만큼 마일리지 지급
	public function awardProductMileage() {
		$result = [
			'success' => 0,
			'failed' => 0,
			'total' => 0,
			'message' => ''
		];

		// wg_orderGoods에서 product_code가 P0000GYJ인 항목 조회 (아직 지급하지 않은 것만)
		// order_status: N10(상품준비중), N20(배송준비중), N21(배송대기), N30(배송중), N40(배송완료), N50(구매확정)
		$sql = "
			SELECT og.order_id, og.order_item_code, og.member_id, og.quantity, og.product_price, og.option_price, og.product_code
			FROM wg_orderGoods og
			WHERE og.product_code = 'P0000GYJ'
			AND og.member_id IS NOT NULL
			AND og.member_id != ''
			AND og.quantity > 0
			AND og.order_status IN ('N10', 'N20', 'N21', 'N30', 'N40', 'N50')
			AND (og.mileage_send_flag IS NULL OR og.mileage_send_flag = 'n')
		";
        //test로 해당부분 변경
		$list = $this->db->query_fetch($sql);

		if (empty($list)) {
			$result['message'] = '지급할 항목이 없습니다.';
			return $result;
		}

		$result['total'] = count($list);

		foreach ($list as $row) {
			$memberId = $row['member_id'];
			$quantity = (int)$row['quantity'];
			$orderId = $row['order_id'];
			$orderItemCode = $row['order_item_code'];
			$productCode = $row['product_code'];
			$productPrice = (float)($row['product_price'] ?? 0);
			$optionPrice = (float)($row['option_price'] ?? 0);

			if ($quantity <= 0) {
				continue;
			}

			// (product_price + option_price) * quantity 만큼 마일리지 지급
			$points = ($productPrice + $optionPrice) * $quantity;
			
			if ($points <= 0) {
				continue;
			}
			
			// 카페24 API 제한: reason은 60바이트 이하여야 함
			$reason = 'P0000GYJ 구매적립';
			
			// Mileage 클래스의 addMileage 사용 (benefit_type은 무조건 'point')
			$mileage = new Mileage();
			list($payload, $resp) = $mileage->addMileage($memberId, $points, 'increase', $reason);
			
			$status = (isset($resp['error']) && $resp['error']) ? 'failed' : 'success';
			$successFlag = ($status === 'success') ? 'y' : 'n';

			// wg_goods_benefit_history에 INSERT (Attendance.php 패턴 참고)
			$sql = "INSERT INTO wg_goods_benefit_history (
				order_id,
				order_item_code,
				member_id,
				product_code,
				benefit_type,
				benefit_value,
				reg_date,
				success_flag
			) VALUES (
				'".addslashes($orderId)."',
				'".addslashes($orderItemCode)."',
				'".addslashes($memberId)."',
				'".addslashes($productCode)."',
				'point',
				'".addslashes($points)."',
				NOW(),
				'{$successFlag}'
			)";
			$this->db->query($sql);

			if ($status === 'success') {
				// 지급 성공 시 wg_orderGoods의 mileage_send_flag를 'y'로 업데이트
				$this->db->query("UPDATE wg_orderGoods SET mileage_send_flag='y' WHERE order_id='".addslashes($orderId)."' AND order_item_code='".addslashes($orderItemCode)."'");
				$result['success']++;
			} else {
				$result['failed']++;
			}
		}

		$result['message'] = "총 {$result['total']}건 중 성공: {$result['success']}건, 실패: {$result['failed']}건";
		return $result;
	}
}
