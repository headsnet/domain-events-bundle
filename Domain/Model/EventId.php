<?php
/**
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class EventId
{
    /**
     * @var UuidInterface
     */
    private $id;

    /**
     * @param UuidInterface $id
     */
    private function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    /**
     * @param string $id
     * @return EventId
     */
    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    /**
     * @param UuidInterface $uuid
     * @return EventId
     */
    public static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function asString(): string
    {
        return $this->id->toString();
    }

    /**
     * @param EventId $compareWith
     * @return bool
     */
    public function equals(self $compareWith): bool
    {
        return $this->id->toString() === $compareWith->id->toString();
    }
}
