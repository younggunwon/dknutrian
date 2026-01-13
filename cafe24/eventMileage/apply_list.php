<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;
use Page\Page;

$eventMileage = new EventMileage();

// 필터 파라미터 수집
$params = [
    'keyword' => $_GET['keyword'] ?? '',
    'key' => $_GET['key'] ?? 'memId',
    'mode' => $_GET['mode'] ?? '',
    'page' => $_GET['page'] ?? 1,
    'limit' => $_GET['pageNum'] ?? 100
];

// 데이터 조회
$data = $eventMileage->getEventMileageHistory($params);

// 뷰 변수 설정
$rows = $data['list'];
$searchCnt = $data['totalCount'];
$totalCnt = $data['totalCountAbsolute'];

// 페이징 처리 (기존 Page 클래스 활용)
$page = new Page($params['page']);
$page->page['list'] = $params['limit'];
$page->block['cnt'] = 10;
$page->recode['total'] = $searchCnt;
$page->setPage();
$page->setUrl($_SERVER['QUERY_STRING']);

$select['mode'][$params['mode']] = 'selected';
$key = $params['key'];
$keyword = $params['keyword'];
?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
    <link type="text/css" href="../css/cafe24.css" rel="stylesheet"/>
    <form id="frmSearch" name="frmSearch" method="get" class="js-form-enter-submit">
        <div class="page-header">
            <div class="table-title">
                쇼핑지원금 지급/사용 내역
            </div>
        </div>
        
        <div class="search-detail-box">
            <table class="table table-cols">
                <colgroup>
                    <col class="width-md"/>
                    <col>
                    <col class="width-md"/>
                    <col/>
                </colgroup>
                <tbody>
                <tr>
                    <th>검색</th>
                    <td colspan="3">
                        <div class="form-inline">
                            <select name="key" class="form-control" style="width:150px;">
                                <option value="memId" <?= ($key === 'memId') ? 'selected' : '' ?>>회원아이디</option>
                                <option value="orderId" <?= ($key === 'orderId') ? 'selected' : '' ?>>주문번호</option>
                                <option value="contents" <?= ($key === 'contents') ? 'selected' : '' ?>>내용</option>
                            </select>
                            <input type="text" name="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="검색어를 입력하세요" style="margin-left: 10px; width:300px;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>구분</th>
                    <td colspan="3">
                        <select name="mode" class="form-control" style="width:150px;">
                            <option value="">전체</option>
                            <option value="add" <?= $select['mode']['add'] ?? '' ?>>지급(+)</option>
                            <option value="remove" <?= $select['mode']['remove'] ?? '' ?>>차감(-)</option>
                            <option value="restore" <?= $select['mode']['restore'] ?? '' ?>>복구(+)</option>
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="table-btn">
            <input type="submit" value="검색" class="btn btn-lg btn-black">
        </div>

        <div class="table-header">
            <div class="pull-left">
                검색 <strong><?= number_format($searchCnt) ?></strong>개 /
                전체 <strong><?= number_format($totalCnt) ?></strong>개
            </div>
            <div class="pull-right">
                <select id="pageNum" name="pageNum" class="form-control" style="width:150px;">
                    <option value="10" <?= $params['limit'] == 10 ? 'selected' : '' ?>>10 개씩 보기</option>
                    <option value="30" <?= $params['limit'] == 30 ? 'selected' : '' ?>>30 개씩 보기</option>
                    <option value="50" <?= $params['limit'] == 50 ? 'selected' : '' ?>>50 개씩 보기</option>
                    <option value="100" <?= $params['limit'] == 100 ? 'selected' : '' ?>>100 개씩 보기</option>
                    <option value="500" <?= $params['limit'] == 500 ? 'selected' : '' ?>>500 개씩 보기</option>
                </select>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-rows">
            <thead>
                <tr>
                    <th>번호</th>
                    <th>일시</th>
                    <th>회원ID</th>
                    <th>구분</th>
                    <th>금액</th>
                    <th>전 잔액</th>
                    <th>후 잔액</th>
                    <th>내용</th>
                    <th>유효기간</th>
                    <th>주문번호</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ((array)$rows as $r) { ?>
                <tr>
                    <td class="center"><?= (int)$r['sno'] ?></td>
                    <td class="center"><?= htmlspecialchars($r['regDt'] ?? '') ?></td>
                    <td class="center"><?= htmlspecialchars($r['memId'] ?? '') ?></td>
                    <td class="center">
                        <?php
                        switch($r['mode']) {
                            case 'add': echo '<span class="text-blue">지급</span>'; break;
                            case 'remove': echo '<span class="text-red">차감</span>'; break;
                            case 'restore': echo '<span class="text-green">복구</span>'; break;
                            default: echo htmlspecialchars($r['mode']);
                        }
                        ?>
                    </td>
                    <td class="right">
                        <strong><?= ($r['mode'] == 'remove' ? '-' : '+') . number_format((int)$r['eventMileage']) ?></strong>
                    </td>
                    <td class="right"><?= number_format((int)$r['beforeEventMileage']) ?></td>
                    <td class="right"><?= number_format((int)$r['afterEventMileage']) ?></td>
                    <td><?= htmlspecialchars($r['contents'] ?? '') ?></td>
                    <td class="center"><?= in_array($r['mode'], ['add', 'restore']) ? htmlspecialchars($r['expiryDate'] ?? '') : '-' ?></td>
                    <td class="center"><?= htmlspecialchars($r['orderId'] ?? '-') ?></td>
                </tr>
            <?php } ?>
            <?php if (empty($rows)) { ?>
                <tr><td colspan="10" class="center empty_table">내역이 없습니다.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="center">
        <?= $page->getPage(); ?>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#pageNum').change(function() {
        $('#frmSearch').submit();
    });
});
</script>

<?php require_once './../footer.php'; ?>
