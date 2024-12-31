<?php
/*
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Headsnet\DomainEventsBundle\Tests\Unit\Domain\Model;

use Headsnet\DomainEventsBundle\Domain\Model\EventId;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use PHPUnit\Framework\TestCase;

class StoredEventTest extends TestCase
{
    private const EVENT_ID = '2ffd5b4b-3f0f-48da-8a09-dd68b903b5f8';
    private const ROOT_ID = '808621ef-8a0b-4c21-84e9-5ee3d7e186d5';
    private const TYPE_NAME = 'Acme\\Namespace\\SomeClass';

    public function test_event_id_getter_returns_correct_value(): void
    {
        $storedEvent = $this->buildStoredEvent();

        $this->assertInstanceOf(EventId::class, $storedEvent->getEventId());
        $this->assertEquals(self::EVENT_ID, $storedEvent->getEventId()->asString());
    }

    public function test_root_id_getter_returns_correct_value(): void
    {
        $storedEvent = $this->buildStoredEvent();

        $this->assertEquals(self::ROOT_ID, $storedEvent->getAggregateRoot());
    }

    public function test_type_name_getter_returns_correct_value(): void
    {
        $storedEvent = $this->buildStoredEvent();

        $this->assertEquals(self::TYPE_NAME, $storedEvent->getTypeName());
    }

    public function test_published_on_setter(): void
    {
        $storedEvent = $this->buildStoredEvent();

        $publishedOn = new \DateTimeImmutable();
        $storedEvent = $storedEvent->setPublishedOn($publishedOn);

        $this->assertEquals($publishedOn, $storedEvent->getPublishedOn());
    }

    private function buildStoredEvent(): StoredEvent
    {
        return new StoredEvent(
            EventId::fromString(self::EVENT_ID),
            self::TYPE_NAME,
            new \DateTimeImmutable(),
            self::ROOT_ID,
            ''
        );
    }
}
