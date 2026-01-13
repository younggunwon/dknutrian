-- wg_member 테이블의 memId를 프라이머리 키로 변경
-- 주의: 실행 전에 기존 데이터에 중복된 memId가 없는지 확인하세요.

-- 중복 확인 쿼리 (실행 전에 확인)
-- SELECT memId, COUNT(*) as cnt FROM wg_member GROUP BY memId HAVING cnt > 1;

-- 1. 기존 프라이머리 키 제거 (sno가 프라이머리 키인 경우)
-- 주의: sno가 AUTO_INCREMENT인 경우, 먼저 AUTO_INCREMENT를 제거해야 합니다.
ALTER TABLE `wg_member` 
  MODIFY `sno` int(11) NOT NULL;

-- 2. 기존 PRIMARY KEY 제거
ALTER TABLE `wg_member` 
  DROP PRIMARY KEY;

-- 3. memId에 이미 인덱스나 UNIQUE 키가 있다면 제거
-- (실제 인덱스 이름은 다를 수 있으므로, SHOW INDEX FROM wg_member; 로 확인 후 수정 필요)
-- ALTER TABLE `wg_member` DROP INDEX `인덱스명`;
-- 또는
-- ALTER TABLE `wg_member` DROP INDEX `memId`;

-- 4. memId를 프라이머리 키로 설정
ALTER TABLE `wg_member` 
  ADD PRIMARY KEY (`memId`);

-- 참고: sno 컬럼은 그대로 유지되지만, AUTO_INCREMENT는 제거됩니다.
-- 필요하다면 이후에 sno에 인덱스를 추가할 수 있습니다:
-- ALTER TABLE `wg_member` ADD INDEX `idx_sno` (`sno`);

