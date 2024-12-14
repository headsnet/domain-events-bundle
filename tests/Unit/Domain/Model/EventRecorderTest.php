<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Unit\Domain\Model;

use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\RecordsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\DomainEventTrait;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\EventRecorderTrait;
use PHPUnit\Framework\TestCase;

class EventRecorderTest extends TestCase
{
    public function test_event_is_recorded(): void
    {
        $sut = $this->fakeAggregate();
        $event = $this->fakeEvent();

        $sut->record($event);

        $this->assertCount(1, $sut->getRecordedEvents());
        $this->assertSame($event, $sut->getRecordedEvents()[0]);
    }

    public function test_multiple_duplicate_events_are_all_recorded(): void
    {
        $sut = $this->fakeAggregate();
        $event = $this->fakeEvent();

        $sut->record($event);
        $sut->record($event);

        $this->assertCount(2, $sut->getRecordedEvents());
        $this->assertSame($event, $sut->getRecordedEvents()[0]);
        $this->assertSame($event, $sut->getRecordedEvents()[1]);
    }

    public function test_record_once_ignores_duplicate_event(): void
    {
        $sut = $this->fakeAggregate();
        $event = $this->fakeEvent();

        $sut->recordOnce($event);
        $sut->recordOnce($event);

        $this->assertCount(1, $sut->getRecordedEvents());
        $this->assertSame($event, $sut->getRecordedEvents()[0]);
    }

    /**
     * @return RecordsEvents&ContainsEvents
     */
    public function fakeAggregate(): object
    {
        return new class() implements RecordsEvents, ContainsEvents {
            use EventRecorderTrait;
        };
    }

    public function fakeEvent(): DomainEvent
    {
        return new class() implements DomainEvent {
            use DomainEventTrait;
        };
    }
}
