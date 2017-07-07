<?php

namespace smartsites\yii2\utils;

use ErrorException;
use yii\db\ActiveRecord;

/**
 * Consider this problem: we need to update product prices, but we need to store old prices of a product to correctly
 * render receipts.
 *
 * The solution to this problem is: to update a product type, don't update it, but copy it and disable the old
 * entity, so a user can't find it via search. Doing so is called replacement, and this trait provides such behavior
 * for an entity.
 *
 * It is safe to actually update an entity if nothing depends on in (e.g. it is safe to update a meal if there are no
 * orders placed for that meal), so in this case [[Replaceable::replace()]] will just update the original entity.
 *
 * Class must implement [[Disableable]] interface to implement this trait.
 * @see Disableable
 */
trait Replaceable
{

    /**
     * @param callable $modify A procedure to modify an existing instance or a copy of existing instance. In this
     * procedure, don't save the instance, and don't disable it — it is done automatically. The point of this
     * parameter is to abstract away whether an instance is edited or cloned and then edited.
     * @return ActiveRecord Either this object, or a copy of this object.
     * @throws ErrorException If can't save the old object or the copy of the old object.
     */
    public function replace(callable $modify)
    {
        /* @var ActiveRecord $this */
        if ($this->canBeUpdated()) {
            $editedInstance = $this;
        } else {
            $editedInstance = $this->createNewInstance();
            $editedInstance->attributes = $this->attributes;
            $this->disable();
        }
        /* @var ActiveRecord $result */
        $modify($editedInstance);
        $saved = $editedInstance->save();
        if (!$saved) {
            throw new ErrorException("Couldn't save:\n" . var_export($editedInstance->errors, true));
        }
        return $editedInstance;
    }

    /**
     * @return ActiveRecord Creates a new empty instance of this model.
     */
    protected abstract function createNewInstance();

    private function canBeUpdated()
    {
        /* @var ActiveRecord $this */
        /* @var ConditionalImmutabilityBehavior $behavior */
        $behavior = $this->getBehavior(ConditionalImmutabilityBehavior::className());
        return $behavior->canUpdate($this);
    }

    /**
     * @throws ErrorException If can't be saved.
     */
    private function disable()
    {
        /* @var \app\utils\Disableable $this */
        $this->enabled = 0;
        $saved = $this->save();
        if (!$saved) {
            throw new ErrorException("Couldn't save:\n" . var_export($this->errors, true));
        }
    }

}
