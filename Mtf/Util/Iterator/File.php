<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Util\Iterator;

/**
 * Class File
 *
 * @api
 */
class File extends AbstractIterator
{
    /**
     * Cached files content
     *
     * @var array
     */
    protected $cached = [];

    /**
     * @constructor
     * @param array $paths
     */
    public function __construct(array $paths)
    {
        $this->data = $paths;
        $this->initFirstElement();
    }

    /**
     * Get file content
     *
     * @return string
     */
    public function current()
    {
        if (!isset($this->cached[$this->current])) {
            $this->cached[$this->current] = file_get_contents($this->current);
        }
        return $this->cached[$this->current];

    }

    /**
     * Check if current element is valid
     *
     * @return boolean
     */
    protected function isValid()
    {
        return true;
    }
}
