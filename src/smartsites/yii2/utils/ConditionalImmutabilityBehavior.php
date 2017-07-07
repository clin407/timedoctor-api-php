<?php

namespace smartsites\yii2\utils;

use ErrorException;
use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Behavior for prohibiting model updates when some condition is true.
 * @property string[] $mutableAttributesNames
 * @see ConditionalImmutabilityBehavior::getMutableAttributesNames()
 */
abstract class ConditionalImmutabilityBehavior extends Behavior
{

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => function (ModelEvent $event) {
                /* @var ActiveRecord $sender */
                $sender = $event->sender;
                if (!$this->areOnlyMutableFieldsDirty($sender) && !$this->canUpdate($sender)) {
                    throw new ErrorException($this->whyCantUpdate());
                }
            }
        ];
    }

    /**
     * @param ActiveRecord $activeRecord
     * @return bool
     */
    private function areOnlyMutableFieldsDirty(ActiveRecord $activeRecord)
    {
        return ArrayHelper::isSubset(
            array_keys($activeRecord->dirtyAttributes),
            $this->mutableAttributesNames
        );
    }

    /**
     * Returns the list of the names of the attributes that are always allowed to change, even if [[canUpdate]] returns
     * false. The model can be mutated by changing only those attributes.
     * @return string[]
     */
    protected abstract function getMutableAttributesNames();


    /**
     * An abstract rule to check whether a model can be updated.
     * @param ActiveRecord $model The model object
     * @return bool True if the $model record can be updated, false otherwise.
     */
    public abstract function canUpdate(ActiveRecord $model);

    /**
     * @return string A human readable reason why the model can't be updated. This message is shown in an exception
     * when there is an update attempt on a model that can't be updated.
     */
    protected abstract function whyCantUpdate();

}
