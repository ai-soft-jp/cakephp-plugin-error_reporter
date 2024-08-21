<?php
declare(strict_types=1);

namespace ErrorReporter\Middleware;

use ErrorReporter\ErrorReporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * ErrorReporter Middleware
 */
class ErrorReporterMiddleware implements MiddlewareInterface
{
    /**
     * Process middelware.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object.
     * @param \Psr\Http\Server\RequestHandelerInterface $handler Request handler.
     * @return \Psr\Http\Message\ResponseInterface Response object.
     * @throws \Throwable Any exceptions can be thrown.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            ErrorReporter::instance()->handleException($exception, $request);
            throw $exception;
        }
    }
}
