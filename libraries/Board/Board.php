<?php
namespace Board;

use Database\DB;
use Cafe24\Cafe24;
use Request;
use Storage\Storage;
use Page\Page;

class Board
{
	public $db;

	public function __construct() {
		$this->db = new DB();
	}
	
	public function uploadPdf() {
		if(!$_POST['boardSno']) {
			throw new \Exception('게시글 번호가 없습니다.');
		}
		
		$file = $_FILES['estimateFile'];
		if(!$file || !$file['tmp_name']) {
			throw new \Exception('업로드된 파일이 없습니다.');
		}

		$sql = 'SELECT * FROM wg_board WHERE boardSno = ' . $_POST['boardSno'];
		$oldBoardFileData = $this->db->query_fetch($sql)[0];

		$file_regex = "/(\.(pdf))$/i";
		// 파일 업로드
		if (isset($file) && is_uploaded_file($file['tmp_name'])) {
			if (!preg_match($file_regex, $file['name'])) {
				throw new \Exception($file['name'] . '은(는) pdf 파일이 아닙니다.');
			}

			// 파일 크기 제한
			if ($file["size"] > 100000000) {
			throw new \Exception('최대 업로드 가능한 파일 크기는 10M 입니다.');
			}
			
			if (preg_match($file_regex, $file['name'])) {
				//기존 파일 삭제
				@unlink(str_replace('https://hoyatools.mycafe24.com', '/hoyatools/www', $oldBoardFileData['adminUploadFile']));
				$fileDir = '/hoyatools/www/fileStorage/board/pdf/' . date('Y') . '/' . date('m') . '/' . date('d') . '/';
				// 폴더가 존재하지 않으면 생성
				$dir = '';
				foreach(explode('/', $fileDir) as $val) {
					if($val) {
						$dir .= '/' .  $val;
						if (!file_exists($dir)) {
							mkdir($dir, 0777, true); // 0777은 권한을 의미하며, 새로운 폴더에 대한 모든 권한을 부여합니다.
						}
					}
				}
				$filePath = $fileDir . time() . $file["name"];

				// 업로드 상태 확인 및 파일 이동
				if (!move_uploaded_file($file['tmp_name'], $filePath)) {
					throw new \Exception('파일 업로드에 실패하였습니다. 관리자에게 문의해주세요.');
				} else {
					$fileUrl = str_replace('/hoyatools/www', 'https://hoyatools.mycafe24.com', $filePath);
				}			
			}
		}
		
		if($fileUrl) {
			$sql = 'UPDATE wg_board SET adminUploadFile="'.$fileUrl.'" WHERE boardSno = '. $_POST['boardSno'];
			$this->db->query($sql);

			# 카페24 sms 발송
			$this->sendSms($oldBoardFileData['boardSno'], $oldBoardFileData['writerId']);
		}

		return $fileUrl;
	}

	public function getBoardView() {
		$sql = "
			SELECT *
			FROM wg_board
			WHERE boardSno = {$_GET['boardSno']}
		";
		$boardView = $this->db->query_fetch($sql)[0];
		$boardView['selectProduct'] = json_decode($boardView['selectProduct'], 1);

		return $boardView;
	}
	
	public function saveBoardGoods($arrData) {
		$newArrData = [];
		foreach($arrData['productNo'] as $key => $val) {
			$newArrData[] = [
				'imageSrc' => $arrData['imageSrc'][$key],
				'productNo' => $arrData['productNo'][$key],
				'productName' => $arrData['productName'][$key],
				'optionName' => $arrData['optionName'][$key],
				'productCnt' => $arrData['productCnt'][$key],
			];
		}

		$selectProduct = json_encode($newArrData, JSON_UNESCAPED_UNICODE);
		$sql = "
			UPDATE wg_board
			SET selectProduct = '".$selectProduct."'
			WHERE boardSno = {$arrData['boardSno']}
		";
		$this->db->query($sql);
	}

