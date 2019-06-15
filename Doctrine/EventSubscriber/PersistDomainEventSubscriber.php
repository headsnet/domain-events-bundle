<?php
/**
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2019
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;

/**
 * Class
 */
class PersistDomainEventSubscriber implements EventSubscriber
{
	/**
	 * @var EventStore
	 */
	private $eventStore;

	/**
	 * @param EventStore $eventStore
	 */
	public function __construct(EventStore $eventStore)
	{
		$this->eventStore = $eventStore;
	}

	/**
	 * @return array
	 */
	public function getSubscribedEvents(): array
	{
		return [
			'prePersist',
			'preUpdate',
			'preRemove'
		];
	}

	/**
	 * @param LifecycleEventArgs $args
	 */
	public function prePersist(LifecycleEventArgs $args): void
	{
		$this->persistEntityDomainEvents($args);
	}

	/**
	 * @param LifecycleEventArgs $args
	 */
	public function preUpdate(LifecycleEventArgs $args): void
	{
		$this->persistEntityDomainEvents($args);
	}

	/**
	 * @param LifecycleEventArgs $args
	 */
	public function preRemove(LifecycleEventArgs $args): void
	{
		$this->persistEntityDomainEvents($args);
	}

	/**
	 * @param LifecycleEventArgs $args
	 */
	private function persistEntityDomainEvents(LifecycleEventArgs $args): void
	{
		$entity = $args->getEntity();

		if ($entity instanceof ContainsEvents)
		{
			foreach ($entity->getRecordedEvents() as $domainEvent)
			{
				$this->eventStore->append($domainEvent);
			}

			$entity->clearRecordedEvents();
		}
	}

}
