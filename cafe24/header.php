<?php
$url = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($url);
$basename = basename($parsedUrl['path']);
$dirname = dirname($parsedUrl['path']);
$isAttendanceSection = strpos($dirname, '/attendance') !== false;

# 세션 유지 10시간
ini_set('session.gc_maxlifetime', 36000);

session_start();

if($_SESSION['cafe24adminFl'] != 'y') {

	if(strpos($_SERVER['HTTP_REFERER'], CAFE24_REFFERER_URL) !== false) {
		$_SESSION['cafe24adminFl'] = 'y';
	} else {
		echo '잘못된 접근입니다. cafe24 관리자 페이지 > 앱 > 관리하기 버튼을 통해 접속해주세요.';
		exit;
	}
} 

?>
<title>동국제약 건강몰 관리자</title>

<!-- jQuery 및 UI 라이브러리 -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> -->

<!-- jQuery Validate -->
<script type="text/javascript" src="../js/jquery.number_only.js"></script>

<!-- Bootstrap JS는 bootstrap-dialog.js 전에 로드되어야 합니다 -->
<script type="text/javascript" src="../js/bootstrap.js"></script>

<!-- Bootstrap Dialog는 Bootstrap 이후에 로드되어야 합니다 -->
<script type="text/javascript" src="../js/bootstrap-dialog.js"></script>
<script type="text/javascript" src="../js/bootstrap-filestyle.min.js"></script>
<script type="text/javascript" src="../js/underscore-min.js"></script>
<script type="text/javascript" src="../js/jquery_validate.js"></script>
<script type="text/javascript" src="../js/common2.js"></script>
<script type="text/javascript" src="../js/common.js"></script>
<script type="text/javascript" src="../js/clipboard.min.js"></script>


<!-- Datetimepicker 관련 라이브러리 -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ko.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>
<link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/css/bootstrap-datetimepicker-standalone.min.css" rel="stylesheet"/>
<link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>

<!-- Bootstrap Datepicker 관련 라이브러리 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ko.min.js"></script>

<link type="text/css" href="../css/cafe24.css" rel="stylesheet"/>
<link type="text/css" href="../css/custom.css" rel="stylesheet"/>



<style type="text/css">
	#content { max-width:1200px; margin:0 auto !important; padding: 100px 30px 30px !important;}
	.page-header{
		border-bottom: 0 !important;
	}
	.header{
		max-width: 1024px;
		margin:0 auto;
		padding:30px;
		padding-bottom:0;
	}
	.header_tap{
		border-bottom: 3px solid #228B22;
		padding-bottom: 3px;
		width: 100%;
		margin-bottom:10px;
	}
	.header_tap ul{
		font-size:0;
	}
	.header_tap ul li{
		cursor:pointer;
		font-size: 15px;
	}
	.header_tap ul li.active {
		display: inline-block;
		padding: 4px 15px;
		border-bottom: 2px solid #228B22;
		background: white;
		border-top: 0;
		font-size: 16px;
		font-weight:bold;
	}
	.header_tap ul li:not(.active) {
		background: #F6F6F6;
		display: inline-block;
		padding: 4px 15px;
		border-top: 1px solid #E6E6E6;
		border-left: 1px solid #E6E6E6;
		border-right: 1px solid #E6E6E6;
	}

</style>
<div class="header">
	<div class="header_tap">
		<ul>
			<li onclick="location.href='/cafe24/promotion/event_mileage_campaign_register.php'" class="<?php if($basename == 'event_mileage_campaign_register.php'){?>active<?php }?>">쇼핑지원금 등록</li>
			<li onclick="location.href='/cafe24/promotion/event_mileage_campaign_list.php'" class="<?php if($basename == 'event_mileage_campaign_list.php'){?>active<?php }?>">쇼핑지원금 리스트</li>
			<li onclick="location.href='/cafe24/eventMileage/apply_list.php'" class="<?php if($basename == 'apply_list.php'){?>active<?php }?>">쇼핑지원금 지급내역</li>
		</ul>
	</div>
</div>