	public function getBoardList() {
		//페이징
		$getValue['page'] = gd_isset($_GET['page'], 1);
		if(!$_GET['pageNum']) {
			$_GET['pageNum'] = '10';
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

		//회원 아이디 검색
		if($_GET['mypageWriterId']) {
			$arrWhere[] = 'b.writerId = "'.$_GET['mypageWriterId'].'"';
		}
		
		//회원 아이디 검색
		if($_GET['memberId']) {
			$arrWhere[] = 'b.member_id = "'.$_GET['memberId'].'"';
		}

		//검색어 검색
		if($_GET['keyword']) {
			if(!$_GET['key']){
				$arrWhere[] = '(b.title LIKE "%'.$_GET['keyword'].'%" OR b.content LIKE "%'.$_GET['keyword'].'%" OR writer LIKE "%'.$_GET['keyword'].'%" )';
			}else {
				$arrWhere[] = 'b.'.$_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
			}
		}

		//날짜 검색
		if($_GET['searchDate'][0]) {
				$arrWhere[] = 'b.created_date >= \''.$_GET['searchDate'][0].' 00:00:00\'';
		}
		if($_GET['searchDate'][1]) {
				$arrWhere[] = 'b.created_date <= \''.$_GET['searchDate'][1].' 23:59:59\'';
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

		$sql = '	
				SELECT 
					'.$field.'
				FROM 
					wg_board b
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					article_no DESC, board_no DESC
				'.$limit.'
		';

		$boardList = $this->db->query_fetch($sql);

		$result['boardList'] = $boardList;

		// 검색된 레코드 수
		$sql = '	
				SELECT 
					count(board_no) as cnt
				FROM 
					wg_board b
				WHERE 
					'.implode(' AND ', $arrWhere).'
				ORDER BY
					article_no desc, board_no desc
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt']; //검색 레코드 수
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		//전체갯수
		//회원 아이디 검색

		$sql = '
				SELECT 
					count(board_no) as cnt
				FROM 
					wg_board b
				'.$strWhere.'
				ORDER BY
					article_no desc, board_no desc
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
		
	# 카페24 API로 주기적으로 동기화
	public function updateBoard() {
		$cafe24 = new Cafe24();
		
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;

		$boardList = [];
		
		foreach(range(0, 100) as $val) {
			$url = "https://{$mallid}.cafe24api.com/api/v2/admin/boards/7/articles?limit=10&offset=" . ($val*10);

			$headers = array(
				'Authorization: Bearer ' . $access_token,
				'Content-Type: application/json',
				'X-Cafe24-Api-Version: ' . $version
			);

			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);
			$response = json_decode($response, 1);

			if (curl_errno($ch)) {
				echo 'Error: ' . curl_error($ch);
			}
			
			if($response['articles'][0]) { 
				$boardList = array_merge($boardList, $response['articles']);
			} else {
				break;
			}
		}
		curl_close($ch);

		foreach($boardList as $key => $val) {
			$sql = "
				SELECT *
				FROM wg_board
				WHERE board_no = 7 AND article_no = {$val['article_no']}
			";
			$result = $this->db->query_fetch($sql);
			//pdf파일 추출
			if(count($val['attach_file_urls']) > 0){
				foreach($val['attach_file_urls'] as $key2 => $val2) {
					if (strpos($val2['url'], '.pdf') !== false) {
						$pdfFileUrl = $val2['url'];
						break;
					}
				}
			}
			
			if($pdfFileUrl){
				$pdfFileUrl = $pdfFileUrl;
			}else {
				$pdfFileUrl = '';
			}

			if($result) {
				$sql = "
					UPDATE wg_board
						SET	title = '{$val['title']}'
						, modify_date = now()
						, article_no =  {$val['article_no']}
						, content = '{$val['content']}'
						, hit = '{$val['hit']}'
						, writer = '{$val['writer']}'
						, member_id = '{$val['member_id']}'
						, shop_no = '{$val['shop_no']}'
						, product_no = '{$product_no}'
						, attach_file_urls = '{$attach_file_urls}'
						WHERE title = '{$val['title']}' AND board_no = {$val['board_no']} AND article_no = ''
				";
				$this->db->query($sql);
			} else {
				$sql = "
					INSERT INTO wg_board(board_no, article_no, shop_no, url, 	writer, member_id, title, content, hit, attach_file_urls, created_date, product_no)
						VALUES({$val['board_no']}, {$val['article_no']}, '{$val['shop_no']}', '{$val['url']}', '{$val['writer']}', '{$val['member_id']}', '{$val['title']}', '{$val['content']}', '{$val['hit']}', '{$attach_file_urls}', '{$val['created_date']}', '{$product_no}')
				";

				$this->db->query($sql);
			}
		}

		echo "<script>alert('연동이 완료되었습니다'); location.href='/cafe24/board/board_list.php'</script>";
	}


	# 파일 업로드 저장
	public function saveUploadFile() {
		
	}

	
	# 게시글의 다운로드 링크 반환 API
	public function getBoardFileLink() {
		
	}

	public function deletBoard($arrData){
		//이미지및 pdf파일삭제
		foreach($arrData['article_no'] as $key => $val) {
			$sql = 'SELECT adminUploadFile, uploadFileUrl FROM wg_board WHERE article_no = '.$val;
			$boardData = $this->db->query_fetch($sql)[0];
			if($boardData['adminUploadFile']) {
				@unlink(str_replace('https://hoyatools.mycafe24.com', '/hoyatools/www', $boardData['adminUploadFile']));	
			}
			if($boardData['uploadFileUrl']) {
				@unlink(str_replace('https://hoyatools.mycafe24.com', '/hoyatools/www', $boardData['uploadFileUrl']));	
			}
		}

		$sql = 'DELETE FROM wg_board WHERE article_no IN ('.implode(',', $arrData['article_no']).') AND board_no=7';
		$this->db->query($sql);
	}

	public function saveBoard($arrData){
		//if($arrData['mode'] == 'register'){		
		//	//인서트
		//	$sql = 'INSERT INTO wg_store(storeNm, zipcode, zonecode, address, addressSub, storeCellPhone,  regDt) VALUES ("'.$arrData['storeNm'].'", "'.$arrData['zipcode'].'", "'.$arrData['zonecode'].'", "'.$arrData['address'].'", "'.$arrData['addressSub'].'" , "'.$arrData['storeCellPhone'].'", now())';
		//	$result = $this->db->query($sql);
		//	$insertSno =	$this->db->insert_id();
		//}else if($arrData['mode'] == 'modify'){
		//	//업데이트
		//	$sql = 'UPDATE wg_store SET storeNm="'.$arrData['storeNm'].'", zipcode="'.$arrData['zipcode'].'", zonecode="'.$arrData['zonecode'].'", address="'.$arrData['address'].'", addressSub="'.$arrData['addressSub'].'", storeCellPhone="'.$arrData['storeCellPhone'].'", modDt=now() WHERE sno='.$arrData['sno'].'';
		//	$result = $this->db->query($sql);
		//}
		

		$sql = 'SELECT * FROM wg_board WHERE sno = '.$arrData['sno'];
		$oldBoardFileData = $this->db->query_fetch($sql)[0];
		$oldImg = $oldBoardFileData['uploadFile'];

		$arrData['delBoardFile'] = gd_isset($arrData['delBoardFile'], false);

		// 비교삭제
		if ($arrData['delBoardFile']) {
			@unlink($oldImg);
			$sql = 'UPDATE wg_board SET uploadFile="" WHERE sno='.$arrData['sno'];
			$this->db->query($sql);
		}

		if($oldBoardFileData['sno']) {
			$sno = $oldBoardFileData['sno'];
		}else {
			$sno = $insertSno;
		}

		$image_regex = "/(\.(pdf))$/i";
		// 파일 업로드
		if (isset($_FILES['uploadFile']) && is_uploaded_file($_FILES['uploadFile']['tmp_name'])) {
			if (!preg_match($image_regex, $_FILES['uploadFile']['name'])) {
				alert($_FILES['uploadFile']['name'] . '은(는) pdf 파일이 아닙니다.');
			}
			
			if (preg_match($image_regex, $_FILES['uploadFile']['name'])) {
				//기존 파일 삭제
				@unlink($oldImg);
				$imgDir = '/patra0701/www/cafe24/board/pdf/'.$sno;
				@mkdir($imgDir, 0777);
				@chmod($imgDir, 0777);
				$imgPath = $imgDir . '/' .$_FILES['uploadFile']['name'];
				$arrData['uploadFile'] = '/patra0701/www/cafe24/board/pdf/'.$sno.'/'.$_FILES['uploadFile']['name'];

				move_uploaded_file($_FILES['uploadFile']['tmp_name'], $imgPath);
				chmod($imgPath, 0777);			
			}
		}else {
			if($arrData['uploadFileTmp'] && !$arrData['delBoardFile']){
				$arrData['uploadFile'] = $arrData['uploadFileTmp'];
			}else {
				$arrData['uploadFile'] = '';
			}
		}

		$sql = 'UPDATE wg_board SET uploadFile="'.$arrData['uploadFile'].'" WHERE sno='.$sno;
		$this->db->query($sql);
	}
	
	# 신규 게시글 작성시 insert
	public function insertBoard() {
		$result = [];
		$result['result'] = true;
		if($_FILES["uploadFile"]["name"]) {
			try {
				$uploadResult = $this->uploadAjaxFile();
				$fileUrl = $uploadResult['fileUrl'];
			} catch(\Exception $e) {
				$message = $e->getMessage();

				$result['result'] = false;
				$result['message'] = $message;
				return $result;
			}
		}

		$selectProduct = $_POST['selectProduct'];

		$sql = "INSERT INTO wg_board(boardTitle, boardContent, writerId, writerName, writerPhone, writerEmail, selectProduct, uploadFileUrl, regDt)
		VALUES('{$_POST['title']}', '{$_POST['content']}', '{$_POST['writerId']}', '{$_POST['writerName']}', '{$_POST['writerPhone']}', '{$_POST['writerEmail']}', '{$selectProduct}', '{$fileUrl}', now())";
		$this->db->query($sql);

		$this->sendAlertAdmin();

		return $result;
	}

	public function uploadAjaxFile() {
		$targetDir = "/hoyatools/www/fileStorage/board/" . date('Y') . '/' . date('m') . '/' . date('d') . '/';
		$targetFile = $targetDir . time() . $_FILES["uploadFile"]["name"];
		$uploadOk = 1;
		$imageFileType = strtolower(end(explode('.', $_FILES["uploadFile"]["name"])));

		// 파일이 이미 존재하는지 확인
		if (file_exists($targetFile)) {
			throw new \Exception('파일이 존재하지 않습니다. 관리자에게 문의해주세요.');
		}

		// 파일 크기 제한
		if ($_FILES["uploadFile"]["size"] > 100000000) {
		throw new \Exception('최대 업로드 가능한 파일 크기는 10M 입니다.');
		}

		// 특정 파일 형식 허용 여부 확인
		$allowType = ['jpg', 'png', 'jpeg', 'gif', 'pdf', 'psd', 'zip', 'ai', 'ppt', 'xls', 'xlsx', 'docx', 'doc', 'hwp'];
		if(!in_array($imageFileType, $allowType)) {
			throw new \Exception('허용하지 않는 확장자 파일입니다.');
		}

		// 폴더가 존재하지 않으면 생성
		$dir = '';
		foreach(explode('/', $targetDir) as $val) {
			if($val) {
				$dir .= '/' .  $val;
				if (!file_exists($dir)) {
					mkdir($dir, 0777, true); // 0777은 권한을 의미하며, 새로운 폴더에 대한 모든 권한을 부여합니다.
				}
			}
		}

		// 업로드 상태 확인 및 파일 이동
		if (!move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $targetFile)) {
			throw new \Exception('파일 업로드에 실패하였습니다. 관리자에게 문의해주세요.');
		} else {
			$return['fileUrl'] = str_replace('/hoyatools/www', 'https://hoyatools.mycafe24.com', $targetFile);
			return $return;
		}
	}

	public function sendSms($boardSno, $memberId) {
		$cafe24 = new Cafe24();
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;
		
		//$url = "https://{$mallid}.cafe24api.com/api/v2/admin/sms/senders";
		//$ch = curl_init($url);

		//$headers = array(
			//'Authorization: Bearer ' . $access_token,
			//'Content-Type: application/json',
			//'X-Cafe24-Api-Version: ' . $version
		//);

		//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//$response = curl_exec($ch);
		//$response = json_decode($response, 1);
		//gd_debug($response);
		//exit;
		
		// 데이터 설정
		$data = [
			"shop_no" => 1,
			"request" => [
				"sender_no" => 2,
				"content" => "안녕하세요. 호야재료사 입니다. 요청하신 견적서 발송드립니다. [확인 URL] https://hoyatool.com/estimate_view.html?boardSno={$boardSno}",
				"member_id" => [
					"{$memberId}",
				],
				"exclude_unsubscriber" => "F",
				"type" => "LMS"
			]
		];
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://{$mallid}.cafe24api.com/api/v2/admin/sms",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => json_encode($data),
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Bearer {$access_token}",
			"Content-Type: application/json",
			"X-Cafe24-Api-Version: {$version}"
		  ),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);

