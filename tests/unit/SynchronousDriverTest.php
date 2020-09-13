<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\PrioritizedPayload;
use Yiisoft\Yii\Queue\Tests\App\RetryablePayload;
use Yiisoft\Yii\Queue\Tests\App\SimplePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class SynchronousDriverTest extends TestCase
{
    protected function needsRealDriver(): bool
    {
        return true;
    }

    /**
     * @dataProvider jobTypesProvider
     *
     * @param string $class
     * @param bool $available
     */
    public function testJobType(string $class, bool $available): void
    {
        $this->markTestSkipped('Needs to be moved to the QueueTest and to replace real driver with a mock');

        $queue = $this->container->get(Queue::class);
        $job = $this->container->get($class);

        if (!$available) {
            $this->expectException(PayloadNotSupportedException::class);
        }

        $id = $queue->push($job);

        if ($available) {
            self::assertTrue($id >= 0);
        }
    }

    public static function jobTypesProvider(): array
    {
        return [
            'Simple job' => [
                SimplePayload::class,
                true,
            ],
            DelayablePayloadInterface::class => [
                DelayablePayload::class,
                false,
            ],
            PrioritisedPayloadInterface::class => [
                PrioritizedPayload::class,
                false,
            ],
            AttemptsRestrictedPayloadInterface::class => [
                RetryablePayload::class,
                true,
            ],
        ];
    }

    public function testNonIntegerId(): void
    {
        $queue = $this->getQueue();
        $job = new SimplePayload();
        $id = $queue->push($job);
        $wrongId = "$id ";
        self::assertEquals(JobStatus::waiting(), $queue->status($wrongId));
    }

    public function testIdSetting(): void
    {
        $message = new Message('simple', [], []);
        $driver = $this->getDriver();
        $driver->setQueue($this->createMock(Queue::class));

        $ids = [];
        $ids[] = $driver->push($message);
        $ids[] = $driver->push($message);
        $ids[] = $driver->push($message);

        self::assertCount(3, array_unique($ids));
    }
}
