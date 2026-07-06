<?php
declare(strict_types=1);

namespace ErrorReporter\View\Helper;

use Cake\Core\Plugin;
use Cake\Error\Debugger;
use Cake\View\Helper;

/**
 * ErrorReporter Helper
 */
class ErrorHelper extends Helper
{
    /**
     * Path prefixes
     */
    protected array $pathPrefixes;

    /**
     * Path regexs
     */
    protected string $pathRegex;

    /**
     * Initialize helper.
     *
     * @param array $config Configuration
     * @return void
     */
    public function initialize(array $config): void
    {
        $paths = [];
        foreach ($this->getConfig('pathMap') as $key => $path) {
            if (is_numeric($key)) {
                $key = $path;
                $path = defined($key) ? constant($key) : null;
            }
            if ($path) {
                $this->pathPrefixes[$path] = $key . DS;
                $paths[] = $path;
            }
        }
        foreach (Plugin::loaded() as $plugin) {
            $path = Plugin::classPath($plugin);
            $this->pathPrefixes[$path] = '(Plugin) ' . $plugin . DS;
            $paths[] = $path;
        }
        usort($paths, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $paths = array_map(function ($path) {
            return preg_quote($path, '#');
        }, $paths);
        $this->pathRegex = '#^(' . implode('|', $paths) . ')#';
    }

    /**
     * Format an iterable.
     *
     * @param iterable $ary Iterable.
     * @param string|null $prefix Key prefix.
     * @return string Formatted string.
     */
    public function formatArray(iterable $ary, ?string $prefix = null): string
    {
        $result = '';
        $filter = $this->getConfig('filter');
        foreach ($ary as $key => $value) {
            if (!empty($prefix)) {
                $key = "{$prefix}[{$key}]";
            }
            if (is_string($key) && preg_match($filter, $key)) {
                $value = '[FILTERED]';
            } elseif ($value === null) {
                $value = '(null)';
            } elseif ($value === []) {
                $value = '(empty array)';
            } elseif (is_iterable($value)) {
                $result .= $this->formatArray($value, $key);
                continue;
            } elseif (is_object($value)) {
                if (is_callable([$value, '__toString'])) {
                    $value = $this->formatStr((string)$value);
                } else {
                    $value = Debugger::exportVar($value);
                }
                $value = preg_replace('/\r?\n+/', "\n", $value);
            } elseif (is_resource($value)) {
                $value = '(resource) ' . get_resource_type($value);
            } else {
                $value = $this->formatStr((string)$value);
            }
            if (mb_strlen($value) > 250) {
                $value = mb_substr($value, 0, 250) . '...';
            }
            $result .= "{$key}={$value}\n";
        }
        return $result;
    }

    /**
     * Format backtrace
     *
     * @param array $backtrace Backtrace.
     * @return string Formatted string.
     */
    public function formatBacktrace(array $backtrace): string
    {
        $result = '';
        foreach ($backtrace as $i => $bt) {
            $row = "#{$i} ";
            if (isset($bt['file'])) {
                $row .= $this->filterPath($bt['file']);
            } else {
                $row .= '[internal function]';
            }
            if (isset($bt['line'])) {
                $row .= "({$bt['line']})";
            }
            $row .= ': ';
            if (isset($bt['class'])) {
                $row .= $bt['class'];
            }
            if (isset($bt['type'])) {
                $row .= $bt['type'];
            }
            if (isset($bt['function'])) {
                $row .= $bt['function'];
                $arg = '';
                if (isset($bt['args'])) {
                    if (is_string($bt['args'])) {
                        $arg = $this->filterPath($bt['args']);
                    } elseif (in_array($bt['function'], ['include', 'include_once', 'require', 'require_once'])) {
                        $arg = $this->filterPath($bt['args'][0]);
                    }
                }
                $row .= '(' . $arg . ')';
            }
            $result .= $row . "\n";
        }
        return $result;
    }

    /**
     * Filter path.
     *
     * @param string $path Path.
     * @return string Filtered path.
     */
    public function filterPath(string $path): string
    {
        if (preg_match($this->pathRegex, $path, $match)) {
            $prefix = $this->pathPrefixes[$match[1]];
            return $prefix . substr($path, strlen($match[1]));
        }
        return $path;
    }

    /**
     * Format a string.
     *
     * @param mixed $str String to be formatted.
     * @param int $length Length to truncate.
     * @return string Formatted string.
     */
    public function formatStr(string $str, int $length = 120): string
    {
        $ellipses = null;
        if (strlen($str) > $length) {
            $str = substr($str, 0, $length);
            $ellipses = '...';
        }
        if (preg_match('/[\x00-\x1F\x7F]|^\s|\s$/sD', $str)) {
            $quoted = preg_replace_callback('/[\x00-\x1F\x7F"]/', function ($match) {
                if ($match[0] === '"') {
                    return '\\"';
                } else {
                    return sprintf('\\x%02X', ord($match[0]));
                }
            }, $str);
            $str = '"' . $quoted . '"';
        }
        return $str . $ellipses;
    }
}
