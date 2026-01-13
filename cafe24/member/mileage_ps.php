<?php
/**
 * 마일리지 지급 이력 처리 페이지
 * 
 * 이 페이지는 마일리지 지급 이력 관련 처리를 담당합니다.
 * 
 * @author 동국제약
 * @version 1.0.0
 */

include "../../helpers/common_helper.php";
include "../../helpers/function_helper.php";

use Mileage\Mileage;
use Database\DB;

$mode = $_POST['mode'] ?? '';
$sno = $_POST['sno'] ?? '';

if (!in_array($mode, ['reapplyGoodsBenefit'])) {
    $result = [
        'success' => false,
        'message' => '잘못된 접근입니다.'
    ];
    echo json_encode($result);
    exit;
} else {
    try {
        $mileage = new Mileage();
        
        // 데이터 검증
        if (!$mode) {
            $result = ['success' => false, 'message' => '올바르지 않은 요청입니다.'];
            echo json_encode($result);
            exit;
        }

        if($mode === 'reapplyGoodsBenefit') {
            if (!$sno) {
                $result = [
                    'success' => false,
                    'message' => '일련번호가 필요합니다.'
                ];
                echo json_encode($result);
                exit;
            }

            $result = $mileage->reapplyGoodsBenefit($sno);
            if($result) {
                $result = [
                    'success' => true,
                    'message' => '혜택이 재적용되었습니다.'
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => '혜택 재적용 중 오류가 발생했습니다. (이미 성공한 항목이거나 처리할 수 없는 항목입니다.)'
                ];
            }
            echo json_encode($result);
            exit;
        }
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'message' => $e->getMessage()
        ];
        echo json_encode($result);
        exit;
    }
}
?>

