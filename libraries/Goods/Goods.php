<?php
namespace Goods;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Storage\Storage;
use Page\Page;

class Goods
{
	public $db;

	public function __construct() {
		$this->db = new DB();
	}

	public function getCategoryGoodsList($categoryData) {
		$productList = [];
		if($categoryData['goodsList']) {
			$productList[] = $categoryData['goodsList'];
		}

		if(is_array($productList)) {
			$productList = array_merge(...array_map(function($item) {
				return explode(',', $item);
			}, $productList));
		}
		
		if($categoryData['childs']) {
			foreach($categoryData['childs'] as $val) {
				$result = $this->getCategoryGoodsList($val);
				if($result) {
					$productList = array_merge($productList, $result);
				}
			}
		}

		return $productList;
	}

	public function getCategory($categoryNo, $mode) {
		$sql = "
			SELECT *
			FROM wg_category
			ORDER BY category_depth DESC, display_order ASC
		";
		$category = $this->db->query_fetch($sql);
		
		if($_GET['type'] == 'tree' || $mode = 'tree') {
			$tmpData = [];
			$category = array_combine(array_column($category, 'category_no'), $category);
			foreach($category as $key => $val) {
				if(($val['parent_category_no'] && $val['category_depth'] != 1) && (!$categoryNo || $val['category_no'] != $categoryNo)) {
					$category[$val['parent_category_no']]['childs'][$val['category_no']] = $category[$key];
				} else {
					$tmpData[$val['category_no']] = $category[$key];
				}

				if($categoryNo && $val['category_no'] == $categoryNo) {
					break;
				}
			}
			$category = $tmpData;
		}

		return $category;
	}

