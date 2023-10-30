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

namespace MageINIC\RatingFilter\Plugin\CatalogSearch\Model\Adapter\Mysql\Aggregation;

use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider as CoreDataProvider;

class DataProvider
{
    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $dresource;

    /**
     * @var ScopeResolverInterface
     */
    protected ScopeResolverInterface $scopeResolver;

    /**
     * @param ResourceConnection $dresource
     * @param ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        ResourceConnection $dresource,
        ScopeResolverInterface $scopeResolver
    ) {
        $this->dresource = $dresource;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Around get data set.
     *
     * @param CoreDataProvider $subject
     * @param \Closure $proceed
     * @param BucketInterface $bucket
     * @param array $dimensions
     * @param Table $entityIdsTable
     * @return Select|mixed
     */
    public function aroundGetDataSet(
        CoreDataProvider $subject,
        \Closure $proceed,
        BucketInterface $bucket,
        array $dimensions,
        Table $entityIdsTable
    ) {
        if ($bucket->getField() == 'rating_summary') {
                return $this->addRatingFilterAggregation($entityIdsTable, $dimensions);
        }
        return $proceed($bucket, $dimensions, $entityIdsTable);
    }

    /**
     * Add rating aggregation.
     *
     * @param Table $entityIdsTable
     * @param array $dimensions
     * @return Select
     */
    protected function addRatingFilterAggregation(
        Table $entityIdsTable,
        $dimensions
    ) {
        $currentScope = $dimensions['scope']->getValue();
        $currentScopeId = $this->scopeResolver->getScope($currentScope)->getId();
        $derivedTableName = $this->dresource->getConnection()->select();
        $derivedTableName->from(
            ['entities' => $entityIdsTable->getName()],
            []
        );

        $columnRating = new \Zend_Db_Expr("
                IF(main_table.rating_summary >=100,
                    5,
                    IF(
                        main_table.rating_summary >=80,
                        4,
                        IF(main_table.rating_summary >=60,
                            3,
                            IF(main_table.rating_summary >=40,
                                2,
                                IF(main_table.rating_summary >=20,
                                    1,
                                    0
                                )
                            )
                        )
                    )
                )
            ");

        $derivedTableName->joinLeft(
            ['main_table' => $this->dresource->getTableName('review_entity_summary')],
            sprintf(
                '`main_table`.`entity_pk_value`=`entities`.entity_id
                AND `main_table`.entity_type = 1
                AND `main_table`.store_id  =  %d',
                $currentScopeId
            ),
            [
                //'entity_id' => 'entity_pk_value',
                'value' => $columnRating,
            ]
        );
        $select = $this->dresource->getConnection()->select();
        $select->from(['main_table' => $derivedTableName]);
        return $select;
    }
}
