<?php
/*
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model\Traits;

use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;

trait EventRecorderTrait
{
    /**
     * @var DomainEvent[]
     */
    private $messages = [];

    /**
     * @return DomainEvent[]
     */
    public function getRecordedEvents(): array
    {
        return $this->messages;
    }

    public function clearRecordedEvents(): void
    {
        $this->messages = [];
    }

    public function record(DomainEvent $message): void
    {
        $this->messages[] = $message;
    }

    public function recordOnce(DomainEvent $message): void
    {
        if (in_array($message, $this->messages, true)) {
            return;
        }

        $this->messages[] = $message;
    }
}
