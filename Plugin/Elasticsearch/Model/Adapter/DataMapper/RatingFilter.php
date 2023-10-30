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

namespace MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter\DataMapper;

use MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter\DataMapperInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Review\Model\ReviewFactory;

class RatingFilter implements DataMapperInterface
{
    public const FIELD_NAME = 'rating_summary';

    /**
     * @var ReviewFactory
     */
    private ReviewFactory $reviewFactory;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * @param ReviewFactory $reviewFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        ProductFactory $productFactory
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * Prepare index data for using in search engine metadata
     *
     * @param int $entityId
     * @param array $entityIndexData
     * @param int $storeId
     * @param array $context
     * @return array
     */
    public function map(int $entityId, array $entityIndexData, int $storeId, array $context = [])
    {
        $lproduct = $this->productFactory->create(['data' => ['entity_id' => $entityId]]);
        $this->reviewFactory->create()->getEntitySummary($lproduct, $storeId);
        return [self::FIELD_NAME => $lproduct->getRatingSummary()->getRatingSummary()];
    }

    /**
     * Is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }
}
