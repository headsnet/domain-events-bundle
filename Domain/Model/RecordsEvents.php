<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

/**
 * Interface RecordsEvents
 */
interface RecordsEvents
{

	/**
	 * @param $event
	 */
	public function record($event): void;

}
