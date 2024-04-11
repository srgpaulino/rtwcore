<?php

namespace App\Handler;

use Monolog\Logger;

class PhpError extends \Slim\Handlers\PhpError {

    private $logger;
    private $logTrace;

    public function __construct(Logger $logger, $displayErrorDetails = false, $logTrace = false)
    {
        $this->logger = $logger;
        $this->logTrace = $logTrace;
        parent::__construct($displayErrorDetails);
    }

    /**
    * Render HTML error page
    *
    * @param  \Exception $exception
    *
    * @return string
    */
    protected function renderHtmlErrorMessage(\Throwable $exception) : string
    {
        $log = $exception->getMessage() .
        ' in file ' .
        $exception->getFile() .
        ' on line ' .
        $exception->getLine() .
        "\r\n";

        if ((int)$this->logTrace === 1) {
            $log .= $exception->getTraceAsString();
        }

        $this->logger->alert($log);

        $title = 'Unexpected Error';

        if ($this->displayErrorDetails) {
            return parent::renderHtmlErrorMessage($exception);
        }

        $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        return $output;
    }

}
