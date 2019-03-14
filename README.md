# Domain Event Bundle

DDD Domain Events for Symfony, with a Doctrine based event store.

Based on the Observer pattern, this package allows you to dispatch domain events from 
with your domain model, so that they are persisted in the same transaction as your aggregate.

These events are then published using a Symfony event listener in the `kernel.TERMINATE` event.

### Installation

```bash
composer require headsnet/domain-events-bundle
```

### Entity Configuration

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
    		    new MyEntityCreated($uuid->asString())
    	    );
    	}
}
```

Then, in `kernel.TERMINATE` event, a listener automatically publishes the domain event on to the `messenger.bus.event` 
event bus for consumption elsewhere.

### Messenger Component

The bundle expects an event bus defined as `messenger.bus.event` to be available.

### Doctrine

The bundle will create a database table called `event` to persist the events in before dispatch.

This allows a permanent record of all events raised.

The `StoredEvent` entity also tracks whether each event has been published to the bus or not.

### TODO

* Allow configuration of event bus name
