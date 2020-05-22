<?php
declare(strict_types=1);

namespace ErrorReporter;

use Cake\Controller\Exception\MissingActionException;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Routing\Exception\MissingRouteException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * ErrorReporter
 */
class ErrorReporter
{
    use InstanceConfigTrait {
        setConfig as _setConfig;
    }

    /**
     * Default configuration
     */
    protected $_defaultConfig = [
        'profile' => 'errorReport',
        'template' => 'ErrorReporter.ErrorReporter/report',
        'ignoreExceptions' => [
            MissingActionException::class,
            RecordNotFoundException::class,
            HttpException::class,
            MissingControllerException::class,
            MissingRouteException::class,
        ],
        'filter' => '/password|credentials|token|card_|security_|HTTP_COOKIE/i',
        'pathMap' => [
            'CAKE' => CORE_PATH,
            'VENDOR' => ROOT . DS . 'vendor' . DS,
            'ROOT' => ROOT . DS,
            'APP', 'CONFIG', 'WWW_ROOT', 'TESTS', 'CACHE', 'TMP', 'LOGS', 'CAKE',
        ],
    ];

    /**
     * Singleton
     */
    protected static $_instance = null;

    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * Singleton
     *
     * @return \ErrorReporter\ErrorReporter A singleton instance
     */
    public static function instance(): ErrorReporter
    {
        if (static::$_instance === null) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * Sets a configuration.
     *
     * @param array $args Arguments.
     * @return \ErrorReporter\ErrorReporter $this
     */
    public function setConfig(...$args): ErrorReporter
    {
        $this->_setConfig(...$args);

        $profile = $this->getConfig('profile');
        if (!Email::getConfig($profile)) {
            throw new RuntimeException(
                "Missing email profile `{$profile}'. " .
                "Add profile or specify profile at ErrorReporter.profile."
            );
        }

        return $this;
    }

    /**
     * Handle an exception.
     *
     * @param \Throwable $exception Exception object.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request Request object.
     * @return bool Whether report sent or not.
     */
    public function handleException(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        if ($this->checkReport($exception)) {
            $this->report($exception, $request);
            return true;
        }
        return false;
    }

    /**
     * Check whether to report an exception or not.
     *
     * @param \Throwable $exception Exception object.
     * @return bool
     */
    protected function checkReport(Throwable $exception): bool
    {
        foreach ((array)$this->getConfig('ignoreExceptions') as $className) {
            if ($exception instanceof $className) {
                return false;
            }
        }
        return true;
    }

    /**
     * Deliver an error report.
     *
     * @param \Throwable $exception Exception object.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request Request object.
     * @return void
     */
    public function report(Throwable $exception, ?ServerRequestInterface $request = null): void
    {
        try {
            $this->_report($exception, $request);
        } catch (Throwable $nestedException) {
            $this->_nestedException($nestedException);
            throw $nestedException;
        }
    }

    /**
     * Deliver an error report (internal).
     *
     * @param \Throwable $exception Exception object.
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object.
     * @return void
     */
    protected function _report(Throwable $exception, ?ServerRequestInterface $request): void
    {
        $classNames = explode('\\', get_class($exception));
        $className = end($classNames);

        if ($request) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            if (isset($uri->base)) {
                $path = $uri->base . $path;
            }
            $subject = sprintf(
                "[%s] %s (%s %s)",
                $request->getHeaderLine('Host'),
                $className,
                $request->getMethod(),
                $path,
            );
        } else {
            $subject = sprintf(
                "[%s] %s (Command Line: %s)",
                trim(`hostname`),
                $className,
                $GLOBALS['argv'][0] ?? '(unknown)',
            );
        }

        $email = new Email($this->getConfig('profile'));
        $email->setSubject($subject);
        $email->viewBuilder()->setTemplate($this->getConfig('template'));
        $email->viewBuilder()->disableAutoLayout();
        $email->viewBuilder()->setHelpers([
            'ErrorReporter.Error' => $this->getConfig(),
        ]);
        $email->viewBuilder()->setVars([
            'exception' => $exception,
            'request' => $request,
        ]);
        $email->send();
    }

    /**
     * Handle nested exception.
     *
     * @param \Throwable $exception A nested exception.
     * @return void
     */
    protected function _nestedException(Throwable $exception): void
    {
        $message = sprintf(
            '[%s] %s in %s on line %s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        $message .= "\nStack Trace:\n" . $exception->getTraceAsString() . "\n\n";

        Log::error($message);
    }
}
