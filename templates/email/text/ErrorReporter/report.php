<?php
use Aws\Exception\AwsException;
use Cake\Http\ServerRequest;
use Psr\Message\Http\RequestInterface;

error_reporting(0);

echo "An error occured in processing:\n";
if ($request) {
    $path = $request->getUri()->getPath();
    $query = $request->getUri()->getQuery();
    if ($query !== '') {
        $path .= "?{$query}";
    }
    if ($request instanceof ServerRequest) {
        $remoteAddr = $request->clientIp();
    } else {
        $params = $request->getServerParams();
        $remoteAddr = $params['REMOTE_ADDR'] ?? null;
    }
    printf("    %s %s\n", $request->getMethod(), $path);
    printf("\n");
    printf("Remote Host = %s\n", $remoteAddr ?: 'N/A');
    printf("User Agent = %s\n", $request->getHeaderLine('User-Agent') ?: 'N/A');
    printf("Referer = %s\n", $request->getHeaderLine('Referer') ?: 'N/A');
} else {
    printf("    Command Line: %s\n", $GLOBALS['argv'][0] ?? '(unknown)');
}
echo "\n";

$number = 0;
do {
    printf("--- Exception(%d)\n", $number);
    printf("%s:\n", get_class($exception));
    printf("%s\n", trim($exception->getMessage()));
    if ($exception->getFile() !== null) {
        printf("File: %s\n", $this->Error->filterPath($exception->getFile()));
    }
    if ($exception->getLine() !== null) {
        printf("Line: %s\n", $exception->getLine());
    }
    printf("Code: %s\n", $exception->getCode());

    if (is_callable([$exception, 'getAttributes'])) {
        $attributes = $exception->getAttributes();
        if (!empty($attributes)) {
            echo "\n";
            echo "--- Attributes\n";
            echo $this->Error->formatArray($attributes);
        }
    }

    if (isset($exception->queryString)) {
        echo "\n";
        echo "--- SQL\n";
        printf("%s\n", trim($exception->queryString));
    }

    if ($exception instanceof AwsException) {
        echo "\n";
        echo "--- AWS\n";
        echo "Connection Error: " . ($exception->isConnectionError() ? 'Yes' : 'No') . "\n";
        echo "Type: {$exception->getAwsErrorType()}\n";
        echo "Code: {$exception->getAwsErrorCode()}\n";
        echo "Message: {$exception->getAwsErrorMessage()}\n";
        echo "Request Id: {$exception->getAwsRequestId()}\n";
        echo "Command: {$exception->getCommand()->getName()}\n";
    }

    if (is_callable([$exception, 'getRequest'])) {
        $xreq = $exception->getRequest();
        if ($xreq instanceof RequestInterface) {
            echo "\n";
            echo "--- Request\n";
            echo "{$xreq->getMethod()} {$xreq->getUri()} HTTP/{$xreq->getProtocolVersion()}\n";
            foreach ($xreq->getHeaders() as $key => $values) {
                foreach ((array)$values as $value) {
                    echo "{$key}: {$value}\n";
                }
            }
        }
    }

    echo "\n";
    echo "--- Backtrace\n";
    echo $this->Error->formatBacktrace($exception->getTrace());
    echo "\n";

    $exception = $exception->getPrevious();
    ++$number;
} while ($exception instanceof Throwable);

if (!empty($outputs)) {
    echo "--- Outputs:\n";
    echo "{$outputs}\n";
    echo "\n";
}

if ($request) {
    $vars = [
        'Environments' => $request->getServerParams(),
        'Query String' => $request->getQueryParams(),
        'Post Data' => $request->getData(),
        'Uploaded Files' => $request->getUploadedFiles(),
        'Cookies' => $request->getCookieParams(),
    ];
    if ($request instanceof ServerRequest) {
        $vars['Session'] = $request->getSession()->read();
    }
} else {
    $vars = [
        'Arguments' => $GLOBALS['argv'] ?? [],
        'Environments' => $_ENV,
    ];
}
foreach ($vars as $name => $var) {
    if (!empty($var)) {
        echo "--- {$name}\n";
        echo $this->Error->formatArray($var);
        echo "\n";
    }
}
