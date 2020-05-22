<?php
declare(strict_types=1);

namespace ErrorReporter\Controller\Component;

use Cake\Controller\Component;
use ErrorReporter\ErrorReporter;
use Throwable;

/**
 * ErrorReporter Component
 */
class ErrorReporterComponent extends Component
{
    /**
     * Deliver an error report.
     *
     * @param \Throwable $exception Exception object.
     * @return bool Whether report sent or not.
     */
    public function report(Throwable $exception): bool
    {
        return ErrorReporter::instance()->handleException($exception, $this->getController()->getRequest());
    }
}