	public function selectCategoryGoods() {
		$cafe24 = new Cafe24();
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;

		$url = "https://{$mallid}.cafe24api.com/api/v2/admin/categories/167/products?display_group=1";
		$ch = curl_init($url);

		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
			'X-Cafe24-Api-Version: ' . $version
		);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		$response = json_decode($response, 1);
		gd_debug($response);
		exit;
	}

	# 카페24 API로 주기적으로 동기화
	public function updateCategory() {
		$cafe24 = new Cafe24();
		$categories = [];
		foreach(range(0, 4) as $val) {
			$response = $cafe24->simpleCafe24Api([
				'url' => "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/admin/categories?offset=".($val * 100)."&limit=100",
				'method' => 'GET'
			]);
			$categories = array_merge($categories, $response['categories']);

		}

		// --------------------------------------------
		//  API에서 조회된 카테고리와 DB에 저장된 카테고리를 비교하여
		//  API에 존재하지 않는 DB 카테고리를 삭제한다.
		// --------------------------------------------
		$apiCategoryNos = array_column($categories, 'category_no');
		// 현재 DB에 존재하는 카테고리 번호 조회
		$sql = "SELECT category_no FROM wg_category";
		$dbCategories = $this->db->query_fetch($sql);
		$dbCategoryNos = array_column($dbCategories, 'category_no');

		$deleteCategoryNos = array_diff($dbCategoryNos, $apiCategoryNos);
		if(!empty($deleteCategoryNos)) {
			$in = implode(',', $deleteCategoryNos);
			// 카테고리-상품 링크 테이블 먼저 정리
			$this->db->query("DELETE FROM wg_goodsLinkCategory WHERE categoryNo IN({$in})");
			// 카테고리 테이블 정리
			$this->db->query("DELETE FROM wg_category WHERE category_no IN({$in})");
		}
		
		foreach($categories as $val) {
			$sql = "SELECT 1 FROM wg_category WHERE category_no = {$val['category_no']}";
			$category = $this->db->query_fetch($sql);

			$val['category_name'] = addslashes($val['category_name']);
			if($category) {
				$sql = "
					UPDATE wg_category
					SET category_depth = {$val['category_depth']},
					category_name = '{$val['category_name']}',
					parent_category_no = {$val['parent_category_no']},
					use_display = '{$val['use_display']}',
					display_order = '{$val['display_order']}'
					WHERE category_no = {$val['category_no']}
				";
			} else {
				$sql = "
					INSERT INTO wg_category(category_no, category_depth, category_name, parent_category_no, use_display, display_order)
					VALUES({$val['category_no']}, {$val['category_depth']}, '{$val['category_name']}', {$val['parent_category_no']}, '{$val['use_display']}', '{$val['display_order']}')
				";
			}
			$this->db->query($sql);
			
			$response = $cafe24->simpleCafe24Api([
				'url' => "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/admin/categories/{$val['category_no']}/products?display_group=1",
				'method' => 'GET'
			]);
			
			$productNo = '';
			foreach($response['products'] as $key => $product) {
				if($key != 0) {
					$productNo .= ',';
				}
				$productNo .= $product['product_no'];

				$sql = "INSERT INTO wg_goodsLinkCategory (categoryNo, goodsNo, regDt) VALUES ({$val['category_no']}, {$product['product_no']}, NOW())";
				$this->db->query($sql);
			}

			$sql = "
				UPDATE wg_category
				SET goodsList = '{$productNo}'
				WHERE category_no = {$val['category_no']}
			";
			$this->db->query($sql);
		}

		
	}
	
	public function updateGoods() {
		$cafe24 = new Cafe24();
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;

		$goodsList = [];
		$oldProductNo = [];
		$sql = "SELECT product_no FROM wg_goods";
		$oldProductNo = $this->db->query_fetch($sql);
		$oldProductNo = array_column($oldProductNo, 'product_no', 'product_no');
		
		$sql = "SELECT variantCode FROM wg_goodsOption";
		$oldVariantCode = $this->db->query_fetch($sql);
		$oldVariantCode = array_column($oldVariantCode, 'variantCode', 'variantCode');

		foreach(range(0, 100) as $val) {
			$response = $cafe24->simpleCafe24Api([
				'url' => "https://{$mallid}.cafe24api.com/api/v2/admin/products?&embed=discountprice,options,additionalimages&limit=100&offset=" . ($val*100),
				'method' => 'GET'
			]);
			
			 //gd_debug($response);
			 //exit;
			
			if(count($response['products']) == 0) {
				break;
			}
			
			if (curl_errno($ch)) {
				echo 'Error: ' . curl_error($ch);
				break;
			}
			
			if($response['products'][0]) { 
				$goodsList = array_merge($goodsList, $response['products']);
			}
			curl_close($ch);
		}

		foreach($goodsList as $key => $val) {
			unset($oldProductNo[$val['product_no']]);

			$sql = "
				SELECT 1
				FROM wg_goods
				WHERE product_no = {$val['product_no']}
			";
			$result = $this->db->query_fetch($sql);
			
			$val['discount_price'] = $val['discountprice']['pc_discount_price'];

			if($result) {
				$sql = "
					UPDATE wg_goods
					SET	shop_no = '{$val['shop_no']}',
					product_no = '{$val['product_no']}',
					product_code = '{$val['product_code']}',
					custom_product_code = '{$val['custom_product_code']}',
					product_name = '{$val['product_name']}',
					eng_product_name = '{$val['eng_product_name']}',
					supply_product_name = '{$val['supply_product_name']}',
					internal_product_name = '{$val['internal_product_name']}',
					model_name = '{$val['model_name']}',
					price_excluding_tax = '{$val['price_excluding_tax']}',
					price = '{$val['price']}',
					retail_price = '{$val['retail_price']}',
					supply_price = '{$val['supply_price']}',
					display = '{$val['display']}',
					selling = '{$val['selling']}',
					product_condition = '{$val['product_condition']}',
					product_used_month = '{$val['product_used_month']}',
					summary_description = '{$val['summary_description']}',
					product_tag = '".implode(',', $val['product_tag'])."',
					margin_rate = '{$val['margin_rate']}',
					tax_calculation = '{$val['tax_calculation']}',
					tax_type = '{$val['tax_type']}',
					tax_rate = '{$val['tax_rate']}',
					price_content = '{$val['price_content']}',
					buy_limit_by_product = '{$val['buy_limit_by_product']}',
					repurchase_restriction = '{$val['repurchase_restriction']}',
					single_purchase_restriction = '{$val['single_purchase_restriction']}',
					buy_unit = '{$val['buy_unit']}',
					minimum_quantity = '{$val['minimum_quantity']}',
					maximum_quantity = '{$val['maximum_quantity']}',
					points_by_product = '{$val['points_by_product']}',
					except_member_points = '{$val['except_member_points']}',
					detail_image = '{$val['detail_image']}',
					list_image = '{$val['list_image']}',
					tiny_image = '{$val['tiny_image']}',
					small_image = '{$val['small_image']}',
					use_naverpay = '{$val['use_naverpay']}',
					naverpay_type = '{$val['naverpay_type']}',
					manufacturer_code = '{$val['manufacturer_code']}',
					trend_code = '{$val['trend_code']}',
					brand_code = '{$val['brand_code']}',
					supplier_code = '{$val['supplier_code']}',
					origin_classification = '{$val['origin_classification']}',
					origin_place_no = '{$val['origin_place_no']}',
					made_in_code = '{$val['made_in_code']}',
					hscode = '{$val['hscode']}',
					product_weight = '{$val['product_weight']}',
					created_date = '{$val['created_date']}',
					updated_date = '{$val['updated_date']}',
					classification_code = '{$val['classification_code']}',
					sold_out = '{$val['sold_out']}',
					additional_price = '{$val['additional_price']}',
					clearance_category_eng = '{$val['clearance_category_eng']}',
					clearance_category_kor = '{$val['clearance_category_kor']}',
					clearance_category_code = '{$val['clearance_category_code']}',
					exposure_limit_type = '{$val['exposure_limit_type']}',
					shipping_fee_by_product = '{$val['shipping_fee_by_product']}',
					shipping_fee_type = '{$val['shipping_fee_type']}',
					market_sync = '{$val['market_sync']}',
					additionalImages = '{$val['additionalimages'][0]['big']}',

					WHERE shop_no = {$val['shop_no']} AND product_no = {$val['product_no']}
				";
				$this->db->query($sql);
			} else {
				$sql = "
					INSERT INTO wg_goods(shop_no, product_no, product_code, custom_product_code, product_name, eng_product_name, supply_product_name, internal_product_name, model_name, price_excluding_tax, price, retail_price, supply_price, display, selling, product_condition, product_used_month, summary_description, product_tag, margin_rate, tax_calculation, tax_type, tax_rate, price_content, buy_limit_by_product, repurchase_restriction, single_purchase_restriction, buy_unit, minimum_quantity, maximum_quantity, points_by_product, except_member_points, detail_image, list_image, tiny_image, small_image, use_naverpay, naverpay_type, manufacturer_code, trend_code, brand_code, supplier_code, origin_classification, origin_place_no, made_in_code, hscode, product_weight, created_date, updated_date, classification_code, sold_out, additional_price, clearance_category_eng, clearance_category_kor, clearance_category_code, exposure_limit_type, shipping_fee_by_product, shipping_fee_type, market_sync, additionalImages, options)
					VALUES('{$val['shop_no']}','{$val['product_no']}','{$val['product_code']}','{$val['custom_product_code']}','{$val['product_name']}','{$val['eng_product_name']}','{$val['supply_product_name']}','{$val['internal_product_name']}','{$val['model_name']}','{$val['price_excluding_tax']}','{$val['price']}','{$val['retail_price']}','{$val['supply_price']}','{$val['display']}','{$val['selling']}','{$val['product_condition']}','{$val['product_used_month']}','{$val['summary_description']}','".implode(',', $val['product_tag'])."','{$val['margin_rate']}','{$val['tax_calculation']}','{$val['tax_type']}','{$val['tax_rate']}','{$val['price_content']}','{$val['buy_limit_by_product']}','{$val['repurchase_restriction']}','{$val['minimum_quantity']}','{$val['single_purchase_restriction']}','{$val['buy_unit']}','{$val['maximum_quantity']}','{$val['points_by_product']}','{$val['except_member_points']}','{$val['detail_image']}','{$val['list_image']}','{$val['tiny_image']}','{$val['small_image']}','{$val['use_naverpay']}','{$val['naverpay_type']}','{$val['manufacturer_code']}','{$val['trend_code']}','{$val['brand_code']}','{$val['supplier_code']}','{$val['origin_classification']}','{$val['origin_place_no']}','{$val['made_in_code']}','{$val['hscode']}','{$val['product_weight']}','{$val['created_date']}','{$val['updated_date']}','{$val['classification_code']}','{$val['sold_out']}','{$val['additional_price']}','{$val['clearance_category_eng']}','{$val['clearance_category_kor']}','{$val['clearance_category_code']}','{$val['exposure_limit_type']}','{$val['shipping_fee_by_product']}','{$val['shipping_fee_type']}','{$val['market_sync']}', '{$val['additionalimages'][0]['big']}', '".addslashes(json_encode($val['options']))."')
				";

				$this->db->query($sql);
			}

			// 상품 옵션도 저장
			$url = "https://".CAFE24_MALL_ID.".cafe24api.com/api/v2/admin/products/{$val['product_no']}/variants";
			$response = $cafe24->simpleCafe24Api([
				'url' => $url,
				'method' => 'GET'
			]);

			// variants 데이터 처리
			foreach ($response['variants'] as $variant) {
				unset($oldVariantCode[$variant['variant_code']]);
				// 옵션 값들을 분리
				$valueText1 = $value1 = $valueText2 = $value2 = $valueText3 = $value3 = 
				$valueText4 = $value4 = $valueText5 = $value5 = '';
				
				foreach ($variant['options'] as $index => $option) {
					$optionIndex = $index + 1;
					${"valueText" . $optionIndex} = addslashes($option['name']);
					${"value" . $optionIndex} = addslashes($option['value']);
				}

				// 나머지 값들 처리
				$variantCode = addslashes($variant['variant_code']);
				$customVariantCode = addslashes($variant['custom_variant_code']);
				$display = $variant['display'];
				$selling = $variant['selling'];
				$additionalAmount = (float)$variant['additional_amount'];
				$useInventory = $variant['use_inventory'];
				$importantInventory = $variant['important_inventory'];
				$inventoryControlType = $variant['inventory_control_type'];
				$displaySoldout = $variant['display_soldout'];
				$quantity = (int)$variant['quantity'];
				$safetyInventory = (int)$variant['safety_inventory'];
				$image = addslashes($variant['image']);

				// UPSERT 쿼리 생성
				$sql = "INSERT INTO wg_goodsOption (
					productNo,
					variantCode,
					valueText1, value1,
					valueText2, value2,
					valueText3, value3,
					valueText4, value4,
					valueText5, value5,
					customVariantCode,
					display,
					selling,
					additionalAmount,
					useInventory,
					importantInventory,
					inventoryControlType,
					displaySoldout,
					quantity,
					safetyInventory,
					image,
					regDt
				) VALUES (
					{$val['product_no']},
					'{$variantCode}',
					'{$valueText1}', '{$value1}',
					'{$valueText2}', '{$value2}',
					'{$valueText3}', '{$value3}',
					'{$valueText4}', '{$value4}',
					'{$valueText5}', '{$value5}',
					'{$customVariantCode}',
					'{$display}',
					'{$selling}',
					{$additionalAmount},
					'{$useInventory}',
					'{$importantInventory}',
					'{$inventoryControlType}',
					'{$displaySoldout}',
					{$quantity},
					{$safetyInventory},
					'{$image}',
					NOW()
				) ON DUPLICATE KEY UPDATE
					valueText1 = '{$valueText1}',
					value1 = '{$value1}',
					valueText2 = '{$valueText2}',
					value2 = '{$value2}',
					valueText3 = '{$valueText3}',
					value3 = '{$value3}',
					valueText4 = '{$valueText4}',
					value4 = '{$value4}',
					valueText5 = '{$valueText5}',
					value5 = '{$value5}',
					customVariantCode = '{$customVariantCode}',
					display = '{$display}',
					selling = '{$selling}',
					additionalAmount = {$additionalAmount},
					useInventory = '{$useInventory}',
					importantInventory = '{$importantInventory}',
					inventoryControlType = '{$inventoryControlType}',
					displaySoldout = '{$displaySoldout}',
					quantity = {$quantity},
					safetyInventory = {$safetyInventory},
					image = '{$image}',
					regDt = NOW()";

				$this->db->query($sql);
			}
		}

		if($oldProductNo) {
			foreach($oldProductNo as $val) {
				$sql = "DELETE FROM wg_goods WHERE product_no = {$val}";
				$this->db->query($sql);
			}
		}

		if($oldVariantCode) {
			// foreach($oldVariantCode as $val) {
			// 	$sql = "DELETE FROM wg_goodsOption WHERE variantCode = {$val}";
			// 	$this->db->query($sql);
			// }
		}

		$this->updateCategory();

		echo "<script>alert('연동이 완료되었습니다'); location.href='/cafe24/goods/goods_list.php'</script>";
	}

	public function saveGoods($postValue) {
		$productNo = $postValue['product_no'];
		$quantities = $postValue['quantity'];
		$discountValues = $postValue['discount_value'];
		$discountTypes = $postValue['discount_type'];

		// 기존 데이터 삭제
		$sql = "DELETE FROM wg_productQuantitySaleConfig WHERE productNo = {$productNo}";
		$this->db->query($sql);

		// 새로운 데이터 입력
		for($i = 0; $i < count($quantities); $i++) {
			if(empty($quantities[$i]) || empty($discountValues[$i])) continue;
			
			$sql = "
				INSERT INTO wg_productQuantitySaleConfig 
				(productNo, quantity, discountValue, discountType, regDt) 
				VALUES 
				({$productNo}, {$quantities[$i]}, {$discountValues[$i]}, '{$discountTypes[$i]}', NOW())
			";
			$this->db->query($sql);
		}
	}

	public function getGoodsAddImage($productNo) {
		if($_SERVER['HTTP_ORIGIN'] == 'https://global.pollimolli.com') {
			$where = 'displayFl2 != \'n\'';
		} else {
			$where = 'displayFl != \'n\'';
		}
		
		$sql = "
			SELECT product_no, additionalImages 
			FROM wg_goods
			WHERE product_no IN(".implode(',', $productNo).")
			AND {$where}
		";
		$addImages = $this->db->query_fetch($sql);
		$addImages = array_combine(array_column($addImages, 'product_no'), array_column($addImages, 'additionalImages'));
		return $addImages;
	}

	public function displayGoods($getData) {
		
		if($getData['mode'] == 'display_goods') {
			$updateSql = 'displayFl = \'y\'';
		} else if($getData['mode'] == 'displaynone_goods') {
			$updateSql = 'displayFl = \'n\'';
		} else if($getData['mode'] == 'display2_goods') {
			$updateSql = 'displayFl2 = \'y\'';
		} else if($getData['mode'] == 'displaynone2_goods') {
			$updateSql = 'displayFl2 = \'n\'';
		}

		$sql = "
			UPDATE wg_goods
			SET {$updateSql}
			WHERE product_no IN(".implode(',', $getData['product_no']).")
		";
		$this->db->query($sql);
	}

	public function getGoodsList() {
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

		//검색어 검색
		if($_GET['keyword']) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name IN(\''.implode('\',\'', $keyword).'\') OR g.product_no IN(\''.implode('\',\'', $keyword).'\') OR g.product_code IN(\''.implode('\',\'', $keyword).'\'))';
				}else {
					$arrWhere[] = 'g.'.$_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name LIKE "%'.$_GET['keyword'].'%" OR g.product_no LIKE "%'.$_GET['keyword'].'%"OR g.product_code LIKE "%'.$_GET['keyword'].'%")';
				}else {
					$arrWhere[] = 'g.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
				}
			}
		}

		//카테고리 검색
		if($_GET['category1'] || $_GET['category2'] || $_GET['category3']) {
			$category = $this->getCategory($_GET['categoryNo'], 'tree');
			if($_GET['category3']) {
				$selectCategory = $category[$_GET['category3']];
			} else if($_GET['category2']) {
				$selectCategory = $category[$_GET['category2']];
			} else if($_GET['category1']) {
				$selectCategory = $category[$_GET['category1']];
			}
			$productList = $this->getCategoryGoodsList($selectCategory);
			$arrWhere[] = 'g.product_no IN('.implode(',', $productList).')'; 
		}

		if($_GET['productNo']) {
			$arrWhere[] = 'g.product_no IN('.implode(',', $_GET['productNo']).')'; 
		}

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

		$orderBy = 'g.created_date DESC';
		if($_GET['sort']) {
			$orderBy = $_GET['sort'];
		}

		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_goods g
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY '.$orderBy.'
				'.$limit.'
		';
		$goodsList = $this->db->query_fetch($sql);
		$result['goodsList'] = $goodsList;

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(product_no) as cnt
				FROM 
					wg_goods g
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					product_no desc
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		$sql = '
				SELECT 
					count(product_no) as cnt
				FROM 
					wg_goods g
				ORDER BY
					product_no desc
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

	public function getGoodsOptionList($forExcel = false) {
		// 티켓 필터 제거

		// 엑셀용이 아닐 때만 페이징 처리
		if (!$forExcel) {
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
		} else {
			$limit = '';
		}

		//검색어 검색
		if($_GET['keyword']) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name IN(\''.implode('\',\'', $keyword).'\') OR g.product_no IN(\''.implode('\',\'', $keyword).'\') OR g.product_code IN(\''.implode('\',\'', $keyword).'\') OR go.value1 IN(\''.implode('\',\'', $keyword).'\') OR go.value2 IN(\''.implode('\',\'', $keyword).'\') OR go.value3 IN(\''.implode('\',\'', $keyword).'\') OR go.value4 IN(\''.implode('\',\'', $keyword).'\') OR go.value5 IN(\''.implode('\',\'', $keyword).'\'))';
				}else if($_GET['key'] == 'option_name'){
					$arrWhere[] = 'go.value1 IN(\''.implode('\',\'', $keyword).'\') OR go.value2 IN(\''.implode('\',\'', $keyword).'\') OR go.value3 IN(\''.implode('\',\'', $keyword).'\') OR go.value4 IN(\''.implode('\',\'', $keyword).'\') OR go.value5 IN(\''.implode('\',\'', $keyword).'\'))';
				}else {
					$arrWhere[] = 'g.'.$_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name LIKE "%'.$_GET['keyword'].'%" OR g.product_no LIKE "%'.$_GET['keyword'].'%"OR g.product_code LIKE "%'.$_GET['keyword'].'%" OR go.value1 LIKE "%'.$_GET['keyword'].'%" OR go.value2 LIKE "%'.$_GET['keyword'].'%" OR go.value3 LIKE "%'.$_GET['keyword'].'%" OR go.value4 LIKE "%'.$_GET['keyword'].'%" OR go.value5 LIKE "%'.$_GET['keyword'].'%")';
				}else if($_GET['key'] == 'option_name'){
					$arrWhere[] = 'go.value1 LIKE "%'.$_GET['keyword'].'%" OR go.value2 LIKE "%'.$_GET['keyword'].'%" OR go.value3 LIKE "%'.$_GET['keyword'].'%" OR go.value4 LIKE "%'.$_GET['keyword'].'%" OR go.value5 LIKE "%'.$_GET['keyword'].'%"';
				}else {
					$arrWhere[] = 'g.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
				}
			}
		}

		if($_GET['category1'] || $_GET['category2'] || $_GET['category3']) {
			$category = $this->getCategory($_GET['categoryNo'], 'tree');
			if($_GET['category3']) {
				foreach($category as $val) {
					foreach($val['childs'] as $val2) {
						if($val2['childs'][$_GET['category3']]) {
							$selectCategory = $val2['childs'][$_GET['category3']];
						}
					}
				}
			} else if($_GET['category2']) {
				foreach($category as $val) {
					if($val['childs'][$_GET['category2']]) {
						$selectCategory = $val['childs'][$_GET['category2']];
					}
				}
			} else if($_GET['category1']) {
				$selectCategory = $category[$_GET['category1']];
			}
			$productList = $this->getCategoryGoodsList($selectCategory);
			$arrWhere[] = 'g.product_no IN('.implode(',', $productList).')'; 
		}

		// 티켓 전용 카테고리 필터 제거

		// 상품등록일 검색
		if($_GET['searchDate'][0] && $_GET['searchDate'][1]) {
			$arrWhere[] = "g.created_date BETWEEN '{$_GET['searchDate'][0]} 00:00:00' AND '{$_GET['searchDate'][1]} 23:59:59'";
		}

		// 진열상태 검색
		if($_GET['display']) {
			$arrWhere[] = "g.display = '{$_GET['display']}'";
		}

		// 판매상태 검색
		if($_GET['selling']) {
			$arrWhere[] = "g.selling = '{$_GET['selling']}'";
		}

		if($_GET['productNo']) {
			$arrWhere[] = 'g.product_no IN('.implode(',', $_GET['productNo']).')'; 
		}

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

		// 정렬 조건
		$orderBy = 'g.created_date DESC';
		if($_GET['sort']) {
			$orderBy = $_GET['sort'];
		}

		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_goods g
					LEFT JOIN wg_goodsOption go ON g.product_no = go.productNo
				WHERE 
					'.implode(' AND ', $arrWhere).'
					AND go.productNo IS NOT NULL
				ORDER BY '.$orderBy.'
				'.$limit.'
		';
		$goodsList = $this->db->query_fetch($sql);
		$result['goodsList'] = $goodsList;

		// 엑셀용일 때는 페이징 관련 데이터를 생성하지 않음
		if ($forExcel) {
			return $goodsList;
		}

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(product_no) as cnt
				FROM 
					wg_goods g
					LEFT JOIN wg_goodsOption go ON g.product_no = go.productNo
				WHERE 
					'.implode(' AND ', $arrWhere).'
					AND go.productNo IS NOT NULL
				ORDER BY
					product_no desc
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		$sql = '
				SELECT 
					count(product_no) as cnt
				FROM 
					wg_goods g
					LEFT JOIN wg_goodsOption go ON g.product_no = go.productNo
				WHERE go.productNo IS NOT NULL
				ORDER BY
					product_no desc
		';
		$totalCnt = $this->db->query_fetch($sql)[0];
		$result['totalCnt'] = $totalCnt['cnt'];

		$pageHtml = '';
		$pageHtml .= '
			<a onclick="move_page(1)" class="first">첫 페이지</a>
			<a onclick="move_page('.$this->page->design['prevPage'].')">이전 페이지</a>
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
	
	public function getGoodsWarrantyList($isExcel = false) {
		$this->page = new Page($getValue['page']);

		//페이징 (엑셀 다운로드가 아닐 때만)
		if (!$isExcel) {
			$getValue['page'] = gd_isset($_GET['page'], 1);
			if(!$_GET['pageNum']) {
				$_GET['pageNum'] = '50';
			}
			$pageNum = $_GET['pageNum'];
			$this->page->page['list'] = $pageNum; // 페이지당 리스트 수
			$this->page->block['cnt'] = 5; // 블록당 리스트 개수
			$this->page->setPage();
			$this->page->setUrl($_SERVER['QUERY_STRING']);
			//관리자 페이징
			if($_GET['mode'] != 'searchStore') {
				$limit = 'LIMIT '.$this->page->recode['start'] . ',' . $pageNum;
			}
			$limit = gd_isset($limit,'');
		} else {
			$limit = ''; // 엑셀 다운로드일 때는 페이징 없음
		}

		//검색어 검색
		if($_GET['keyword']) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name IN(\''.implode('\',\'', $keyword).'\') OR g.product_no IN(\''.implode('\',\'', $keyword).'\') OR g.product_code IN(\''.implode('\',\'', $keyword).'\') OR pwr.memNm IN(\''.implode('\',\'', $keyword).'\') OR pwr.registerNo IN(\''.implode('\',\'', $keyword).'\') OR pwr.memberId IN(\''.implode('\',\'', $keyword).'\') OR pwr.memCellPhone IN(\''.implode('\',\'', $keyword).'\'))';
				}else {
					if($_GET['key'] == 'memNm' || $_GET['key'] == 'registerNo' || $_GET['key'] == 'memberId' || $_GET['key'] == 'memCellPhone') {
						$arrWhere[] = 'pwr.'.$_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
					} else {
						$arrWhere[] = 'g.'.$_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
					}
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = '(g.product_name LIKE "%'.$_GET['keyword'].'%" OR g.product_no LIKE "%'.$_GET['keyword'].'%"OR g.product_code LIKE "%'.$_GET['keyword'].'%" OR pwr.memNm LIKE "%'.$_GET['keyword'].'%" OR pwr.registerNo LIKE "%'.$_GET['keyword'].'%" OR pwr.memberId LIKE "%'.$_GET['keyword'].'%" OR pwr.memCellPhone LIKE "%'.$_GET['keyword'].'%")';
				}else {
					if($_GET['key'] == 'memNm' || $_GET['key'] == 'registerNo' || $_GET['key'] == 'memberId' || $_GET['key'] == 'memCellPhone') {
						$arrWhere[] = 'pwr.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
					} else {
						$arrWhere[] = 'g.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
					}
				}
			}
		}

		//카테고리 검색
		if($_GET['category1'] || $_GET['category2'] || $_GET['category3']) {
			$category = $this->getCategory($_GET['categoryNo'], 'tree');
			if($_GET['category3']) {
				$selectCategory = $category[$_GET['category3']];
			} else if($_GET['category2']) {
				$selectCategory = $category[$_GET['category2']];
			} else if($_GET['category1']) {
				$selectCategory = $category[$_GET['category1']];
			}
			$productList = $this->getCategoryGoodsList($selectCategory);
			$arrWhere[] = 'g.product_no IN('.implode(',', $productList).')'; 
		}

		if($_GET['productNo']) {
			$arrWhere[] = 'g.product_no IN('.implode(',', $_GET['productNo']).')'; 
		}
		
		//신청상태
		if($_GET['applyFl']) {
			$arrWhere[] = 'pwr.applyFl = "'.$_GET['applyFl'].'"'; 
		}
		// 보증등록 기간별 조회
		if($_GET['searchRegDate'][0] && $_GET['searchRegDate'][1]) {
			$arrWhere[] = "pwr.regDt BETWEEN '{$_GET['searchRegDate'][0]} 00:00:00' AND '{$_GET['searchRegDate'][1]} 23:59:59'";
		}
		
		// 보증신청 기간별 조회
		if($_GET['searchApplyDate'][0] && $_GET['searchApplyDate'][1]) {
			$arrWhere[] = "pwr.warrantyApplyDt BETWEEN '{$_GET['searchApplyDate'][0]} 00:00:00' AND '{$_GET['searchApplyDate'][1]} 23:59:59'";
		}
		if(count($arrWhere) == 0) {
			$arrWhere[] = 1;
		}

		if(count($arrWhere) == 0) {
			$arrWhere[] = 1;
		}
		
		if($_GET['field']) {
			$field = $_GET['field'];
		} else {
			$field = 'pwr.*, g.detail_image, g.product_code, g.product_name';
		}

		$orderBy = 'pwr.sno DESC';
		if($_GET['sort']) {
			$orderBy = $_GET['sort'];
		}

		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_productWarrantyRegistration pwr
				LEFT JOIN 
					wg_goods g ON pwr.goodsNo = g.product_no
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY '.$orderBy.'
				'.$limit.'
		';
		$goodsList = $this->db->query_fetch($sql);
		$result['goodsList'] = $goodsList;

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(pwr.goodsNo) as cnt
				FROM 
					wg_productWarrantyRegistration pwr
				LEFT JOIN 
					wg_goods g ON pwr.goodsNo = g.product_no
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					pwr.goodsNo desc
		';
		$searchCnt = $this->db->query_fetch($sql)[0];

		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		$sql = '
				SELECT 
					count(pwr.goodsNo) as cnt
				FROM 
					wg_productWarrantyRegistration pwr
				LEFT JOIN 
					wg_goods g ON pwr.goodsNo = g.product_no
				ORDER BY
					pwr.goodsNo desc
		';
		$totalCnt = $this->db->query_fetch($sql)[0];
		$result['totalCnt'] = $totalCnt['cnt'];

		// 엑셀 다운로드가 아닐 때만 페이징 처리
		if (!$isExcel) {
			$pageHtml = '';
			$pageHtml .= '
				<a onclick="move_page(1)" class="first">첫 페이지</a>
				<a onclick="move_page('.$this->page->design['prevPage'].')">이전 페이지</a>
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
		}

		return $result;
	}

	/**
	 * 보증서 등록 목록 엑셀 다운로드
	 */
	public function getGoodsWarrantyListForExcel() {
		return $this->getGoodsWarrantyList(true);
	}

	public function saveVariant($getData) {
		// CRM 필드 제거로 저장 로직 단순화 (현재 변경 없음)
	}

	public function deleteVariant($getData) {
		if (!isset($getData['variantCodes']) || !is_array($getData['variantCodes'])) {
			return array(
				'success' => false,
				'message' => '삭제할 상품이 선택되지 않았습니다.'
			);
		}

		$deletedCount = 0;
		$errorCount = 0;

		foreach($getData['variantCodes'] as $variantCode) {
			$variantCode = addslashes($variantCode);
			$sql = "DELETE FROM wg_goodsOption WHERE variantCode = '{$variantCode}'";
			
			if ($this->db->query($sql)) {
				$deletedCount++;
			} else {
				$errorCount++;
			}
		}

		if ($deletedCount > 0) {
			return array(
				'success' => true,
				'message' => "삭제 완료: 성공 {$deletedCount}건" . ($errorCount > 0 ? ", 실패 {$errorCount}건" : "")
			);
		} else {
			return array(
				'success' => false,
				'message' => "삭제 실패: {$errorCount}건"
			);
		}
	}

	public function downloadExcel() {
		ini_set("display_errors", 0);
		error_reporting(0);
		try {
			$output = fopen('php://temp', 'r+');
			
			// UTF-8 BOM 추가
			fputs($output, "\xEF\xBB\xBF");
			
			// 헤더 작성
			$headers = array(
				'상품 고유번호',
				'옵션코드',
				'상품명',
				'옵션1',
				'옵션2',
				'옵션3',
				'소비자가',
				'판매가',
				'옵션가'
			);
			fputcsv($output, $headers);
			
			// 현재 검색 조건으로 데이터 조회 (페이징 없이)
			$goodsList = $this->getGoodsOptionList(true);
			
			// 데이터 작성
			foreach($goodsList as $row) {
				fputcsv($output, array(
					$row['product_no'],
					$row['variantCode'],
					$row['product_name'],
					$row['value1'],
					$row['value2'],
					$row['value3'],
					$row['retail_price'],
					$row['price'],
					$row['price'] + $row['additionalAmount']
				));
			}
			
			rewind($output);
			$csv = stream_get_contents($output);
			fclose($output);
			
			// 파일명에 현재 날짜 추가
			$filename = 'goods_option_list_' . date('Y-m-d_H-i-s') . '.csv';
			
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			echo $csv;
			exit;
			
		} catch (Exception $e) {
			return array(
				'success' => false,
				'message' => '엑셀 다운로드 중 오류가 발생했습니다: ' . $e->getMessage()
			);
		}
	}

	public function uploadExcel($file) {
		try {
			$handle = fopen($file['tmp_name'], 'r');
			
			// UTF-8 BOM 제거
			$bom = fread($handle, 3);
			if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
				rewind($handle);
			}
			
			// 헤더 건너뛰기
			fgetcsv($handle);
			
			$success = 0;
			$error = 0;
			
			while (($row = fgetcsv($handle)) !== FALSE) {
				if (count($row) >= 9) {  // CRM 컬럼 제거 반영
					$product_no = addslashes($row[0]);
					$variantCode = addslashes($row[1]);
					// CRM 필드 제거: 업로드에서 별도 업데이트 없음
					$sql = null;
					
					if ($sql && $this->db->query($sql)) {
						$success++;
					} else {
						$error++;
					}
				}
			}
			
			fclose($handle);
			
			return [
				'success' => true,
				'message' => "처리 완료: 성공 {$success}건, 실패 {$error}건"
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
			];
		}
	}
	

	public function updateWarrantApply($arrData)
	{
		$snoArray = $arrData['sno'] ?? [];
		$applyFlArray = $arrData['applyFl'] ?? [];
		
		$successCount = 0;
		$errorCount = 0;
		
		foreach ($snoArray as $sno) {
			if (isset($applyFlArray[$sno])) {
				$applyFl = $applyFlArray[$sno];

				$sql = "UPDATE wg_productWarrantyRegistration SET applyFl = '".$applyFl."' WHERE sno = ".$sno."";
				$this->db->query($sql);
				
			}
		}
	}
	

	public function registerWarrant($arrData)
	{
		try {
			$memNm = $arrData['memNm'] ?? '';
			$memCellPhone = $arrData['memCellPhone'] ?? '';
			$memberId = $arrData['memberId'] ?? '';
			$productsJson = $arrData['products'] ?? '';
			
			// 필수 파라미터 검증
			if (empty($memberId)) {
				return [
					'success' => false,
					'message' => '회원 ID가 필요합니다.'
				];
			}
			
			if (empty($productsJson)) {
				return [
					'success' => false,
					'message' => '제품 정보가 필요합니다.'
				];
			}
			
			// JSON 디코드
			$products = json_decode($productsJson, true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				return [
					'success' => false,
					'message' => '제품 정보 형식이 올바르지 않습니다.'
				];
			}
			
			if (!is_array($products) || empty($products)) {
				return [
					'success' => false,
					'message' => '등록할 제품이 없습니다.'
				];
			}

			
			// 각 제품별로 INSERT
			foreach ($products as $product) {
				$goodsNm = $product['productName'] ?? '';
				$goodsNo = $product['productNo'] ?? $product['id'] ?? '';
				$registerNo = $product['registrationNumber'] ?? '';
				$purchaseDt = $product['purchaseDate'] ?? '';
				$vendor = $product['purchasePlace'] ?? '';
				$warrantyDt = $product['warrantyPeriod'] ?? '';
				
				// 필수 필드 검증
				if (empty($goodsNm) || empty($registerNo) || empty($purchaseDt) || empty($vendor)) {
					$errorCount++;
					continue;
				}
				
				// 등록번호 중복 검사
				$checkSql = "SELECT COUNT(*) as cnt FROM wg_productWarrantyRegistration WHERE registerNo = '" . addslashes($registerNo) . "'";
				$checkResult = $this->db->query($checkSql);
				$row = $this->db->fetch($checkResult);
				
				if ($row['cnt'] > 0) {
					return [
						'success' => false,
						'message' => '등록번호가 이미 있습니다.'
					];
					$errorCount++;
					continue;
				}
				
				// 데이터 삽입
				$sql = "INSERT INTO wg_productWarrantyRegistration 
						(memberId, goodsNm, goodsNo, registerNo, purchaseDt, vendor, warrantyDt, applyFl, regDt, memNm, memCellPhone) 
						VALUES ('" . addslashes($memberId) . "', '" . addslashes($goodsNm) . "', '" . addslashes($goodsNo) . "', '" . addslashes($registerNo) . "', '" . addslashes($purchaseDt) . "', '" . addslashes($vendor) . "', '" . addslashes($warrantyDt) . "', 'b', NOW(), '" . addslashes($memNm) . "', '" . addslashes($memCellPhone) . "' )";
				
				if ($this->db->query($sql)) {
					$successCount++;
				} else {
					$errorCount++;
				}
			}
			
			return [
				'success' => $successCount > 0,
				'successCount' => $successCount,
				'errorCount' => $errorCount,
				'message' => $errorCount == 0 ? 
					$successCount . '개의 제품이 성공적으로 등록되었습니다.' : 
					'일부 제품 등록에 실패했습니다. (성공: ' . $successCount . '개, 실패: ' . $errorCount . '개)'
			];
			
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => '제품 등록 중 오류가 발생했습니다: ' . $e->getMessage()
			];
		}
	}

	public function getRegisteredWarrants($arrData)
	{
		try {
			$memberId = $arrData['memberId'] ?? '';
			
			// 필수 파라미터 검증
			if (empty($memberId)) {
				return [
					'success' => false,
					'message' => '회원 ID가 필요합니다.',
					'data' => []
				];
			}
			
			// 등록된 제품 목록 조회
			$sql = "SELECT pwr.*, g.detail_image FROM wg_productWarrantyRegistration pwr
					LEFT JOIN wg_goods g ON g.product_no = pwr.goodsNo
					WHERE pwr.memberId = '" . addslashes($memberId) . "' 
					ORDER BY pwr.regDt DESC";
			
			$result = $this->db->query($sql);

			$products = [];
			while ($row = $this->db->fetch($result)) {
				$products[] = [
					'sno' => $row['sno'],
					'detail_image' => $row['detail_image'],
					'productName' => $row['goodsNm'],
					'registrationNumber' => $row['registerNo'],
					'purchaseDate' => $row['purchaseDt'],
					'purchasePlace' => $row['vendor'],
					'warrantyPeriod' => $row['warrantyDt'],
					'applyType' => $row['applyType'],
					'phone' => $row['phone'],
					'email' => $row['email'],
					'imagePath' => $row['imagePath'],
					'applyFl' => $row['applyFl'],
					'regDt' => $row['regDt']
				];
			}
			
			return [
				'success' => true,
				'message' => '등록된 제품 목록을 조회했습니다.',
				'data' => $products
			];
			
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => '등록된 제품 목록 조회 중 오류가 발생했습니다: ' . $e->getMessage(),
				'data' => []
			];
		}
	}


	public function getWarrantyGoodsNmList($keyword = '', $page = 1, $limit = 20)
	{
		$arrWhere = [];
		
		// 에칭팬 카테고리(676) 인것들만 가져오기
		$sql = 'SELECT goodsList FROM wg_category WHERE category_no=676';
		$goodsNoArr = $this->db->query_fetch($sql)[0]['goodsList'];
		$arrWhere[] = ' product_no IN (' . $goodsNoArr . ')';

		// 검색어 조건
		if ($keyword) {
			$arrWhere[] = 'product_name LIKE "%' . addslashes($keyword) . '%"';
		}
		
		// WHERE 조건 설정
		if (count($arrWhere) == 0) {
			$arrWhere[] = '1';
		}
		
		// 페이징 계산
		$offset = ($page - 1) * $limit;
		
		// 전체 개수 조회
		$countSql = 'SELECT COUNT(*) as total FROM wg_goods WHERE ' . implode(' AND ', $arrWhere);
		$totalResult = $this->db->query_fetch($countSql);
		$totalCount = $totalResult[0]['total'];
		
		// 상품 목록 조회 - 정렬 순서: 프라이팬 → 궁중팬 → 사이즈 순
		$sql = 'SELECT *, 
				CASE 
					WHEN product_name LIKE "%프라이팬%" THEN 1
					WHEN product_name LIKE "%궁중팬%" THEN 2
					ELSE 3
				END as sort_order,
				CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(product_name, "cm", 1), " ", -1) AS UNSIGNED) as size_num
				FROM wg_goods 
				WHERE ' . implode(' AND ', $arrWhere) . ' 
				ORDER BY sort_order ASC, size_num DESC, product_name ASC 
				LIMIT ' . $offset . ', ' . $limit;
		
		$goodsList = $this->db->query_fetch($sql);
		
		// 페이징 정보 계산
		$totalPages = ceil($totalCount / $limit);
		
		return [
			'products' => $goodsList,
			'pagination' => [
				'currentPage' => $page,
				'totalPages' => $totalPages,
				'totalCount' => $totalCount,
				'limit' => $limit,
				'hasNext' => $page < $totalPages,
				'hasPrev' => $page > 1
			]
		];
	}

	/**
	 * 보증신청 처리
	 */
	public function warrantApply($arrData) {
		try {
			$sno = $arrData['sno'] ?? '';
			$applicationType = $arrData['applicationType'] ?? 'coating_defect';
			$phone = $arrData['phone'] ?? '';
			$email = $arrData['email'] ?? '';
			
			// 필수 파라미터 검증
			if (empty($sno)) {
				return [
					'success' => false,
					'message' => '제품 번호가 필요합니다.'
				];
			}
			
			if (empty($phone)) {
				return [
					'success' => false,
					'message' => '연락처가 필요합니다.'
				];
			}
			
			if (empty($email)) {
				return [
					'success' => false,
					'message' => '이메일이 필요합니다.'
				];
			}
			
			// 이메일 형식 검증
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return [
					'success' => false,
					'message' => '올바른 이메일 형식이 아닙니다.'
				];
			}
			
			// 해당 제품이 존재하는지 확인
			$checkSql = "SELECT COUNT(*) as cnt FROM wg_productWarrantyRegistration WHERE sno = '" . addslashes($sno) . "'";
			$checkResult = $this->db->query($checkSql);
			$row = $this->db->fetch($checkResult);
			
			if ($row['cnt'] == 0) {
				return [
					'success' => false,
					'message' => '해당 제품을 찾을 수 없습니다.'
				];
			}
			
			// 이미지 업로드 처리
			$imagePath = '';
			if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
				$imagePath = $this->uploadImage($_FILES['productImage']);
				if ($imagePath === false) {
					return [
						'success' => false,
						'message' => '이미지 업로드에 실패했습니다.'
					];
				}
			}
			
			// 보증신청 정보 업데이트
			$sql = "UPDATE wg_productWarrantyRegistration 
					SET applyType = '" . addslashes($applicationType) . "',
						phone = '" . addslashes($phone) . "',
						email = '" . addslashes($email) . "',
						imagePath = '" . addslashes($imagePath) . "',
						applyFl = 'w',
						modDt = NOW(),
						warrantyApplyDt = NOW()
					WHERE sno = '" . addslashes($sno) . "'";
			
			if ($this->db->query($sql)) {
				return [
					'success' => true,
					'message' => '보증신청이 완료되었습니다.'
				];
			} else {
				return [
					'success' => false,
					'message' => '보증신청 처리 중 오류가 발생했습니다.'
				];
			}
			
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => '보증신청 중 오류가 발생했습니다: ' . $e->getMessage()
			];
		}
	}

	/**
	 * 이미지 업로드 처리
	 */
	public function uploadImage($file) {
		try {
			$uploadDir = '../uploads/warranty_images/';
			if (!file_exists($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			
			$fileName = time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
			$filePath = $uploadDir . $fileName;
			
			if (move_uploaded_file($file['tmp_name'], $filePath)) {
				// 전체 URL로 반환
				return 'https://happycall4.mycafe24.com/uploads/warranty_images/' . $fileName;
			} else {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

	public function checkRegistrationNumberDuplicate($registerNo) {
		// 등록번호 중복 검사
		$checkSql = "SELECT COUNT(*) as cnt FROM wg_productWarrantyRegistration WHERE registerNo = '" . addslashes($registerNo) . "'";
		$checkResult = $this->db->query($checkSql);
		$row = $this->db->fetch($checkResult);
		
		$isDuplicate = ($row['cnt'] > 0);
		
		return [
			'success' => true,
			'isDuplicate' => $isDuplicate,
			'count' => $row['cnt']
		];
	}
}