-- 회원 그룹(등급) 테이블
CREATE TABLE IF NOT EXISTS `wg_memberGroup` (
  `sno` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
  `group_no` int(11) NOT NULL COMMENT '그룹 번호',
  `group_nm` varchar(100) NOT NULL COMMENT '그룹명',
  `reg_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일시',
  `mod_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  PRIMARY KEY (`sno`),
  UNIQUE KEY `idx_group_no` (`group_no`),
  KEY `idx_group_nm` (`group_nm`),
  KEY `idx_reg_date` (`reg_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 그룹(등급)';

-- 기본 데이터 삽입 (이미지에서 보이는 그룹들)
INSERT INTO `wg_memberGroup` (`sno`, `group_no`, `group_nm`, `reg_date`, `mod_date`) VALUES
(25, 26, 'VVIP', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(26, 25, 'VIP', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(27, 24, 'GOLD', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(28, 23, 'SILVER', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(29, 2, '워커', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(30, 14, '관리자', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(31, 18, '탈퇴', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(32, 4, '임직원', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(33, 5, '임직원 지인', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(34, 3, '러너', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(35, 17, '테스터', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(36, 1, 'FRIEND', '2025-03-26 14:23:44', '2025-12-23 13:06:51'),
(37, 27, 'PRIME', '2025-04-10 12:25:43', '2025-12-23 13:06:51'),
(38, 28, 'PARTNER', '2025-04-10 12:25:43', '2025-12-23 13:06:51')
ON DUPLICATE KEY UPDATE 
  `group_nm` = VALUES(`group_nm`),
  `mod_date` = VALUES(`mod_date`);



