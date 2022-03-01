# Domain Event Bundle

[![Build Status](https://travis-ci.com/headsnet/domain-events-bundle.svg?branch=master)](https://travis-ci.com/headsnet/domain-events-bundle)
[![Latest Stable Version](https://poser.pugx.org/headsnet/domain-events-bundle/v)](//packagist.org/packages/headsnet/domain-events-bundle)
[![Total Downloads](https://poser.pugx.org/headsnet/domain-events-bundle/downloads)](//packagist.org/packages/headsnet/domain-events-bundle)
[![License](https://poser.pugx.org/headsnet/domain-events-bundle/license)](//packagist.org/packages/headsnet/domain-events-bundle)

DDD Domain Events for Symfony, with a Doctrine based event store.

This package allows you to dispatch domain events from within your domain
model, so that they are persisted in the same transaction as your aggregate.

These events are then published using a Symfony event listener in the
`kernel.TERMINATE` event.

This ensures transactional consistency and guaranteed delivery via the Outbox
pattern.

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

Domain events should be dispatched from within your domain model - i.e. from
directly inside your entities.

Here we record a domain event for entity creation. It is then automatically
persisted to the Doctrine `event`
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

Then, in `kernel.TERMINATE` event, a listener automatically publishes the domain
event on to the `messenger.bus.event` event bus for consumption elsewhere.

### Amending domain events

Even though events should be treated as immutable, it might be convenient
to add or change meta data before adding them to the event store.

Before a domain event is appended to the event store,
the standard Doctrine event store emits a `PreAppendEvent` Symfony event,
which can be used e.g. to set the actor ID as in the following example:

```php
use App\Entity\User;
use Headsnet\DomainEventsBundle\Doctrine\Event\PreAppendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

final class AssignDomainEventUser implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreAppendEvent::class => 'onPreAppend'
        ];
    }

    public function onPreAppend(PreAppendEvent $event): void
    {
        $domainEvent = $event->getDomainEvent();
        if (null === $domainEvent->getActorId()) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $domainEvent->setActorId($user->getId());
            }
        }
    }
}
```

### Deferring Events Into The Future

If you specify a future date for the `DomainEvent::occurredOn` the event will
not be published until this date.

This allows scheduling of tasks directly from within the domain model.

#### Replaceable Future Events

If an event implements `ReplaceableDomainEvent` instead of `DomainEvent`,
recording multiple instances of the same event for the same aggregate root will
overwrite previous recordings of the event, as long as it is not yet published.

For example, say you have an aggregate _Booking_, which has a future
_ReminderDue_ event. If the booking is then modified to have a different
date/time, the reminder must also be modified. By implementing
`ReplaceableDomainEvent`, you can simply record a new _ReminderDue_ event, and
providing that the previous _ReminderDue_ event had not been published, it will
be removed and superseded by the new _ReminderDue_ event.

### Event dispatching

By default only the DomainEvent is dispatched to the configured event bus.

You can overwrite the default event dispatcher with your own implementation to
annotate the message before dispatching it, e.g. to add an envelope with custom stamps.

Example:

```yaml
services:
    headsnet_domain_events.domain_event_dispatcher_service:
        class: App\Infrastructure\DomainEventDispatcher
```

```php
class PersonCreated implements DomainEvent, AuditableEvent
{
    …
}
```

```php
class DomainEventDispatcher implements \Headsnet\DomainEventsBundle\EventSubscriber\DomainEventDispatcher
{
    private MessageBusInterface  $eventBus;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function dispatch(DomainEvent $event): void
    {
        if ($event instanceof AuditableEvent) {
            $this->eventBus->dispatch(
                new Envelope($event, [new AuditStamp()])
            );
        } else {
            $this->eventBus->dispatch($event);
        }
    }
}
```

### Messenger Component

By default, the bundle expects a message bus called `messenger.bus.event` to be
available.
This can be configured using the bundle configuration - see
[Default Configuration](#default-configuration).

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

The bundle will create a database table called `event` to persist the events in
before dispatch.
This allows a permanent record of all events raised.

The `StoredEvent` entity also tracks whether each event has been published to
the bus or not.

Finally, a Doctrine DBAL custom type called `datetime_immutable_microseconds` is
automatically registered. This allows the StoredEvent entity to persist events
with microsecond accuracy. This ensures that events are published in the exact
same order they are recorded.

### Legacy Events Classes

During refactorings, you may well move or rename event classes. This will
result in legacy class names being stored in the database.

There is a console command, which will report on these legacy event classes
that do not match an existing, current class in the codebase (based on the
Composer autoloading).

```
bin/console headsnet:domain-events:name-check
```

You can then define the `legacy_map` configuration parameter, to map old,
legacy event class names to their new replacements.

```yaml
headsnet_domain_events:
    legacy_map:
        App\Namespace\Event\YourLegacyEvent1: App\Namespace\Event\YourNewEvent1
        App\Namespace\Event\YourLegacyEvent2: App\Namespace\Event\YourNewEvent2
```

Then you can re-run the console command with the `--fix` option. This will
then update the legacy class names in the database with their new references.

There is also a `--delete` option which will remove all legacy events from
the database if they are not found in the legacy map. **THIS IS A DESTRUCTIVE
COMMAND PLEASE USE WITH CAUTION.**

### Default Configuration

```yaml
headsnet_domain_events:
    message_bus:
        name: messenger.bus.event
    legacy_map: []
```

### Contributing

Contributions are welcome. Please submit pull requests with one fix/feature per
pull request.

Composer scripts are configured for your convenience:

```
> composer test       # Run test suite
> composer cs         # Run coding standards checks
> composer cs-fix     # Fix coding standards violations
> composer static     # Run static analysis with Phpstan
```

