<?php

namespace Log;

class Log
{
    /**
     * 로그 타입 상수
     */
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const DEBUG = 'DEBUG';
    
    /**
     * 로그 저장 디렉토리
     */
    private $logPath;
    
    /**
     * 로그 파일명 포맷
     */
    private $fileNameFormat = 'Y-m-d';
    
    /**
     * 로그 파일 확장자
     */
    private $fileExtension = '.log';
    
    /**
     * 생성자
     * 
     * @param string $logPath 로그 저장 디렉토리 (기본값: logs)
     */
    public function __construct($logPath = null)
    {
        if ($logPath === null) {
            $this->logPath = dirname(__FILE__) . '/logs';
        } else {
            $this->logPath = $logPath;
        }
        
        // 로그 디렉토리가 없으면 생성
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * 로그 파일 이름을 생성하고 디렉토리를 확인/생성
     * 
     * @return string 로그 파일 전체 경로
     */
    private function getLogFilePath()
    {
        $yearMonth = date('ym'); // 2601
        $day = (int)date('j');
        
        if ($day <= 10) {
            $period = '1';
        } elseif ($day <= 20) {
            $period = '2';
        } else {
            $period = '3';
        }
        
        $subDir = "{$yearMonth}-{$period}"; // 2601-1
        $fullDirPath = $this->logPath . '/' . $subDir;
        
        if (!file_exists($fullDirPath)) {
            mkdir($fullDirPath, 0755, true);
        }
        
        $fileName = date($this->fileNameFormat) . $this->fileExtension;
        return $fullDirPath . '/' . $fileName;
    }
    
    /**
     * 로그 메시지 포맷 생성
     * 
     * @param string $type 로그 타입
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return string 포맷된 로그 메시지
     */
    private function formatLogMessage($type, $message, $context = [])
    {
        $date = date('Y-m-d H:i:s');
        $formattedMessage = "[{$date}] [{$type}] {$message}";
        
        // 컨텍스트 데이터가 있으면 추가
        if (!empty($context)) {
            $contextStr = json_encode($context, JSON_UNESCAPED_UNICODE);
            $formattedMessage .= " Context: {$contextStr}";
        }
        
        return $formattedMessage . PHP_EOL;
    }
    
    /**
     * 로그 기록
     * 
     * @param string $type 로그 타입
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function log($type, $message, $context = [])
    {
        $logFilePath = $this->getLogFilePath();
        $logMessage = $this->formatLogMessage($type, $message, $context);
        
        return file_put_contents($logFilePath, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 정보 로그 기록
     * 
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function info($message, $context = [])
    {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * 경고 로그 기록
     * 
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function warning($message, $context = [])
    {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * 오류 로그 기록
     * 
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function error($message, $context = [])
    {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * 디버그 로그 기록
     * 
     * @param string $message 로그 메시지
     * @param array $context 추가 컨텍스트 데이터
     * @return bool 성공 여부
     */
    public function debug($message, $context = [])
    {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * 로그 파일 이름 포맷 설정
     * 
     * @param string $format 날짜 포맷 (date 함수 포맷 사용)
     * @return $this
     */
    public function setFileNameFormat($format)
    {
        $this->fileNameFormat = $format;
        return $this;
    }
    
    /**
     * 로그 파일 확장자 설정
     * 
     * @param string $extension 파일 확장자 (.log, .txt 등)
     * @return $this
     */
    public function setFileExtension($extension)
    {
        $this->fileExtension = $extension;
        return $this;
    }
    
    /**
     * 로그 디렉토리 경로 설정
     * 
     * @param string $path 로그 디렉토리 경로
     * @return $this
     */
    public function setLogPath($path)
    {
        $this->logPath = $path;
        
        // 로그 디렉토리가 없으면 생성
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        return $this;
    }
} 