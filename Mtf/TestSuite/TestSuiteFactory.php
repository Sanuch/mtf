<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\TestSuite;

use Mtf\ObjectManager;

/**
 * Class TestSuiteFactory
 *
 * @api
 */
class TestSuiteFactory
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @constructor
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create test suite
     *
     * @param string $class
     * @param array $arguments
     * @return InjectableTestCase|RegularTestCase
     */
    public function create($class, array $arguments)
    {
        $arguments['class'] = $class;

        $refClass = new \ReflectionClass($class);
        if ($refClass->isSubclassOf('Mtf\\TestCase\\Injectable')) {
            $object = $this->objectManager->create('Mtf\\TestSuite\\InjectableTestCase', $arguments);
        } else {
            $object = $this->objectManager->create('Mtf\\TestSuite\\RegularTestCase', $arguments);
        }

        return $object;
    }

    /**
     * Get test suite
     *
     * @param string $class
     * @return InjectableTestCase|RegularTestCase
     */
    public function get($class)
    {
        $refClass = new \ReflectionClass($class);
        if ($refClass->isSubclassOf('Mtf\\TestCase\\Injectable')) {
            $object = $this->objectManager->get('Mtf\\TestSuite\\InjectableTestCase');
        } else {
            $object = $this->objectManager->create('Mtf\\TestSuite\\RegularTestCase');
        }

        return $object;
    }
}
