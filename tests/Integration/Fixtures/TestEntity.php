<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Integration\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\RecordsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\EventRecorderTrait;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
class TestEntity implements RecordsEvents, ContainsEvents
{
    use EventRecorderTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
