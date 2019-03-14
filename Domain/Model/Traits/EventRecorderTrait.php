<?php
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

	/**
	 * @return array
	 */
	public function getRecordedEvents(): array
	{
		return $this->messages;
	}

	/**
	 * @return void
	 */
	public function clearRecordedEvents(): void
	{
		$this->messages = [];
	}

	/**
	 * @param $message
	 */
	public function record($message): void
	{
		$this->messages[] = $message;
	}

}
