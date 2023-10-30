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

class FinderFilter implements DataMapperInterface
{
    public const FIELD_NAME = 'finder';
    public const DOCUMENT_FIELD_NAME = 'finder';
    public const INDEX_DOCUMENT = 'document';

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
        $filterVal = $context[self::INDEX_DOCUMENT][self::DOCUMENT_FIELD_NAME] ?? $entityId;
        return [self::FIELD_NAME => $filterVal];
    }

    /**
     * Is allowed
     *
     * @return bool
     */
    public function isAllowed()
    {
        return true;
    }
}
