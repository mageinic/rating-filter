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

namespace MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter\BucketBuilder;

use MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter\BucketBuilderInterface as BucketBuilderInterface;
use Magento\Framework\Search\Request\BucketInterface as RequestBucketInterface;
use Magento\Framework\Search\Dynamic\DataProviderInterface;

class RatingFilter implements BucketBuilderInterface
{
    /**
     * Build
     *
     * @param RequestBucketInterface $bucket
     * @param array $dimensions
     * @param array $queryResult
     * @param DataProviderInterface $dataProvider
     * @return array
     */
    public function build(
        RequestBucketInterface $bucket,
        array                  $dimensions,
        array                  $queryResult,
        DataProviderInterface  $dataProvider
    ) {
        $qvalues = [];
        foreach ($queryResult['aggregations'][$bucket->getName()]['buckets'] as $qresultBucket) {
            $qkey = (int)floor($qresultBucket['key'] / 20);
            $previousCount = isset($qvalues[$qkey]['count']) ? $qvalues[$qkey]['count'] : 0;
            $qvalues[$qkey] = [
                'value' => $qkey,
                'count' => $qresultBucket['doc_count'] + $previousCount,
            ];
        }
        return $qvalues;
    }
}
