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

namespace MageINIC\RatingFilter\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class Rating extends AbstractFilter
{
    public const RATING_COLLECTION_FLAG = 'rating_filter_applied1';

    /**
     * @var BlockFactory
     */
    protected BlockFactory $blockFactory;

    /**
     * @var Status
     */
    protected Status $_stockResource;

    /**
     * @var array|int[]
     */
    protected array $stars = [
        1 => 20,
        2 => 40,
        3 => 60,
        4 => 80,
        5 => 100,
        6 => -1
    ];

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param ObjectManagerInterface $objectManager
     * @param ResourceConnection $resourceConnection
     * @param Http $httpRequest
     * @param BlockFactory $blockFactory
     * @param Status $stockResource
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ItemFactory              $filterItemFactory,
        StoreManagerInterface    $storeManager,
        Layer                    $layer,
        DataBuilder              $itemDataBuilder,
        ObjectManagerInterface   $objectManager,
        ResourceConnection       $resourceConnection,
        Http                     $httpRequest,
        BlockFactory             $blockFactory,
        Status                   $stockResource,
        array                    $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $data);
        $this->productMetadata = $productMetadata;
        $this->objectManager = $objectManager;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->_requestVar = 'rat';
        $this->httpRequest = $httpRequest;
        $this->blockFactory = $blockFactory;
        $this->_stockResource = $stockResource;
    }

    /**
     * Apply filter to collection
     *
     * @param RequestInterface $request
     * @return $this|Rating
     * @throws NoSuchEntityException
     */
    public function apply(RequestInterface $request)
    {
        $filter =$request->getParam($this->getRequestVar(), null);
        if (is_null($filter)) {
            return $this;
        }
        $condition = $this->stars[$filter];

        if ($filter == 6) {
            $condition = new \Zend_Db_Expr("IS NULL");

        }

        $collection = $this->getLayer()->getProductCollection();
        $collection->setFlag(self::RATING_COLLECTION_FLAG, $filter);
        $this->getLayer()->getProductCollection()->addFieldToFilter('rating_summary', $condition);
        $select = $collection->getSelect();
        $minRating = (array_key_exists($filter, $this->stars))
            ? $this->stars[$filter]
            : 0;
        $rat_table=$this->resourceConnection->getTableName('rating_option_vote_aggregated');
        $select->joinLeft(
            ['rating' =>$rat_table],
            sprintf(
                '`rating`.`entity_pk_value`=`e`.entity_id
                    AND `rating`.`store_id`  =  %d',
                $this->storeManager->getStore()->getId()
            ),
            ''
        );
        if ($minRating == "-1") {
            $select->where('`rating`.`percent` IS NULL');
        } else {
            $select->where(
                '`rating`.`percent` >= ?',
                $minRating
            );
        }

        $state = $this->_createItem($this->getLabelHtml($filter), $filter);
        $this->getLayer()->getState()->addFilter($state);
        return $this;
    }

    /**
     * Get filter name
     *
     * @return Phrase
     */
    public function getName()
    {
        return __('Rating Filter');
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData(): array
    {
        $data = [];
        $count = $this->_getCount();
        $currentValue = $this->httpRequest->getQuery($this->_requestVar);

        for ($i = 5; $i >= 1; $i--) {
            $data[] = [
                'label' => $this->getLabelHtml($i),
                'value' => ($currentValue == $i) ? null : $i,
                'count' => $count[($i - 1)],
                'real_count' => ((isset($count[$i]) && $i != 5 ? $count[$i] : 0) - $count[($i - 1)]),
                'option_id' => $i,
            ];
        }
        return $data;
    }

    /**
     * Get rating count.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Select_Exception
     */
    public function _getCount(): array
    {

        $collection = $this->getLayer()->getProductCollection();
        $connection = $collection->getConnection();
        $connection
            ->query('SET @ONE :=0, @TWO := 0, @THREE := 0, @FOUR := 0, @FIVE := 0, @NOT_RATED := 0');
        $select = clone $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);
        $where = $select->getPart(Select::WHERE);
        $from = $select->getPart(Select::FROM);
        if (!isset($from['stock_status_index'])) {

            $select->join(
                [
                    'stock_status_index' => $this->_stockResource->getMainTable()
                ],
                'e.entity_id = stock_status_index.product_id',
                []
            );
        }
        foreach ($where as $key => $part) {
            if (strpos($part, "percent") !== false) {
                if ($key == 0) {
                    $where[$key] = "1";
                } else {
                    unset($where[$key]);
                }
            }
        }
        $rat_table=$this->resourceConnection->getTableName('rating_option_vote_aggregated');
        $select->setPart(\Zend_Db_Select::WHERE, $where);
        $select->joinLeft(
            ['rsc' => $rat_table],
            sprintf(
                '`rsc`.`entity_pk_value`=`e`.entity_id

                AND `rsc`.store_id  =  %d',
                $this->storeManager->getStore()->getId()
            ),
            ['e.entity_id','rsc.percent']
        );
        if (version_compare($this->productMetadata->getVersion(), '2.1.0', '<')) {
            $select2 = new \Zend_Db_Select($connection);
        } else { // for version 2.1.0 & up
            $select2 = new \Zend_Db_Select($connection, $this->objectManager->get(\Magento\Framework\DB\Select\SelectRenderer::class));
        }

        $select2->from($select);
        $select = $select2;

        $columns = new \Zend_Db_Expr("
            IF(`t`.`percent` >= 20, @ONE := @ONE + 1, 0),
            IF(`t`.`percent` >= 40, @TWO := @TWO + 1, 0),
            IF(`t`.`percent` >= 60, @THREE := @THREE + 1, 0),
            IF(`t`.`percent` >= 80, @FOUR := @FOUR + 1, 0),
            IF(`t`.`percent` >= 100, @FIVE := @FIVE + 1, 0),
            IF(`t`.`percent` IS NULL, @NOT_RATED := @NOT_RATED + 1, 0)
        ");
        $select->columns($columns);
        $connection->query($select);
        $result = $connection->fetchRow('SELECT @ONE, @TWO, @THREE, @FOUR, @FIVE, @NOT_RATED;');
        return array_values($result);
    }

    /**
     * Init items.
     *
     * @return $this|Rating
     */
    protected function _initItems()
    {
        $data  = $this->_getItemsData();
        $items = [];

        foreach ($data as $itemData) {
            $item = $this->_createItem(
                $itemData['label'],
                $itemData['value'],
                $itemData['count']
            );
            $item->setOptionId($itemData['option_id']);
            $item->setRealCount($itemData['real_count']);
            if ($itemData['count']) {
                $items[] = $item;
            }
        }
        $this->_items = $items;
        return $this;
    }

    /**
     * Get label html.
     *
     * @param string $countStars
     * @return Phrase|string
     */
    protected function getLabelHtml($countStars)
    {
        $block = $this->blockFactory->createBlock(Template::class);
        $block->setTemplate('MageINIC_RatingFilter::layer/rating.phtml');
        if ($this->getLayer()->getProductCollection()->getFlag(self::RATING_COLLECTION_FLAG)) {
            $block->setData('filterval', $this->getLayer()->getProductCollection()->getFlag(
                self::RATING_COLLECTION_FLAG
            ));
        }
        $block->setData('star', $countStars);
        $html = $block->toHtml();
        return $html;
    }
}
