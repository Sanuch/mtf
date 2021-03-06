<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */
namespace Magento\Framework\App\Filesystem\DirectoryList;

use Magento\Framework\App\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Class Configuration
 * @package Magento\Framework\App\Filesystem\DirectoryList
 */
class Configuration
{
    /**
     * Path to filesystem directory configuration
     *
     * @var string
     */
    const XML_FILESYSTEM_DIRECTORY_PATH = 'system/filesystem/directory';

    /**
     * Declaration wrapper configuration
     */
    const XML_FILESYSTEM_WRAPPER_PATH = 'system/filesystem/protocol';

    /**
     * Filesystem Directory configuration
     *
     * @var array
     */
    protected $directories;

    /**
     * Filesystem protocols configuration
     *
     * @var array
     */
    protected $protocols;

    /**
     * Store directory configuration
     *
     * @param \Magento\Framework\App\ConfigInterface $config
     */
    public function __construct(\Magento\Framework\App\ConfigInterface $config)
    {
        $this->directories = $config->getValue(self::XML_FILESYSTEM_DIRECTORY_PATH) ? : array();
        $this->protocols = $config->getValue(self::XML_FILESYSTEM_WRAPPER_PATH) ? : array();
    }

    /**
     * Add directories from configuration to Filesystem
     *
     * @param DirectoryList $directoryList
     * @return void
     */
    public function configure(DirectoryList $directoryList)
    {
        foreach ($this->directories as $code => $directoryConfiguration) {
            if (!$directoryList->isConfigured($code)) {
                $directoryList->addDirectory($code, $directoryConfiguration);
            }
        }

        foreach ($this->protocols as $code => $protocolConfiguration) {
            $directoryList->addProtocol($code, $protocolConfiguration);
        }
    }
}
