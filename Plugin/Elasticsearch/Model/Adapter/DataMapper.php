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

namespace MageINIC\RatingFilter\Plugin\Elasticsearch\Model\Adapter;

class DataMapper
{
    /**
     * @var array
     */
    private array $dataMappers = [];

    /**
     * @param array $dataMappers
     */
    public function __construct(
        array $dataMappers = []
    ) {
        $this->dataMappers = $dataMappers;
    }

    /**
     * Around map
     *
     * @param $subject
     * @param callable $proceed
     * @param int $productId
     * @param array $indexData
     * @param int $storeId
     * @param array $context
     * @return array
     */
    public function aroundMap(
        $subject,
        callable $proceed,
        int $productId,
        array $indexData,
        int $storeId,
        array $context = []
    ) {
        $dataDocument = $proceed($productId, $indexData, $storeId, $context);
        $context['document'] = $dataDocument;
        foreach ($this->dataMappers as $dmapper) {
            if ($dmapper instanceof DataMapperInterface && $dmapper->isAllowed()) {
                $dataDocument = array_merge($dataDocument, $dmapper->map($productId, $indexData, $storeId, $context));
            }
        }
        return $dataDocument;
    }
}