		$response = json_decode($response, 1);
		if($response['error']) {
			throw new \Exception($response['error']['message'], $response['error']['code']);
		}
		//gd_debug($data);
		//gd_debug($response);
	}

	public function sendAlertAdmin() {
		$cafe24 = new Cafe24();
		
		$mallid = CAFE24_MALL_ID;
		$access_token = $cafe24->getToken();
		$version = CAFE24_API_VERSION;
		
		// 데이터 설정
		$data = [
			"shop_no" => 1,
			"request" => [
				"sender_no" => 2,
				"content" => "고객님 견적문의 작성되었습니다.",
				"member_id" => [
					"djatkdgh9787",
				],
				"exclude_unsubscriber" => "F",
				"type" => "SMS"
			]
		];
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://{$mallid}.cafe24api.com/api/v2/admin/sms",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => json_encode($data),
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Bearer {$access_token}",
			"Content-Type: application/json",
			"X-Cafe24-Api-Version: {$version}"
		  ),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);

		$response = json_decode($response, 1);
		if($response['error']) {
			//throw new \Exception($response['error']['message'], $response['error']['code']);
		}

		$to = "hoyatool@naver.com";  // 받는 사람의 이메일 주소
		$subject = "cafe24 견적 요청건이 있습니다.";         // 이메일 제목
		$message = "cafe24 견적 요청건 확인 url = https://hoyatools.mycafe24.com/cafe24/board/board_list.php";  // 이메일 내용
		$headers = "From: hoyatool@naver.com";  // 보내는 사람의 이메일

		// 메일 발송
		if(mail($to, $subject, $message, $headers)) {
			//echo "Mail sent successfully!";
		} else {
			//echo "Mail sending failed.";
		}

	}

	/**
	 * QNA 목록 조회
	 */
	public function getQnaList() {
		// 페이징
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

		$limit = 'LIMIT '.$this->page->recode['start'] . ',' . $pageNum;

		// 검색어 검색
		$arrWhere = [];
		if($_GET['keyword']) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = '(question IN(\''.implode('\',\'', $keyword).'\') OR answer IN(\''.implode('\',\'', $keyword).'\'))';
				} else {
					$arrWhere[] = $_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = '(question LIKE "%'.$_GET['keyword'].'%" OR answer LIKE "%'.$_GET['keyword'].'%")';
				} else {
					$arrWhere[] = $_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
				}
			}
		}

		// 상태 검색
		if($_GET['status']) {
			$arrWhere[] = 'status = "'.$_GET['status'].'"';
		}

		if(count($arrWhere) == 0) {
			$arrWhere[] = 1;
		}

		$orderBy = 'sno DESC';
		if($_GET['sort']) {
			$orderBy = $_GET['sort'];
		}

		$sql = '
			SELECT 
				*
			FROM 
				wg_qna
			WHERE 
				'.implode(' AND ', $arrWhere).'
			ORDER BY '.$orderBy.'
			'.$limit.'
		';
		$qnaList = $this->db->query_fetch($sql);
		$result['qnaList'] = $qnaList;

		// 검색된 레코드 수
		$sql = '
			SELECT count(sno) as cnt
			FROM wg_qna
			WHERE '.implode(' AND ', $arrWhere).'
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$this->page->recode['total'] = $searchCnt['cnt'];
		$this->page->setPage();
		$result['searchCnt'] = $searchCnt['cnt'];

		// 전체갯수
		$sql = '
			SELECT count(sno) as cnt
			FROM wg_qna
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

	/**
	 * QNA 상세 조회
	 */
	public function getQnaView($sno) {
		$sql = 'SELECT * FROM wg_qna WHERE sno = '.$sno.'';
		$result = $this->db->query_fetch($sql);
		return $result ? $result[0] : null;
	}

	/**
	 * 프론트엔드용 QNA 목록 조회 (노출된 것만, 정렬순서대로, 검색 기능 포함, 페이지네이션)
	 */
	public function getFrontQnaList() {
		// 페이징 설정
		$getValue['page'] = gd_isset($_GET['page'], 1);
		$pageNum = 10; // 페이지당 5개
		$this->page = new Page($getValue['page']);
		$this->page->page['list'] = $pageNum;
		$this->page->block['cnt'] = 5;
		$this->page->setUrl($_SERVER['QUERY_STRING']);

		// 검색어 검색
		$arrWhere = [];
		$arrWhere[] = 'status = "y"'; // 노출된 것만
		
		if($_GET['keyword']) {
			$keyword = $_GET['keyword'];
			if(strpos($_GET['keyword'], ',') !== false) {
				$keyword = explode(',', $keyword);
				if(!$_GET['key']){
					$arrWhere[] = '(question IN(\''.implode('\',\'', $keyword).'\') OR answer IN(\''.implode('\',\'', $keyword).'\'))';
				} else {
					$arrWhere[] = $_GET['key'].' IN(\''.implode('\',\'', $keyword).'\')';
				}
			} else {
				if(!$_GET['key']){
					$arrWhere[] = '(question LIKE "%'.$_GET['keyword'].'%" OR answer LIKE "%'.$_GET['keyword'].'%")';
				} else {
					$arrWhere[] = $_GET['key'].' LIKE "%'.$_GET['keyword'].'%"';
				}
			}
		}

		$orderBy = 'sortOrder ASC, sno ASC';
		if($_GET['sort']) {
			$orderBy = $_GET['sort'];
		}

		// 검색된 레코드 수 먼저 조회
		$sql = '
			SELECT count(sno) as cnt
			FROM wg_qna
			WHERE '.implode(' AND ', $arrWhere).'
		';
		$searchCnt = $this->db->query_fetch($sql)[0];
		$totalCount = $searchCnt['cnt'];

		// Page 클래스에 총 개수 설정
		$this->page->setTotal($totalCount);
		$this->page->setPage();

		// LIMIT 설정
		$limit = 'LIMIT '.$this->page->recode['start'] . ',' . $this->page->recode['limit'];

		// QNA 목록 조회
		$sql = '
			SELECT 
				question,
				answer,
				sortOrder
			FROM 
				wg_qna
			WHERE 
				'.implode(' AND ', $arrWhere).'
			ORDER BY '.$orderBy.'
			'.$limit.'
		';
		$qnaList = $this->db->query_fetch($sql);

		// Page 클래스의 getPageWithPageData() 메서드 사용하여 페이지네이션 HTML 생성
		$pageHtml = $this->page->getPageWithPageData();

		$result = [];
		$result['qnaList'] = $qnaList;
		$result['pagination'] = $pageHtml;
		$result['totalCount'] = $totalCount;
		$result['currentPage'] = $this->page->page['now'];
		$result['totalPage'] = $this->page->page['total'];

		return $result;
	}

	public function registerQna()
	{
		try {
			$question = trim($_POST['question'] ?? '');
			$answer = trim($_POST['answer'] ?? '');
			$status = $_POST['status'] ?? 'y';
			$sortOrder = intval($_POST['sortOrder'] ?? 0);
			
			// 유효성 검사
			if (empty($question)) {
				return ['success' => false, 'message' => '제목을 입력해주세요.'];
			}
			
			if (empty($answer)) {
				return ['success' => false, 'message' => '내용을 입력해주세요.'];
			}
			
			if (mb_strlen($question) > 200) {
				return ['success' => false, 'message' => '제목은 200자 이하로 입력해주세요.'];
			}
			
			if (mb_strlen($answer) > 5000) {
				return ['success' => false, 'message' => '내용은 5000자 이하로 입력해주세요.'];
			}
			
			// QNA 등록
			$sql = "INSERT INTO wg_qna (question, answer, status, sortOrder, regDt, modDt) 
					VALUES ('".$question."', '".$answer."', '".$status."', ".$sortOrder.", NOW(), NOW())";
			$result = $this->db->query($sql);
			
			if ($result) {
				return ['success' => true, 'message' => 'QNA가 등록되었습니다.'];
			} else {
				return ['success' => false, 'message' => 'QNA 등록에 실패했습니다.'];
			}
			
		} catch (Exception $e) {
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}

	public function updateQna() {
		
		try {
			$sno = intval($_POST['sno'] ?? 0);
			$question = trim($_POST['question'] ?? '');
			$answer = trim($_POST['answer'] ?? '');
			$status = $_POST['status'] ?? 'y';
			$sortOrder = intval($_POST['sortOrder'] ?? 0);
			
			if ($sno <= 0) {
				return ['success' => false, 'message' => '잘못된 요청입니다.'];
			}
			
			// 유효성 검사
			if (empty($question)) {
				return ['success' => false, 'message' => '질문을 입력해주세요.'];
			}
			
			if (empty($answer)) {
				return ['success' => false, 'message' => '답변을 입력해주세요.'];
			}
			
			if (mb_strlen($question) > 200) {
				return ['success' => false, 'message' => '질문은 200자 이하로 입력해주세요.'];
			}
			
			if (mb_strlen($answer) > 5000) {
				return ['success' => false, 'message' => '답변은 5000자 이하로 입력해주세요.'];
			}

			// QNA 수정
			$sql = "UPDATE wg_qna 
					SET question = '".$question."', answer = '".$answer."', status = '".$status."', sortOrder = ".$sortOrder.", modDt = NOW() 
					WHERE sno = ".$sno."";
		
			$result = $this->db->query($sql);
			
			if ($result) {
				return ['success' => true, 'message' => 'QNA가 수정되었습니다.'];
			} else {
				return ['success' => false, 'message' => 'QNA 수정에 실패했습니다.'];
			}
			
		} catch (Exception $e) {
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}

	public function deleteQna() {
		
		try {
			$snoArray = $_POST['sno'] ?? [];
			
			if (empty($snoArray) || !is_array($snoArray)) {
				return ['success' => false, 'message' => '삭제할 QNA를 선택해주세요.'];
			}
			
			$snoList = array_map('intval', $snoArray);
			$snoList = array_filter($snoList, function($sno) { return $sno > 0; });
			
			if (empty($snoList)) {
				return ['success' => false, 'message' => '잘못된 요청입니다.'];
			}

			$sql = "DELETE FROM wg_qna WHERE sno IN (".implode(',', $snoArray).")";
			
			$result = $this->db->query($sql, $snoList);
			
			if ($result) {
				return ['success' => true, 'message' => count($snoList) . '개의 QNA가 삭제되었습니다.'];
			} else {
				return ['success' => false, 'message' => 'QNA 삭제에 실패했습니다.'];
			}
			
		} catch (Exception $e) {
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}

	public function updateStatus() {
		
		try {
			$snoArray = $_POST['sno'] ?? [];
			$status = $_POST['allStatus'] ?? '';
			
			
			if (empty($snoArray) || !is_array($snoArray)) {
				return ['success' => false, 'message' => '변경할 QNA를 선택해주세요.'];
			}

			if (!in_array($status, ['y', 'n'])) {
				return ['success' => false, 'message' => '잘못된 상태값입니다.'];
			}


			$sql = "UPDATE wg_qna SET status = '".$status."', modDt = NOW() WHERE sno IN (".implode(',', $snoArray).")";
			$result = $this->db->query($sql);
			
			if ($result) {
				$statusText = $status == 'y' ? '노출' : '비노출';
				return ['success' => true, 'message' => count($snoList) . '개의 QNA가 ' . $statusText . '로 변경되었습니다.'];
			} else {
				return ['success' => false, 'message' => '상태 변경에 실패했습니다.'];
			}
			
		} catch (Exception $e) {
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}

	public function updateSingleStatus() {
		try {
			$sno = intval($_POST['sno'] ?? 0);
			$status = $_POST['status'] ?? '';
			
			if ($sno <= 0) {
				return ['success' => false, 'message' => '잘못된 요청입니다.'];
			}
			
			if (!in_array($status, ['y', 'n'])) {
				return ['success' => false, 'message' => '잘못된 상태값입니다.'];
			}
			
			$sql = "UPDATE wg_qna SET status = '".$status."', modDt = NOW() WHERE sno = ".$sno."";
			$result = $this->db->query($sql);
			
			if ($result) {
				$statusText = $status == 'y' ? '노출' : '비노출';
				return ['success' => true, 'message' => 'QNA가 ' . $statusText . '로 변경되었습니다.'];
			} else {
				return ['success' => false, 'message' => '상태 변경에 실패했습니다.'];
			}
			
		} catch (Exception $e) {
			return ['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()];
		}
	}
}