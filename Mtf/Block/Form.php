<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Block;

use Mtf\Client\Element;
use Mtf\Fixture\FixtureInterface;
use Mtf\Fixture\InjectableFixture;
use Mtf\Client\Driver\Selenium\Browser;

/**
 * Class Form
 * Is used to represent any form on the page
 *
 * @api
 */
class Form extends Block
{
    /**
     * Mapping for field locator
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Array of placeholders applied on selector
     *
     * @var array
     */
    protected $placeholders = [];

    /**
     * Determine whether to use only mapped fields
     *
     * @var bool
     */
    protected $mappingMode = false;

    /**
     * Wrap element to pass into form
     *
     * @var string
     */
    protected $wrapper = '';

    /**
     * Mapper instance
     *
     * @var Mapper
     */
    protected $mapper;

    /**
     * Array with filled fields
     *
     * @var array
     */
    public $setFields = [];

    /**
     * @constructor
     * @param Element $element
     * @param BlockFactory $blockFactory
     * @param Mapper $mapper
     * @param Browser $browser
     * @param array $config [optional]
     */
    public function __construct(
        Element $element,
        BlockFactory $blockFactory,
        Mapper $mapper,
        Browser $browser,
        array $config = []
    ) {
        $this->mapper = $mapper;
        parent::__construct($element, $blockFactory, $browser, $config);
    }

    /**
     * Initialize block
     *
     * @return void
     */
    protected function _init()
    {
        $xmlFilePath = $this->getXmlFilePath();
        if (file_exists($xmlFilePath)) {
            $mapping = $this->mapper->read($xmlFilePath);
            $this->wrapper = isset($mapping['wrapper']) ? $mapping['wrapper'] : '';
            $this->mapping = isset($mapping['fields']) ? $mapping['fields'] : [];
            $this->mappingMode = isset($mapping['strict']) ? (bool)$mapping['strict'] : false;
            $this->applyPlaceholders();
        }
    }

    /**
     * Get path for form *.xml file with mapping
     *
     * @return string
     */
    protected function getXmlFilePath()
    {
        return MTF_TESTS_PATH . str_replace('\\', '/', get_class($this)) . '.xml';
    }

    /**
     * Set wrapper value to the root form
     *
     * @param string $wrapper
     * @return void
     */
    public function setWrapper($wrapper)
    {
        $this->wrapper = $wrapper;
    }

    /**
     * Set mapping to the root form
     *
     * @param array $mapping
     * @return void
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = array_replace($this->mapping, $mapping);
    }

    /**
     * Apply placeholders to selectors.
     * Placeholder in .xml is specified via '%' sign from both side.
     *
     * @return void
     */
    protected function applyPlaceholders()
    {
        foreach ($this->placeholders as $placeholder => $replacement) {
            $pattern = '%' . $placeholder . '%';
            foreach ($this->mapping as $key => $locator) {
                if (strpos($locator['selector'], $pattern) !== false) {
                    $this->mapping[$key]['selector'] = str_replace($pattern, $replacement, $locator['selector']);
                }
            }
        }
    }

    /**
     * Fixture mapping
     *
     * @param array|null $fields
     * @param string|null $parent
     * @return array
     */
    protected function dataMapping(array $fields = null, $parent = null)
    {
        $mapping = [];
        $mappingFields = ($parent !== null) ? $parent : $this->mapping;
        $data = ($this->mappingMode || null === $fields) ? $mappingFields : $fields;
        foreach ($data as $key => $value) {
            if (isset($value['value'])) {
                $value = $value['value'];
            }
            if (!$this->mappingMode && is_array($value) && null !== $fields
                && isset($mappingFields[$key]['composite'])
            ) {
                $mapping[$key] = $this->dataMapping($value, $mappingFields[$key]);
            } else {
                $mapping[$key]['selector'] = isset($mappingFields[$key]['selector'])
                    ? $mappingFields[$key]['selector']
                    : (($this->wrapper != '') ? "[name='{$this->wrapper}" . "[{$key}]']" : "[name={$key}]");
                $mapping[$key]['strategy'] = isset($mappingFields[$key]['strategy'])
                    ? $mappingFields[$key]['strategy']
                    : Element\Locator::SELECTOR_CSS;
                $mapping[$key]['input'] = isset($mappingFields[$key]['input'])
                    ? $mappingFields[$key]['input']
                    : null;
                $mapping[$key]['class'] = isset($mappingFields[$key]['class'])
                    ? $mappingFields[$key]['class']
                    : null;
                $mapping[$key]['value'] = $this->mappingMode
                    ? (isset($fields[$key]['value']) ? $fields[$key]['value'] : $fields[$key])
                    : $value;
            }
        }

        return $mapping;
    }

    /**
     * Get element of particular class if defined in xml configuration or of one of framework classes otherwise
     *
     * @param Element $context
     * @param array $field
     * @return Element
     * @throws \Exception
     */
    protected function getElement(Element $context, array $field)
    {
        if (isset($field['class'])) {
            $element = $context->find($field['selector'], $field['strategy'], $field['class']);
            if (!$element instanceof Element) {
                throw new \Exception('Wrong Element Class.');
            }
        } else {
            $element = $context->find($field['selector'], $field['strategy'], $field['input']);
        }

        return $element;
    }

    /**
     * Fill specified form data
     *
     * @param array $fields
     * @param Element $element
     */
    protected function _fill(array $fields, Element $element = null)
    {
        $context = ($element === null) ? $this->_rootElement : $element;
        foreach ($fields as $name => $field) {
            if (!isset($field['value'])) {
                $this->_fill($field, $context);
            } else {
                $element = $this->getElement($context, $field);
                if ($this->mappingMode || ($element->isVisible() && !$element->isDisabled())) {
                    $element->setValue($field['value']);
                    $this->setFields[$name] = $field['value'];
                }
            }
        }
    }

    /**
     * Fill the root form
     *
     * @param FixtureInterface $fixture
     * @param Element|null $element
     * @return $this
     */
    public function fill(FixtureInterface $fixture, Element $element = null)
    {
        $data = $fixture->getData();
        $fields = isset($data['fields']) ? $data['fields'] : $data;
        $mapping = $this->dataMapping($fields);
        $this->_fill($mapping, $element);

        return $this;
    }

    /**
     * Get data of specified form data
     *
     * @param array $fields
     * @param Element|null $element
     * @return array
     */
    protected function _getData(array $fields, Element $element = null)
    {
        $data = [];
        $context = ($element === null) ? $this->_rootElement : $element;
        foreach ($fields as $key => $field) {
            if (!isset($field['value'])) {
                $data[$key] = $this->_getData($field);
            } else {
                $element = $this->getElement($context, $field);
                if ($this->mappingMode || $element->isVisible()) {
                    $data[$key] = $element->getValue();
                }
            }
        }

        return $data;
    }

    /**
     * Get data of the root form
     *
     * @param FixtureInterface|null $fixture
     * @param Element|null $element
     * @return array
     */
    public function getData(FixtureInterface $fixture = null, Element $element = null)
    {
        if (null === $fixture) {
            $fields = null;
        } else {
            $isHasData = ($fixture instanceof InjectableFixture) ? $fixture->hasData() : true;
            $data = $isHasData ? $fixture->getData() : [];
            $fields = isset($data['fields']) ? $data['fields'] : $data;
        }
        $mapping = $this->dataMapping($fields);

        return $this->_getData($mapping, $element);
    }
}
