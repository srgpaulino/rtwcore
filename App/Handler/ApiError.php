<?php

namespace App\Handler;

use TFCLog\PDOLogger;

class Error extends \Slim\Handlers\ApiError {

    private $logger;
    private $logTrace;
    private $output;

    public function __construct(PDOLogger $logger, $displayErrorDetails, $logTrace = false){
        $this->logger = $logger;
        $this->logTrace = $logTrace;
        parent::__construct($displayErrorDetails);
    }

    /**
     * Render API error response
     *
     * @param  \Exception $exception
     *
     * @return string
     */
    protected function renderApiErrorMessage($e) : array
    {

        $log = $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine() . "\r\n";
        if((int)$this->logTrace === 1) $log .= $e->getTraceAsString();

        dd($log);

        $output = [
            'error' => [
                'message'       => $e->getMessage(),
                'type'          => $this::THROWABLE[$e->getCode]['type'],
                'code'          => $e->getCode(),
                'subcode'       => $this::THROWABLE[$e->getCode]['subcode'],
                'title'         => $this::THROWABLE[$e->getCode]['title'],
                'details'       => $e->getTrace()
            ]
        ];

        if( in_array($e->getCode(), $this::LOG) ) {
            $this->logger->log($log);
        }
        if( in_array($e->getCode(), $this::DEBUG) ) {
            $this->logger->debug($log);
        }
        if( in_array($e->getCode(), $this::INFO) ) {
            $this->logger->info($log);
        }
        if( in_array($e->getCode(), $this::NOTICE) ) {
            $this->logger->notice($log);
        }
        if( in_array($e->getCode(), $this::WARNING) ) {
            $this->logger->warning($log);
        }
        if( in_array($e->getCode(), $this::ERROR) ) {
            $this->logger->error($log);
        }
        if( in_array($e->getCode(), $this::CRITICAL) ) {
            $this->logger->critical($log);
        }
        if( in_array($e->getCode(), $this::ALERT) ) {
            $this->logger->alert($log);
        }
        if( in_array($e->getCode(), $this::EMERGENCY) ) {
            $this->logger->emergency($log);
        }

        return $output;
    }

}
