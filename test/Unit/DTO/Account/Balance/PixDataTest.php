<?php

declare(strict_types=1);

namespace HyperfTest\Unit\DTO\Account\Balance;

use App\DataTransfer\Account\Balance\PixData;
use App\Enum\PixKeyTypeEnum;
use App\Model\AccountWithdrawPix;
use PHPUnit\Framework\TestCase;
use Mockery;

class PixDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $this->assertEquals(PixKeyTypeEnum::EMAIL, $pixData->type);
        $this->assertEquals('test@example.com', $pixData->key);
    }

    public function testFromArray(): void
    {
        $data = [
            'type' => 'email',
            'key' => 'test@example.com'
        ];

        $pixData = PixData::fromArray($data);

        $this->assertEquals(PixKeyTypeEnum::EMAIL, $pixData->type);
        $this->assertEquals('test@example.com', $pixData->key);
    }

    public function testFromArrayWithInvalidType(): void
    {
        $data = [
            'type' => 'invalid_type',
            'key' => 'test@example.com'
        ];

        $this->expectException(\ValueError::class);
        PixData::fromArray($data);
    }

    public function testFromModel(): void
    {
        $model = Mockery::mock(AccountWithdrawPix::class);
        $model->shouldReceive('getAttribute')->with('type')->andReturn('email');
        $model->shouldReceive('getAttribute')->with('key')->andReturn('test@example.com');
        $model->shouldAllowMockingProtectedMethods();
        $model->shouldIgnoreMissing();
        $model->type = 'email';
        $model->key = 'test@example.com';

        $pixData = PixData::fromModel($model);

        $this->assertEquals(PixKeyTypeEnum::EMAIL, $pixData->type);
        $this->assertEquals('test@example.com', $pixData->key);
    }

    public function testToArray(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $array = $pixData->toArray();

        $expected = [
            'type' => 'email',
            'key' => 'test@example.com'
        ];

        $this->assertEquals($expected, $array);
    }

    public function testValidateWithValidEmail(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $errors = $pixData->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidEmail(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'invalid-email'
        );

        $errors = $pixData->validate();

        $this->assertNotEmpty($errors);
        $this->assertContains('Email PIX inválido.', $errors);
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testValidateWithVariousInvalidEmails(string $invalidEmail): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: $invalidEmail
        );

        $errors = $pixData->validate();

        $this->assertNotEmpty($errors);
        $this->assertContains('Email PIX inválido.', $errors);
    }

    public function invalidEmailProvider(): array
    {
        return [
            'no at symbol' => ['invalid-email'],
            'no domain' => ['test@'],
            'no local part' => ['@example.com'],
            'double dots' => ['test..double@example.com'],
            'empty string' => [''],
            'spaces' => ['test @example.com'],
            'invalid chars' => ['test@exam ple.com'],
            'missing top-level domain' => ['test@example'],
        ];
    }

    /**
     * @dataProvider validEmailProvider
     */
    public function testValidateWithVariousValidEmails(string $validEmail): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: $validEmail
        );

        $errors = $pixData->validate();

        $this->assertEmpty($errors, "Email {$validEmail} should be valid");
    }

    public function validEmailProvider(): array
    {
        return [
            'simple email' => ['test@example.com'],
            'with subdomain' => ['user@mail.example.com'],
            'with numbers' => ['user123@example.com'],
            'with dots in local' => ['user.name@example.com'],
            'with plus sign' => ['user+tag@example.com'],
            'with dashes' => ['user-name@example-domain.com'],
            'short domain' => ['test@ex.co'],
            'long domain' => ['test@very-long-domain-name.example.com'],
        ];
    }

    public function testIsValid(): void
    {
        $validPixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $invalidPixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'invalid-email'
        );

        $this->assertTrue($validPixData->isValid());
        $this->assertFalse($invalidPixData->isValid());
    }

    public function testReadonlyProperties(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        // Properties should be readonly
        $reflection = new \ReflectionClass($pixData);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testFromArrayWithMissingKey(): void
    {
        $data = [
            'type' => 'email'
            // Missing 'key'
        ];

        $this->expectException(\TypeError::class);
        PixData::fromArray($data);
    }

    public function testFromArrayWithMissingType(): void
    {
        $data = [
            'key' => 'test@example.com'
            // Missing 'type'
        ];

        $this->expectException(\TypeError::class);
        PixData::fromArray($data);
    }

    public function testFromArrayWithNullValues(): void
    {
        $data = [
            'type' => null,
            'key' => null
        ];

        $this->expectException(\TypeError::class);
        PixData::fromArray($data);
    }

    public function testSerialization(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        // Test that the object can be serialized and unserialized
        $serialized = serialize($pixData);
        $unserialized = unserialize($serialized);

        $this->assertEquals($pixData->type, $unserialized->type);
        $this->assertEquals($pixData->key, $unserialized->key);
    }

    public function testJsonSerialization(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $array = $pixData->toArray();
        $json = json_encode($array);
        $decodedArray = json_decode($json, true);

        $reconstructed = PixData::fromArray($decodedArray);

        $this->assertEquals($pixData->type, $reconstructed->type);
        $this->assertEquals($pixData->key, $reconstructed->key);
    }

    public function testEquality(): void
    {
        $pixData1 = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $pixData2 = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'test@example.com'
        );

        $pixData3 = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'different@example.com'
        );

        // Since these are value objects, they should be equal if their values are equal
        $this->assertEquals($pixData1->type, $pixData2->type);
        $this->assertEquals($pixData1->key, $pixData2->key);
        $this->assertNotEquals($pixData1->key, $pixData3->key);
    }

    public function testValidationIsIdempotent(): void
    {
        $pixData = new PixData(
            type: PixKeyTypeEnum::EMAIL,
            key: 'invalid-email'
        );

        $errors1 = $pixData->validate();
        $errors2 = $pixData->validate();

        $this->assertEquals($errors1, $errors2);
    }

    public function testEdgeCaseEmails(): void
    {
        $edgeCases = [
            ['a@b.co', true], // Shortest valid email
            ['test@localhost', false], // No TLD
            ['test.email@domain.com', true], // Dot in local part
            ['test+email@domain.com', true], // Plus in local part
            ['test_email@domain.com', true], // Underscore in local part
        ];

        foreach ($edgeCases as [$email, $shouldBeValid]) {
            $pixData = new PixData(
                type: PixKeyTypeEnum::EMAIL,
                key: $email
            );

            $isValid = $pixData->isValid();
            
            if ($shouldBeValid) {
                $this->assertTrue($isValid, "Email {$email} should be valid");
            } else {
                $this->assertFalse($isValid, "Email {$email} should be invalid");
            }
        }
    }
}