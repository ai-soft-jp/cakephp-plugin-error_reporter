<?php
declare(strict_types=1);

namespace ErrorReporter;

use Cake\Core\BasePlugin;

/**
 * Plugin class for ErrorReporter
 */
class ErrorReporterPlugin extends BasePlugin
{
    /**
     * The name of this plugin
     */
    protected ?string $name = 'ErrorReporter';

    /**
     * Don't try to load routes
     */
    protected bool $routesEnabled = false;
}
