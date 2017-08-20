<?php

namespace smartsites\yii2\utils;

use yii\db\ActiveRecord;
use function Functional\map;

/**
 * Mass editing of entities many-to-many relationship (i.e. where records are
 * linked via a table).
 *
 * <i>Old children</i> = Deleted + Updated
 *
 * <i>Incoming children</i> = Created + Updated
 *
 * Sets of new and old records may intersect or not, and one can be a subset of
 * the other. This trait transparently supports all these cases.
 *
 * Usage example:
 * <pre>
 * $person = Person::findOne(['id'=>1]);
 * if (Yii::$app->request->isPost()) {
 *   $relationship = new ManyToManyRelationship(
 *     $person,
 *     City::class,
 *     "favouriteCities",
 *     function() {
 *       return City::find();
 *     }
 *     Yii::$app->request->post()
 *   );
 *   $relationship->unlinkMissing();
 *   $relationship->linkIncoming();
 *   $favouriteCities = $relationship->getCurrentChildren();
 *   if (Model::validateMultiple($favouriteCities)) {
 *     foreach ($favouriteCities as $city) {
 *       $city->save();
 *     }
 *     $person->populateRelation('favouriteCities', $favouriteCities);
 *   }
 * }
 * $this->render('update', [
 *   'person' => $person
 * ]);
 * </pre>
 * @see http://www.yiiframework.com/doc-2.0/guide-input-tabular-input.html#combining-update-create-and-delete-on-one-page
 */
class ManyToManyRelationship extends XToManyRelationship
{
    /** @var callable */
    private $findAllChildren;

    /**
     * @inheritdoc
     * @param callable $findAllChildren f() = ActiveQuery that returns a query with
     * all child records.
     */
    public function __construct(
        ActiveRecord $parent,
        $childrenModelClassName,
        $relationName,
        callable $findAllChildren,
        array $incomingData
    )
    {
        parent::__construct(
            $parent,
            $childrenModelClassName,
            $relationName,
            $incomingData
        );
        $this->findAllChildren = $findAllChildren;
    }

    /**
     * Unlinks child models whose ids are not in $this->incomingData
     */
    public function unlinkMissing()
    {
        foreach ($this->missingChildren() as $child) {
            $this->parent->unlink($this->relationName, $child, true);
        }
    }

    /**
     * Links child models whose ids are in $this->incomingData and which are
     * not linked to the parent model yet.
     */
    public function linkIncoming()
    {
        $incomingIds = map(
            $this->incomingChildrenData,
            function($config) {
                return $config['id'];
            }
        );
        $existingChildrenIds =
            map(
                $this->parent
                    ->getRelation($this->relationName)
                    ->select('id')
                    ->where(['in', 'id', $incomingIds])
                    ->asArray()
                    ->all(),
                function($record) {
                    return $record['id'];
                }
            );
        $newChildrenIds = array_diff($incomingIds, $existingChildrenIds);
        $newChildren = call_user_func($this->findAllChildren)
            ->where(['in', 'id', $newChildrenIds])
            ->all();
        foreach ($newChildren as $child) {
            $this->parent->link($this->relationName, $child);
        }
    }

    /**
     * Returns all children of the parent model that are currently in the
     * database.
     * @return ActiveRecord[]
     */
    public function getCurrentChildren() {
        $this->parent->refresh();
        return $this->parent
            ->getRelation($this->relationName)
            ->all();

    }

}

