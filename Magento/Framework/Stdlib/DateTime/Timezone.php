<?php
/**
 * {license_notice}
 *
 * @copyright  {copyright}
 * @license    {license_link}
 */
namespace Magento\Framework\Stdlib\DateTime;

class Timezone implements \Magento\Framework\Stdlib\DateTime\TimezoneInterface
{
    /**
     * @var array
     */
    protected $_allowedFormats = array(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_FULL,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_LONG,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_MEDIUM,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_SHORT
    );

    /**
     * @var \Magento\BaseScopeResolverInterface
     */
    protected $_scopeResolver;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateFactory
     */
    protected $_dateFactory;

    /**
     * @var string
     */
    protected $_defaultTimezonePath;

    /**
     * @param \Magento\BaseScopeResolverInterface $scopeResolver
     * @param \Magento\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param DateFactory $dateFactory
     * @param string $defaultTimezonePath
     */
    public function __construct(
        \Magento\BaseScopeResolverInterface $scopeResolver,
        \Magento\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\DateFactory $dateFactory,
        $defaultTimezonePath
    ) {
        $this->_scopeResolver = $scopeResolver;
        $this->_localeResolver = $localeResolver;
        $this->_dateTime = $dateTime;
        $this->_dateFactory = $dateFactory;
        $this->_defaultTimezonePath = $defaultTimezonePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTimezonePath()
    {
        return $this->_defaultTimezonePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTimezone()
    {
        return \Magento\Framework\Stdlib\DateTime\TimezoneInterface::DEFAULT_TIMEZONE;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTimezone()
    {
        return $this->_scopeResolver->getScope()->getConfig('general/locale/timezone');
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormat($type = null)
    {
        return $this->_getTranslation($type, 'date');
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatWithLongYear()
    {
        return preg_replace(
            '/(?<!y)yy(?!y)/',
            'yyyy',
            $this->_getTranslation(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_SHORT, 'date')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormat($type = null)
    {
        return $this->_getTranslation($type, 'time');
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTimeFormat($type)
    {
        return $this->getDateFormat($type) . ' ' . $this->getTimeFormat($type);
    }

    /**
     * {@inheritdoc}
     */
    public function date($date = null, $part = null, $locale = null, $useTimezone = true)
    {
        if (is_null($locale)) {
            $locale = $this->_localeResolver->getLocale();
        }

        if (empty($date)) {
            // $date may be false, but \Magento\Framework\Stdlib\DateTime\DateInterface uses strict compare
            $date = null;
        }
        $date = $this->_dateFactory->create(array('date' => $date, 'part' => $part, 'locale' => $locale));
        if ($useTimezone) {
            $timezone = $this->_scopeResolver->getScope()->getConfig($this->getDefaultTimezonePath());
            if ($timezone) {
                $date->setTimezone($timezone);
            }
        }

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function scopeDate($scope = null, $date = null, $includeTime = false)
    {
        $timezone = $this->_scopeResolver->getScope($scope)->getConfig($this->getDefaultTimezonePath());
        $date = $this->_dateFactory->create(
            array('date' => $date, 'part' => null, 'locale' => $this->_localeResolver->getLocale())
        );
        $date->setTimezone($timezone);
        if (!$includeTime) {
            $date->setHour(0)->setMinute(0)->setSecond(0);
        }
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function formatDate(
        $date = null,
        $format = \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_SHORT,
        $showTime = false
    ) {
        if (!in_array($format, $this->_allowedFormats, true)) {
            return $date;
        }
        if (!$date instanceof \Magento\Framework\Stdlib\DateTime\DateInterface && $date && !strtotime($date)) {
            return '';
        }
        if (is_null($date)) {
            $date = $this->date(gmdate('U'), null, null);
        } elseif (!$date instanceof \Magento\Framework\Stdlib\DateTime\DateInterface) {
            $date = $this->date(strtotime($date), null, null);
        }

        if ($showTime) {
            $format = $this->getDateTimeFormat($format);
        } else {
            $format = $this->getDateFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * {@inheritdoc}
     */
    public function formatTime(
        $time = null,
        $format = \Magento\Framework\Stdlib\DateTime\TimezoneInterface::FORMAT_TYPE_SHORT,
        $showDate = false
    ) {
        if (!in_array($format, $this->_allowedFormats, true)) {
            return $time;
        }

        if (is_null($time)) {
            $date = $this->date(time());
        } elseif ($time instanceof \Magento\Framework\Stdlib\DateTime\DateInterface) {
            $date = $time;
        } else {
            $date = $this->date(strtotime($time));
        }

        if ($showDate) {
            $format = $this->getDateTimeFormat($format);
        } else {
            $format = $this->getTimeFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * {@inheritdoc}
     */
    public function utcDate($scope, $date, $includeTime = false, $format = null)
    {
        $dateObj = $this->scopeDate($scope, $date, $includeTime);
        $dateObj->set($date, $format);
        $dateObj->setTimezone(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::DEFAULT_TIMEZONE);
        return $dateObj;
    }

    /**
     * {@inheritdoc}
     */
    public function scopeTimeStamp($scope = null)
    {
        $timezone = $this->_scopeResolver->getScope($scope)->getConfig($this->getDefaultTimezonePath());
        $currentTimezone = @date_default_timezone_get();
        @date_default_timezone_set($timezone);
        $date = date('Y-m-d H:i:s');
        @date_default_timezone_set($currentTimezone);
        return strtotime($date);
    }

    /**
     * {@inheritdoc}
     */
    public function isScopeDateInInterval($scope, $dateFrom = null, $dateTo = null)
    {
        if (!$scope instanceof \Magento\BaseScopeInterface) {
            $scope = $this->_scopeResolver->getScope($scope);
        }

        $scopeTimeStamp = $this->scopeTimeStamp($scope);
        $fromTimeStamp = strtotime($dateFrom);
        $toTimeStamp = strtotime($dateTo);
        if ($dateTo) {
            // fix date YYYY-MM-DD 00:00:00 to YYYY-MM-DD 23:59:59
            $toTimeStamp += 86400;
        }

        $result = false;
        if (!$this->_dateTime->isEmptyDate($dateFrom) && $scopeTimeStamp < $fromTimeStamp) {
        } elseif (!$this->_dateTime->isEmptyDate($dateTo) && $scopeTimeStamp > $toTimeStamp) {
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Returns a localized information string, supported are several types of informations.
     * For detailed information about the types look into the documentation
     *
     * @param  string $value Name to get detailed information about
     * @param  string $path (Optional) Type of information to return
     * @return string|false The wished information in the given language
     */
    protected function _getTranslation($value = null, $path = null)
    {
        return $this->_localeResolver->getLocale()->getTranslation($value, $path, $this->_localeResolver->getLocale());
    }
}
