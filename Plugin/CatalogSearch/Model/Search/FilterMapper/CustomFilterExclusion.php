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

namespace MageINIC\RatingFilter\Plugin\CatalogSearch\Model\Search\FilterMapper;

use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomFilterExclusion
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var string[]
     */
    private array $validFields = ['rating_summary'];

    /**
     * @var string
     */
    private string $productIdLink;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->productIdLink = $productMetadata->getEdition() == 'Enterprise' ? 'row_id' : 'entity_id';
    }

    /**
     * Apply filter
     *
     * @param FilterInterface $filterObj
     * @param Select $select
     * @return bool
     * @throws NoSuchEntityException
     */
    public function apply(
        FilterInterface $filterObj,
        Select $select
    ) {
        if (!in_array($filterObj->getField(), $this->validFields, true)) {
            return false;
        }
        switch ($filterObj->getField()) {
            case 'rating_summary':
                $isApplied = $this->applyRatingFilterLayer($filterObj, $select);
                break;
            default:
                $isApplied = false;
        }

        return $isApplied;
    }

    /**
     * Apply rating filter layer
     *
     * @param FilterInterface $filterObj
     * @param Select $select
     * @return bool
     * @throws NoSuchEntityException
     */
    private function applyRatingFilterLayer(
        FilterInterface $filterObj,
        Select $select
    ) {
        $alias = $filterObj->getField() . RequestGenerator::FILTER_SUFFIX;
        $storeId=$this->storeManager->getStore()->getId();
        $select->joinLeft(
            [$alias => $this->resourceConnection->getTableName('review_entity_summary')],
            sprintf(
                '`rating_summary_filter`.`entity_pk_value`=`search_index`.entity_id
                AND `rating_summary_filter`.entity_type = 1
                AND `rating_summary_filter`.store_id  =  %d',
                $storeId
            ),
            []
        );

        return true;
    }
}
