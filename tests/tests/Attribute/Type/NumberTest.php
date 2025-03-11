<?php

namespace Concrete\Tests\Attribute\Type;

use Concrete\TestHelpers\Attribute\AttributeTypeTestCase;

class NumberTest extends AttributeTypeTestCase
{
    protected $atHandle = 'number';

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->metadatas[] = 'Concrete\Core\Entity\Attribute\Key\Settings\EmptySettings';
    }

    public function testValidateFormEmptyArray(): void
    {
        $this->assertFalse($this->ctrl->validateForm(null));
    }
}
