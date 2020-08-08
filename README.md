# Domain Event Bundle

[![Build Status](https://travis-ci.com/headsnet/domain-events-bundle.svg?branch=master)](https://travis-ci.com/headsnet/domain-events-bundle)

DDD Domain Events for Symfony, with a Doctrine based event store.

Based on the Observer pattern, this package allows you to dispatch domain events from
within your domain model, so that they are persisted in the same transaction as your aggregate.

These events are then published using a Symfony event listener in the `kernel.TERMINATE` event.

This ensures transactional consistency and guaranteed delivery via the Outbox pattern.

_Requires Symfony 4.4, or Symfony 5.x_

### Installation

```bash
composer require headsnet/domain-events-bundle
```

(see [Messenger Component](#messenger-component) below for prerequisites)

### The Domain Event Class

A domain event class must be instantiated with an aggregate root ID.

You can add other parameters to the constructor as required.

```php
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\DomainEventTrait;

final class DiscountWasApplied implements DomainEvent
{
    use DomainEventTrait;

    public function __construct(string $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;
        $this->occurredOn = (new \DateTimeImmutable)->format(DateTime::ATOM);
    }
}
```

### Recording Events

Domain events should be dispatched from within your domain model - i.e. from directly inside your entities.

Here we record a domain event for entity creation. It is then automatically persisted to the Doctrine `event`
database table in the same database transaction as the main entity is persisted.

```php
use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\RecordsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\EventRecorderTrait;

class MyEntity implements ContainsEvents, RecordsEvents
{
	use EventRecorderTrait;

	public function __construct(PropertyId $uuid)
    	{
    	    $this->uuid = $uuid;

    	    // Record a domain event
    	    $this->record(
    		    new DiscountWasApplied($uuid->asString())
    	    );
    	}
}
```

Then, in `kernel.TERMINATE` event, a listener automatically publishes the domain event on to the `messenger.bus.event`
event bus for consumption elsewhere.

### Deferring Events Into The Future

If you specify a future date for the `DomainEvent::occurredOn` the event will not be published until this date.

This allows scheduling of tasks directly from within the domain model.

### Replaceable Events (Future)

If an event implements `ReplaceableDomainEvent` instead of `DomainEvent`, recording multiple instances of the same
event for the same aggregate root will overwrite previous recordings of the event, as long as it is not yet published.

For example, say you have an aggregate _Booking_, which has a future _ReminderDue_ event. If the booking is then modified
to have a different date/time, the reminder must also be modified. By implementing `ReplaceableDomainEvent`, you can
simply record a new _ReminderDue_ event, and providing that the previous _ReminderDue_ event had not been published, it will be
removed and superseded by the new _ReminderDue_ event.

### Messenger Component

The bundle expects an event bus defined as `messenger.bus.event` to be available before the package is required.

```yaml
framework:
    messenger:
        …

        buses:
            messenger.bus.event:
                # Optional
                default_middleware: allow_no_handlers
```

[Symfony Messenger/Multiple Buses](https://symfony.com/doc/current/messenger/multiple_buses.html)

### Doctrine

The bundle will create a database table called `event` to persist the events in before dispatch.

This allows a permanent record of all events raised.

The `StoredEvent` entity also tracks whether each event has been published to the bus or not.

### TODO

* Allow configuration of event bus name

### Contributing

Contributions are welcome. Please submit pull requests with one fix/feature per PR.

