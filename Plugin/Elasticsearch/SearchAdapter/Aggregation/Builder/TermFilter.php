<?php
/**
 * MageINIC
 * Copyright (C) 2023 MageINIC <support@mageinic.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://opensource.org/licenses/gpl-3.0.html.
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category MageINIC
 * @package MageINIC_RatingFilter
 * @copyright Copyright (c) 2023 MageINIC (https://www.mageinic.com/)
 * @license https://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MageINIC <support@mageinic.com>
 */

namespace MageINIC\RatingFilter\Plugin\Elasticsearch\SearchAdapter\Aggregation\Builder;

use Magento\Framework\Search\Request\BucketInterface as RequestBucketInterface;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter\BucketBuilderInterface;

class TermFilter
{
    /**
     * @var array
     */
    private array $bucketBuilders = [];

    /**
     * @param array $bucketBuilders
     */
    public function __construct(
        array $bucketBuilders = []
    ) {
        $this->bucketBuilders = $bucketBuilders;
    }

    /**
     * Around build.
     *
     * @param $subject
     * @param callable $proceed
     * @param RequestBucketInterface $bucket
     * @param array $dimensions
     * @param array $queryResult
     * @param DataProviderInterface $dataProvider
     * @return array
     */
    public function aroundBuild(
        $subject,
        callable $proceed,
        RequestBucketInterface $bucket,
        array $dimensions,
        array $queryResult,
        DataProviderInterface $dataProvider
    ) {
        if (isset($this->bucketBuilders[$bucket->getField()])) {
            $termBuilder = $this->bucketBuilders[$bucket->getField()];
            if ($termBuilder instanceof BucketBuilderInterface) {
                return $termBuilder->build($bucket, $dimensions, $queryResult, $dataProvider);
            }
        }
        return $proceed($bucket, $dimensions, $queryResult, $dataProvider);
    }
}
