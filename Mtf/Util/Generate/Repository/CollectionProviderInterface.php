<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Util\Generate\Repository;

/**
 * Class CollectionProviderInterface
 *
 * @api
 */
interface CollectionProviderInterface
{
    /**
     * Get fixture items collection from a source storage
     *
     * @param array $fixture
     * @return array
     */
    public function getCollection(array $fixture);
}
