<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Console;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'headsnet:domain-events:name-check',
    description: 'Check and/or update legacy event class names stored in the database.'
)]
final class EventNameCheckCommand extends Command
{
    private EntityManagerInterface $em;

    /**
     * @var array<string, string|null>
     */
    private array $legacyMap;

    private SymfonyStyle $io;

    private bool $deleteUnfixable;

    /**
     * @param array<string, string> $legacyMap
     */
    public function __construct(EntityManagerInterface $em, array $legacyMap)
    {
        parent::__construct();
        $this->em = $em;
        $this->legacyMap = $legacyMap;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'fix',
                'f',
                InputOption::VALUE_NONE,
                'Automatically fix any errors based on the legacy_map setting.'
            )
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_NONE,
                'Remove events that cannot be fixed using the legacy_map. THIS IS A DESTRUCTIVE COMMAND!'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->deleteUnfixable = $input->getOption('delete');

        if ($input->getOption('fix') && 0 === count($this->legacyMap)) {
            $this->showDefineLegacyMapErrorMessage();

            return Command::INVALID;
        }

        $legacyEvents = $this->findLegacyEventTypes();

        $this->displayLegacyEventsFound($legacyEvents);

        if ($input->getOption('fix')) {
            $this->fixLegacyEvents($legacyEvents);
        }

        return Command::SUCCESS;
    }

    private function showDefineLegacyMapErrorMessage(): void
    {
        $this->io->error([
            "You must define the legacy mappings before you can fix event class names.\n\n" .
            "In headsnet_domain_events.yaml, configure the 'legacy_map' option. E.g.\n\n" .
            "headsnet_domain_events:\n" .
            "  legacy_map:\n" .
            "    App\Namespace\Event\YourLegacyEvent1: App\Namespace\Event\YourNewEvent1\n" .
            "    App\Namespace\Event\YourLegacyEvent2: App\Namespace\Event\YourNewEvent2\n",
        ]);
    }

    /**
     * @return array<string>
     */
    protected function loadEventTypesFromEventStore(): array
    {
        $eventTypes = $this->em->createQueryBuilder()
            ->select([
                'event.typeName',
            ])
            ->from(StoredEvent::class, 'event')
            ->groupBy('event.typeName')
            ->orderBy('event.typeName', Criteria::ASC)
            ->getQuery()
            ->getResult();

        return array_map(
            function (array $type): string {
                return $type['typeName'];
            },
            $eventTypes
        );
    }

    /**
     * @return array<string>
     */
    protected function findLegacyEventTypes(): array
    {
        $legacyEvents = [];
        foreach ($this->loadEventTypesFromEventStore() as $storedEventClass) {
            if (!class_exists($storedEventClass)) {
                $legacyEvents[] = $storedEventClass;
            }
        }

        return $legacyEvents;
    }

    /**
     * @param array<string> $legacyEvents
     */
    protected function displayLegacyEventsFound(array $legacyEvents): void
    {
        if (count($legacyEvents) > 0) {
            $this->io->warning(
                sprintf('Found %d legacy event classes found', count($legacyEvents))
            );

            array_map(
                function (string $legacyEvent) {
                    $this->io->text($legacyEvent);
                },
                $legacyEvents
            );
        }
    }

    /**
     * @param array<string> $legacyEvents
     */
    protected function fixLegacyEvents(array $legacyEvents): void
    {
        array_map(
            function (string $legacyEvent) {
                $this->tryFixingEventClass($legacyEvent);
            },
            $legacyEvents
        );
    }

    private function tryFixingEventClass(string $eventClass): void
    {
        if (!array_key_exists($eventClass, $this->legacyMap)) {
            $this->io->error(sprintf("Cannot fix - not found in legacy map:\n%s", $eventClass));

            return;
        }

        if (null !== $this->legacyMap[$eventClass]) {
            $this->fixLegacyEventName($eventClass);
        } elseif ($this->deleteUnfixable) {
            $this->removeLegacyEvent($eventClass);
        }
    }

    private function fixLegacyEventName(string $eventClass): void
    {
        $this->io->success(
            sprintf("Fixing legacy event\n%s =>\n%s", $eventClass, $this->legacyMap[$eventClass])
        );

        $this->em->createQueryBuilder()
            ->update(StoredEvent::class, 'event')
            ->set('event.typeName', ':new_name')
            ->where('event.typeName = :old_name')
            ->setParameter('new_name', $this->legacyMap[$eventClass])
            ->setParameter('old_name', $eventClass)
            ->getQuery()
            ->execute();
    }

    private function removeLegacyEvent(string $eventClass): void
    {
        $this->io->warning(
            sprintf("Removing legacy event\n%s", $eventClass)
        );

        $this->em->createQueryBuilder()
            ->delete(StoredEvent::class, 'event')
            ->where('event.typeName = :event_to_delete')
            ->setParameter('event_to_delete', $eventClass)
            ->getQuery()
            ->execute();
    }
}
