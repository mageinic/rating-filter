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

class FieldMapper
{
    public const ELASTIC_ES_DATA_TYPE_STRING = 'string';
    public const ELASTIC_ES_DATA_TYPE_FLOAT = 'float';
    public const ELASTIC_ES_DATA_TYPE_INT = 'integer';
    public const ELASTIC_ES_DATA_TYPE_DATE = 'date';
    public const ELASTIC_ES_DATA_TYPE_ARRAY = 'array';
    /**
     * @var array
     */
    private $fields = [];

    /**
     * @param array $fields
     */
    public function __construct(
        array $fields = []
    ) {
        $this->fields = $fields;
    }

    /**
     * @inheritdoc
     */
    public function afterGetAllAttributesTypes($subject, array $result)
    {
        foreach ($this->fields as $qfieldName => $qfieldType) {
            if (is_object($qfieldType) && ($qfieldType instanceof AdditionalFieldMapperInterface)) {
                $attributeTypes = $qfieldType->getAdditionalAttributeTypes();
                $result = array_merge($result, $attributeTypes);
                continue;
            }
            if (empty($qfieldName)) {
                continue;
            }
            if ($this->isValidFieldType($qfieldType)) {
                $result[$qfieldName] = ['type' => $qfieldType];
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function aroundGetFieldName($subject, callable $proceed, $qattributeCode, $context = [])
    {
        if (isset($this->fields[$qattributeCode]) && is_object($this->fields[$qattributeCode])) {
            $qfiledMapper = $this->fields[$qattributeCode];
            if ($qfiledMapper instanceof AdditionalFieldMapperInterface) {
                return $qfiledMapper->getFiledName($context);
            }
        }
        return $proceed($qattributeCode, $context);
    }

    /**
     * Is valid field type
     *
     * @param $fieldType
     * @return false|mixed
     */
    private function isValidFieldType($fieldType)
    {
        switch ($fieldType) {
            case self::ELASTIC_ES_DATA_TYPE_STRING:
            case self::ELASTIC_ES_DATA_TYPE_DATE:
            case self::ELASTIC_ES_DATA_TYPE_INT:
            case self::ELASTIC_ES_DATA_TYPE_FLOAT:
                break;
            default:
                $fieldType = false;
                break;
        }
        return $fieldType;
    }
}
