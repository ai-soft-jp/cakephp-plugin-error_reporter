<?php
declare(strict_types=1);

namespace ErrorReporter;

use Cake\Core\BasePlugin;

/**
 * Plugin class for ErrorReporter
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name.
     *
     * @var string
     */
    protected $name = 'ErrorReporter';

    /**
     * Don't try to load routes.
     *
     * @var bool
     */
    protected $routesEnabled = false;
}
