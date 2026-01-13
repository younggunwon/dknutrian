<?php
namespace Workplace;

use Database\DB;

class Workplace
{
	public $db;

	public function __construct() {
		$this->db = new DB();
	}
	public function saveStore($arrData) {
		if($arrData['mode'] == 'register'){		
			//인서트
			$sql = 'INSERT INTO wg_store(storeNm, zipcode, zonecode, address, addressSub, storeCellPhone,  regDt) VALUES ("'.$arrData['storeNm'].'", "'.$arrData['zipcode'].'", "'.$arrData['zonecode'].'", "'.$arrData['address'].'", "'.$arrData['addressSub'].'" , "'.$arrData['storeCellPhone'].'", now())';
			$result = $this->db->query($sql);
			$insertSno =	$this->db->insert_id();
		}else if($arrData['mode'] == 'modify'){
			//업데이트
			$sql = 'UPDATE wg_store SET storeNm="'.$arrData['storeNm'].'", zipcode="'.$arrData['zipcode'].'", zonecode="'.$arrData['zonecode'].'", address="'.$arrData['address'].'", addressSub="'.$arrData['addressSub'].'", storeCellPhone="'.$arrData['storeCellPhone'].'", modDt=now() WHERE sno='.$arrData['sno'].'';
			$result = $this->db->query($sql);
		}

		$sql = 'SELECT * FROM wg_store WHERE sno = '.$arrData['sno'];
		$oldStoreData = $this->db->query_fetch($sql)[0];
		$oldImg = $oldStoreData['storeImg'];

		// 비교삭제
		if ($arrData['delStoreImg']) {
			$oldImg = explode('^|^', $oldImg);
			foreach($oldImg as $key => $val) {
				@unlink('/dnjsddurjs1/www/'.$val);
			}

			foreach($arrData['delStoreImg'] as $key => $val) {
				unset($arrData['storeImgTmp'][$key]);
			}
		}
		$sql = 'UPDATE wg_store SET storeImg="" WHERE sno='.$arrData['sno'];
		$this->db->query($sql);

		if($oldStoreData['sno']) {
			$sno = $oldStoreData['sno'];
		}else {
			$sno = $insertSno;
		}

		$image_regex = "/(\.(gif|jpe?g|png))$/i";

		// 아이콘 업로드

		foreach($_FILES['storeImg']['tmp_name'] as $key => $val) {
			if (isset($_FILES['storeImg']) && is_uploaded_file($val)) {
				if (!preg_match($image_regex, $_FILES['storeImg']['name'][$key])) {
					alert($_FILES['storeImg']['name'][$key] . '은(는) 이미지 파일이 아닙니다.');
				}

				if (preg_match($image_regex, $_FILES['storeImg']['name'][$key])) {
					$imgDir = '/patra0701/www/wg/'.$sno;
					@mkdir($imgDir, 0777);
					@chmod($imgDir, 0777);
					$imgPath = $imgDir . '/' .$_FILES['storeImg']['name'][$key];
					$arrData['storeImg'][] = 'wg/'.$sno.'/'.$_FILES['storeImg']['name'][$key];
					move_uploaded_file($val, $imgPath);
					chmod($imgPath, 0777);			
				}
			}else {
				if($arrData['storeImgTmp'][$key]){
					$arrData['storeImg'][] = $arrData['storeImgTmp'][$key];
				}
			}
		}
		$sql = 'UPDATE wg_store SET storeImg="'.implode('^|^', $arrData['storeImg']).'" WHERE sno='.$sno;
		$this->db->query($sql);
	}

	
	public function deletStore($sno)
	{
		//이미지 삭제
		foreach($sno as $key => $val) {
			$sql = 'SELECT * FROM wg_store WHERE sno = '.$val;
			$storeData = $this->db->query_fetch($sql)[0];
			if($storeData['storeImg']) {
				@unlink('/dnjsddurjs1/www/'.$storeData['storeImg']);	
			}
		}

		$sql = 'DELETE FROM wg_store WHERE sno IN ('.implode(',', $sno).')';
		$this->db->query($sql);

	}

	public function SearchStore($arrData){

		$strWhere=[];

		if($arrData['locate']){
			$strWhere[] = 's.address LIKE "%'.$arrData['locate'].'%"';
		}
		if($arrData['subLocate']){
			$strWhere[] = 's.address LIKE "%'.$arrData['subLocate'].'%"';
		}
		if($arrData['text']){
			$strWhere[] = '(s.address LIKE "%'.$arrData['text'].'%" OR s.storeNm LIKE "%'.$arrData['text'].'%")';
		}

		$strWhere = implode(' AND ', $strWhere);

		if($strWhere == null){
			$strWhere = 1;
		}

		$sql = 'SELECT * FROM wg_store s WHERE '.$strWhere;

		$searchData = $this->db->query_fetch($sql);
		
		return $searchData;
	}

	
}