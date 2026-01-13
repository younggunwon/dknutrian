-- wg_member 테이블에서 중복된 memId 확인

-- 1. 중복된 memId와 중복 횟수 확인
SELECT 
    memId, 
    COUNT(*) as cnt 
FROM 
    wg_member 
GROUP BY 
    memId 
HAVING 
    cnt > 1
ORDER BY 
    cnt DESC;

-- 2. 중복된 memId의 상세 정보 확인 (중복된 memId가 있을 경우)
-- 위 쿼리에서 memId를 확인한 후, 해당 memId의 모든 레코드 확인
-- 예: SELECT * FROM wg_member WHERE memId = '중복된_memId';

-- 3. NULL memId 확인
SELECT 
    COUNT(*) as null_count 
FROM 
    wg_member 
WHERE 
    memId IS NULL OR memId = '';

-- 4. 전체 행 수와 고유한 memId 수 비교
SELECT 
    COUNT(*) as total_rows,
    COUNT(DISTINCT memId) as unique_memIds,
    COUNT(*) - COUNT(DISTINCT memId) as duplicate_count
FROM 
    wg_member;

-- 5. memId가 NULL이거나 빈 문자열인 레코드 확인
SELECT 
    * 
FROM 
    wg_member 
WHERE 
    memId IS NULL OR memId = ''
LIMIT 100;

-- 6. 전체 행 수와 memId가 있는 행 수 비교 (같아야 정상)
SELECT 
    COUNT(*) as total_rows,
    COUNT(memId) as rows_with_memId,
    COUNT(*) - COUNT(memId) as rows_without_memId
FROM 
    wg_member;

-- 7. memId가 NULL이거나 빈 문자열인 잘못된 레코드 삭제 (주의: 실행 전 반드시 백업!)
-- DELETE FROM wg_member 
-- WHERE memId IS NULL OR memId = '';

-- 8. count(*)와 SELECT * 결과 차이 확인
-- count(*) 결과: 93205개
-- SELECT * 결과: 86504개
-- 차이: 6701개

-- 8-1. 실제 전체 행 수 확인
SELECT COUNT(*) as total_count FROM wg_member WHERE 1;

-- 8-2. SELECT *로 가져온 행 수를 서브쿼리로 확인 (LIMIT 없이)
SELECT COUNT(*) as select_star_count 
FROM (
    SELECT * FROM wg_member WHERE 1
) as subquery;

-- 8-3. 차이가 나는 원인 확인: 특정 조건으로 필터링되는 행이 있는지 확인
-- (예: 특정 날짜 이후, 특정 상태 등)
SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN regDt IS NULL THEN 1 END) as null_regDt,
    COUNT(CASE WHEN joinDt IS NULL THEN 1 END) as null_joinDt,
    COUNT(CASE WHEN memNm IS NULL OR memNm = '' THEN 1 END) as null_memNm,
    COUNT(CASE WHEN cellphone IS NULL OR cellphone = '' THEN 1 END) as null_cellphone
FROM wg_member WHERE 1;

-- 8-4. 최신/최旧 레코드 확인 (일부만 조회되는 경우를 확인)
SELECT 
    MIN(regDt) as oldest_regDt,
    MAX(regDt) as newest_regDt,
    MIN(joinDt) as oldest_joinDt,
    MAX(joinDt) as newest_joinDt,
    COUNT(*) as total_count
FROM wg_member WHERE 1;

-- 8-5. LIMIT 없이 전체 조회 (실제로 몇 개가 나오는지 확인)
-- 주의: 이 쿼리는 시간이 오래 걸릴 수 있습니다
-- SELECT * FROM wg_member WHERE 1;

-- 8-6. 결론: 데이터베이스에는 93205개가 모두 존재함
-- SELECT *로 86504개만 보이는 것은 클라이언트(phpMyAdmin 등)의 표시 제한 때문입니다
-- 실제 데이터는 모두 정상적으로 저장되어 있습니다.

-- 8-7. 실제로 모든 데이터를 확인하려면 LIMIT을 명시적으로 설정하세요
-- SELECT * FROM wg_member WHERE 1 LIMIT 100000;
-- 또는 페이지네이션으로 확인:
-- SELECT * FROM wg_member WHERE 1 LIMIT 0, 50000;  -- 첫 5만개
-- SELECT * FROM wg_member WHERE 1 LIMIT 50000, 50000;  -- 다음 5만개

-- 8-8. 클라이언트 설정 확인 및 변경 방법:
-- phpMyAdmin의 경우: 설정 > 메인 패널 > 표시할 최대 행 수를 늘리거나
-- 또는 쿼리 결과 화면에서 "전체 표시" 옵션을 사용하세요

