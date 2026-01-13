-- wg_eventMileageCampaign 테이블에 자동발급 관련 컬럼 추가
ALTER TABLE `wg_eventMileageCampaign` 
ADD COLUMN IF NOT EXISTS `autoPayFl` VARCHAR(20) DEFAULT NULL COMMENT '자동발급 주기 구분 (weekDay: 매 요일마다, monthDay: 매월, yearDate: 매년)' AFTER `paymentFl`,
ADD COLUMN IF NOT EXISTS `weekDay` VARCHAR(50) DEFAULT NULL COMMENT '매 요일마다 선택된 요일 (쉼표 구분: mo,tu,we,th,fr,sa,su)' AFTER `autoPayFl`,
ADD COLUMN IF NOT EXISTS `monthDay` INT(11) DEFAULT NULL COMMENT '매월 지급일' AFTER `weekDay`,
ADD COLUMN IF NOT EXISTS `yearDate` VARCHAR(20) DEFAULT NULL COMMENT '매년 지급일 (MM-DD 형식)' AFTER `monthDay`,
ADD COLUMN IF NOT EXISTS `searchQuery` TEXT DEFAULT NULL COMMENT '자동발급 시 회원 검색 쿼리 (JSON 형식)' AFTER `yearDate`;




