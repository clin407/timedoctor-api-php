<?php

namespace smartsites\codeception\support\tests;

use yii\db\BaseActiveRecord;

/**
 * Unit tests for classes that extend the model classes and automatically fill their fields with fake generated values.
 */
trait GeneratedEntityTest
{

    use TestWithSeededFaker;

    private function howManyEntitiesToGenerate()
    {
        return 2;
    }

    public abstract function modelName();

    public function testIsValidWhenGenerated()
    {
        for ($i = 0; $i < $this->howManyEntitiesToGenerate(); $i++) {
            $entity = $this->createGeneratedEntity();
            $this->assertTrue(
                $entity->validate(),
                print_r($entity->errors, true)
            );
        }
    }

    public function testCanBeSavedWhenGenerated()
    {
        for ($i = 0; $i < $this->howManyEntitiesToGenerate(); $i++) {
            $entity = $this->createGeneratedEntity();
            $entity->save();
            $this->assertEmpty($entity->errors, print_r($entity->errors, true));
        }
    }

    /**
     * @return BaseActiveRecord Entity instance;
     */
    private function createGeneratedEntity()
    {
        $generatedEntityName = self::modelName();
        return new $generatedEntityName($this->faker);
    }

}
