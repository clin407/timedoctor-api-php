<?php

namespace smartsites\yii2\utils;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use function Functional\every;
use function Functional\filter;
use function Functional\map;
use function Functional\sort;
use function Functional\zip;

/**
 * Implements methods for mass editing of entities that have one-to-many
 * relationship.
 *
 * Terms used in this class' methods and documentation:
 * - Parent object - the "one" in "one-to-many".
 * - Child objects - the "many" in "one-to-many".
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
 * Sets of new and old records may intersect or not, and one can be a subset of
 * the other. This trait transparently supports all these cases.
 *
 * Usage example:
 * <pre>
 * $family = Family::findOne(['id'=>1]);
 * if (Yii::$app->request->isPost()) {
 *   $tabularInput = new TabularInput(
 *     $family,
 *     FamilyMember::class,
 *     function(Family $it) {
 *       return $it->getFamilyMembers();
 *     },
 *     function(Family $parent, FamilyMember $child) {
 *       $child->parent_id = $parent->id;
 *     },
 *     Yii::$app->request->post('FamilyMember')
 *   );
 *   $tabularInput->deleteMissing();
 *   $familyMembers = $tabularInput->createUpdateValidateSave();
 * } else {
 *   $familyMembers = $family->familyMembers;
 * }
 * $this->render('update', [
 *   'family' => $family,
 *   'members' => $familyMembers
 * ]);
 * </pre>
 * @see http://www.yiiframework.com/doc-2.0/guide-input-tabular-input.html#combining-update-create-and-delete-on-one-page
 */
class TabularInput
{

    /** @var ActiveRecord */
    private $parent;

    /** @var string */
    private $childrenModelClassName;

    /** @var callable */
    private $getOldChildrenQuery;

    /** @var callable */
    private $linkParentAndChild;

    /** @var array */
    private $incomingData;

    /**
     * @param ActiveRecord $parent Instance of the parent class whose children
     * we're editing.
     * @param string $childrenModelClassName Fully qualified class name of the
     * child model.
     * @param callable $getOldChildrenQuery f(Parent) Maps this entity to an
     * ActiveQuery with the one-to-many relationship to find the children.
     * <pre>
     * function(Family $family) {
     *   return $family->getFamilyMembers();
     * }
     * </pre>
     * @param callable $linkParentAndChild f(Parent, Child) How to link parent
     * and child without committing them to the database, e.g.
     * <pre>
     * function(Family $family, FamilyMember $familyMember) {
     *   $familyMember->family_id = $family->id;
     * }
     * </pre>
     * @param array $incomingData Part of data received by controller that
     * corresponds to the entities we're loading.
     */
    public function __construct(
        ActiveRecord $parent,
        $childrenModelClassName,
        callable $getOldChildrenQuery,
        callable $linkParentAndChild,
        array $incomingData
    )
    {
        $this->parent = $parent;
        $this->childrenModelClassName = $childrenModelClassName;
        $this->getOldChildrenQuery = $getOldChildrenQuery;
        $this->linkParentAndChild = $linkParentAndChild;
        $this->incomingData = $incomingData;
    }

    /**
     * Deletes from the database the children of this entity whose ids are not
     * in $this->incomingData entries
     */
    public function deleteMissing()
    {
        /** @var ActiveQuery $oldChildrenQuery */
        $oldChildrenQuery = call_user_func($this->getOldChildrenQuery, $this->parent);
        $toBeDeleted =
            $oldChildrenQuery
                ->where([
                    'not in',
                    'id',
                    map(
                        $this->incomingData,
                        function($config) {
                            return $config['id'];
                        }
                    )
                ])
                ->all();
        foreach ($toBeDeleted as $activeRecord) {
            $activeRecord->delete();
        }
    }

    /**
     * Creates models from those arrays in the incoming data whose ids don't
     * exist in the database yet in the children table. This method doesn't save
     * the records, so they can be optionally validated later.
     * @return ActiveRecord[]
     */
    public function createNew()
    {
        /** @var ActiveQuery $query */
        $query = call_user_func($this->getOldChildrenQuery, $this->parent);
        $oldIds = map(
            $query->select(['id'])->asArray(true)->all(),
            function($it) {
                return $it['id'];
            }
        );
        $newIncomingConfigs = filter(
            $this->incomingData,
            function($config) use ($oldIds) {
                return $config['id'] === '';
            }
        );
        return map(
            $newIncomingConfigs,
            function($config) {
                /** @var ActiveRecord $newChild */
                $newChild = new $this->childrenModelClassName();
                $newChild->attributes = $config;
                call_user_func($this->linkParentAndChild, $this->parent, $newChild);
                return $newChild;
            }
        );
    }

    /**
     * Updates the set of models which are both in the database and in the
     * incoming data.
     * @return ActiveRecord[] Active records that should be updated, with new
     * data loaded, but not yet saved.
     */
    public function updateOld()
    {
        $incomingRecordsToUpdate =
            filter(
                $this->incomingData,
                function($config) {
                    return $config['id'] !== '';
                }
            );
        /** @var ActiveQuery $query */
        $query = call_user_func($this->getOldChildrenQuery, $this->parent);
        $updatedModels = $query
            ->where([
                'in',
                'id',
                map(
                    $incomingRecordsToUpdate,
                    function($config) {
                        return $config['id'];
                    }
                )
            ])
            ->orderBy('id ASC')
            ->all();
        assert(count($incomingRecordsToUpdate) == count($updatedModels));
        zip(
            sort(
            // Sort in case controller parameters weren't sorted by id,
            // so every incoming data element will be zipped
            // with a model with the same id.
                $incomingRecordsToUpdate,
                function($left, $right) {
                    return strcmp($left['id'], $right['id']);
                }
            ),
            $updatedModels,
            function($incomingConfig, ActiveRecord $model) {
                $model->load($incomingConfig);
            }
        );
        return $updatedModels;
    }

    /**
     * Creates new records, updates old records and returns them all.
     * @return ActiveRecord[] All new and updated records
     */
    public function createUpdate()
    {
        return array_merge(
            $this->createNew(),
            $this->updateOld()
        );
    }

    /**
     * Creates new records, updates old records and returns them all, validated.
     * @return ActiveRecord[] All new and updated records, validated
     */
    public function createUpdateValidate()
    {
        return map(
            $this->createUpdate(),
            function(ActiveRecord $record) {
                $record->validate();
            }
        );
    }

    /**
     * Creates and updates the child records, validates them and saves all of
     * them if every one of them is valid.
     * @return ActiveRecord[] All new and updated records, validated and saved
     */
    public function createUpdateValidateSave()
    {
        $records = $this->createUpdateValidate();
        if (every(
            $records,
            function(ActiveRecord $record) {
                return !$record->hasErrors();
            }
        )) {
            foreach ($records as $record) {
                $record->save();
            }
        }
        return $records;
    }

}

