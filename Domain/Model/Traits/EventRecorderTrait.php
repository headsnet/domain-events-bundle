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

namespace Headsnet\DomainEventsBundle\Domain\Model\Traits;

/**
 * Trait EventRecorderTrait
 */
trait EventRecorderTrait
{
	/**
	 * @var array
	 */
	private $messages = [];

	public function getRecordedEvents(): array
	{
		return $this->messages;
	}

	public function clearRecordedEvents(): void
	{
		$this->messages = [];
	}

	public function record($message): void
	{
		$this->messages[] = $message;
	}

}
