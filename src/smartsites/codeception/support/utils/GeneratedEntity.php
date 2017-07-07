<?php

namespace smartsites\codeception\support\utils;

use yii\db\ActiveRecordInterface;

/**
 * Generated entities are automatically populated models. When a [[GeneratedEntity]] is created, its dependencies are
 * optionally either injected via constructor, or created automatically in its constructor (it is the responsibility of
 * the implementor of a GeneratedEntity descendant to implement that logic correctly, though it is mostly
 * straight-forward).
 * @see Generated* classes for examples of GeneratedEntity implementation.
 */
trait GeneratedEntity
{


    /**
     * Yii requires this, so e.g. "generated" model objects can be refreshed with $activeRecord->refresh()
     * @param array $row
     * @return ActiveRecordInterface
     */
    public static function instantiate($row)
    {
        return new parent();
    }

    /**
     * @return $this
     */
    public function saved() {
        $this->noisySave();
        return $this;
    }

    /**
     * @param callable $how
     * @return $this
     */
    public function modify(callable $how) {
        $how($this);
        return $this;
    }


}
