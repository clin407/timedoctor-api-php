<?php

namespace smartsites\yii2\utils;

use yii\db\ActiveRecord;
use function Functional\filter;
use function Functional\map;
use function Functional\sort;
use function Functional\zip;

/**
 * Mass editing of entities that have one-to-many relationship (i.e. where
 * records are linked via a field in the child record).
 *
 *
 * Usage example:
 * <pre>
 * $family = Family::findOne(['id'=>1]);
 * if (Yii::$app->request->isPost()) {
 *   $relationship = new OneToManyRelationship(
 *     $family,
 *     FamilyMember::class,
 *     "familyMembers",
 *     Yii::$app->request->post()
 *   );
 *   $relationship->deleteMissing();
 *   $familyMembers = $relationship->createUpdate();
 *   $relationship->linkNew($familyMembers);
 *   if (Model::validateMultiple()) {
 *     foreach ($familyMembers as $member) {
 *       $member->save();
 *     }
 *   }
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
class OneToManyRelationship extends XToManyRelationship
{

    /**
     * @inheritdoc
     */
    public function __construct(
        ActiveRecord $parent,
        $childrenModelClassName,
        $relationName,
        array $incomingData
    )
    {
        parent::__construct(
            $parent,
            $childrenModelClassName,
            $relationName,
            $incomingData
        );
    }

    /**
     * Deletes from the database the children of this entity whose ids are not
     * in $this->incomingData entries
     */
    public function deleteMissing()
    {
        foreach ($this->missingChildren() as $child) {
            $child->delete();
        }
    }

    /**
     * Creates models from those arrays in the incoming data whose ids don't
     * exist in the database yet in the children table.
     * - This method doesn't save the records, so they can be optionally
     * validated later.
     * - This method doesn't link the children to parent models, because linking
     * should occur after validation. Child models may be linked via a junction
     * table, so no entries should be inserted into the junction table in case
     * when some of the incoming children are not valid.
     * @return ActiveRecord[]
     */
    public function createNew()
    {
        $oldIds = map(
            $this->parent
                ->getRelation($this->relationName)
                ->select(['id'])
                ->asArray(true)
                ->all(),
            function($it) {
                return $it['id'];
            }
        );
        $newIncomingConfigs = filter(
            $this->incomingChildrenData,
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
                $this->parent->link($this->relationName, $newChild);
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
                $this->incomingChildrenData,
                function($config) {
                    return $config['id'] !== '';
                }
            );
        $updatedModels =
            $this->parent
            ->getRelation($this->relationName)
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
        assert(
            count($incomingRecordsToUpdate) == count($updatedModels),
            count($incomingRecordsToUpdate) ." ". count($updatedModels)
        );
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
                $model->attributes = $incomingConfig;
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

}

