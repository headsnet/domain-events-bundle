<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

/**
 * Interface ContainsEvents
 */
interface ContainsEvents
{

	/**
	 * @return array
	 */
	public function getRecordedEvents(): array;

	/**
	 * @return void
	 */
	public function clearRecordedEvents(): void;

}
