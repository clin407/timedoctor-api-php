<?php

namespace smartsites\yii2\utils;

use ReflectionClass;
use yii\db\ActiveRecord;
use function Functional\map;

/**
 * Mass editing of entities one-to-many or many-to-many relationship
 *
 * Terms used in this class' methods and documentation:
 * - Parent object - an object that links with multiple other object, the "one"
 * in "one-to-many" or the left "many" in "many-to-many".
 * - Child objects - the object that reprsent the records that are linked to the
 * parent object. The "many" in "one-to-many", or the right "many" in
 * "many-to-many".
 * - Old children - child records that are currently in the database.
 * - Incoming children - children created and updated using the incoming
 * attribute arrays (usually POST parameters in controller).
 *
 * <pre>
 * --------------
 * |            |
 * | Deleted    |    <-- Old children
 * | -----------+---
 * | | Updated  |  |
 * --------------  | <-- Incoming children
 *   |  Created    |
 *   ---------------
 * </pre>
 *
 * Sets of new and old records may intersect or not, or one can be a subset of
 * the other. XToManyRelationship descendants must transparently supports all
 * these cases.
 *
 * @see http://www.yiiframework.com/doc-2.0/guide-input-tabular-input.html#combining-update-create-and-delete-on-one-page
 */
abstract class XToManyRelationship
{

    /** @var ActiveRecord */
    protected $parent;

    /** @var string */
    protected $childrenModelClassName;

    /** @var string */
    protected $relationName;

    /** @var array */
    protected $incomingChildrenData;

    /**
     * @param ActiveRecord $parent Instance of the parent class whose children
     * we're editing.
     * @param string $childrenModelClassName Fully qualified class name of the
     * child model.
     * @param string $relationName Name of the relation in the parent model that
     * links it to the child models.
     * @param array $incomingData Data that came to the controller, usually
     * Yii::$app->request->post() has to be used here.
     */
    public function __construct(
        ActiveRecord $parent,
        $childrenModelClassName,
        $relationName,
        array $incomingData
    )
    {
        $this->parent = $parent;
        $this->childrenModelClassName = $childrenModelClassName;
        $this->relationName = $relationName;
        $this->incomingChildrenData = self::childModelIncomingData(
            $childrenModelClassName,
            $incomingData
        );
    }

    /**
     * @param string $childrenModelClassName
     * @param array $incomingData
     * @return array Only the data for the child models for the relation.
     */
    private static function childModelIncomingData(
        $childrenModelClassName,
        array $incomingData
    )
    {
        $shortName = (new ReflectionClass($childrenModelClassName))->getShortName();
        $data = $incomingData[$shortName];
        if ($data === null) {
            $data = [];
        }
        return $data;
    }

    /**
     * Returns child models whose ids are not in $this->incomingData
     * @return ActiveRecord[]
     */
    protected function missingChildren()
    {
        return $this->parent
            ->getRelation($this->relationName)
            ->where([
                'not in',
                'id',
                map(
                    $this->incomingChildrenData,
                    function($config) {
                        return $config['id'];
                    }
                )
            ])
            ->all();
    }

}

