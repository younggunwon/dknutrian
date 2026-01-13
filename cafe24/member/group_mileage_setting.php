<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Database\DB;
use Cafe24\Cafe24;

$db = new DB();
$cafe24 = new Cafe24();

// 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grades'])) {
    foreach ((array)$_POST['grades'] as $groupNo => $row) {
        $groupNo = (int)$groupNo;
        $cashExtraRate = (float)($row['rate'] ?? 0);
        // 등급명은 수정 불가: 비율만 업데이트
        $sql = "UPDATE wg_memberGrade SET cashExtraRate = {$cashExtraRate}, modDt = NOW() WHERE groupNo = {$groupNo}";
        $db->query($sql);
    }
    echo "<script>alert('저장되었습니다.'); location.href='group_mileage_setting.php';</script>";
    exit;
}

// 페이지 로드 시: 카페24 등급 동기화 → 로컬 테이블 업데이트(존재하지 않으면 추가, 이름 변경 반영)
$accessToken = $cafe24->getToken();
$url = "https://" . CAFE24_MALL_ID . ".cafe24api.com/api/v2/admin/customergroups?limit=200";
$response = $cafe24->simpleCafe24Api([
    'url' => $url,
    'method' => 'GET'
]);

$grades = [];
// API 스펙: 'customergroups' 키 사용
if (isset($response['customergroups']) && is_array($response['customergroups'])) {
    foreach ($response['customergroups'] as $g) {
        $groupNo = (int)$g['group_no'];
        $gradeName = addslashes($g['group_name'] ?? '');
        // 로컬에 없으면 추가, 있으면 이름만 동기화(비율은 유지)
        $row = $db->query_fetch("SELECT cashExtraRate FROM wg_memberGrade WHERE groupNo = {$groupNo}");
        if ($row) {
            $db->query("UPDATE wg_memberGrade SET gradeName='{$gradeName}', modDt = NOW() WHERE groupNo = {$groupNo}");
        } else {
            $db->query("INSERT INTO wg_memberGrade (groupNo, gradeName, cashExtraRate, regDt, modDt) VALUES ({$groupNo}, '{$gradeName}', 0.00, NOW(), NOW())");
        }
    }
}

// 출력용 데이터 조회
$rows = $db->query_fetch("SELECT groupNo, gradeName, cashExtraRate FROM wg_memberGrade ORDER BY groupNo ASC");
?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
    <div class="table-title ">등급별 현금결제 추가 적립율 설정 <button type="button" class="btn btn-sm btn-gray" onclick="location.href='/api/order.php?mode=awardMileage'">적립 실행</button></div>
    <form method="post">
        <div class="table-responsive">
            <table class="table table-rows">
                <thead>
                    <tr>
                        <th style="min-width: 80px">등급번호</th>
                        <th style="min-width: 200px">등급명</th>
                        <th style="min-width: 160px">현금결제 추가 적립율(%)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ((array)$rows as $r) { ?>
                    <tr>
                        <td class="center"><?= (int)$r['groupNo'] ?></td>
                        <td class="center"><?= htmlspecialchars($r['gradeName']) ?><?php if($r['gradeName'] == '사업자') { echo '(50만원 이상 결제시)'; } ?></td>
                        <td class="center">
                            <input type="number" step="0.01" min="0" name="grades[<?= (int)$r['groupNo'] ?>][rate]" value="<?= (float)$r['cashExtraRate'] ?>" class="form-control" style="width: 160px;"/>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (empty($rows)) { ?>
                    <tr><td colspan="3" class="center">등록된 회원등급이 없습니다. 잠시 후 다시 시도해주세요.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="table-btn">
            <button type="submit" class="btn btn-lg btn-black">저장</button>
        </div>
    </form>
</div>

<?php require_once './../footer.php'; ?>


