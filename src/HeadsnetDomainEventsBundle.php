<?php
/*
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Headsnet\DomainEventsBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\DBAL\Types\Type;
use Headsnet\DomainEventsBundle\Doctrine\DBAL\Types\DateTimeImmutableMicrosecondsType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeadsnetDomainEventsBundle extends Bundle
{
    private const TYPE_NAME = 'datetime_immutable_microseconds';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->addDoctrineMapping($container);
        $this->addDBALCustomType($container);
    }

    private function addDoctrineMapping(ContainerBuilder $container): void
    {
        if (class_exists(DoctrineOrmMappingsPass::class))
        {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createXmlMappingDriver(
                    [realpath(__DIR__ . '/Doctrine/Mapping') => 'Headsnet\DomainEventsBundle\Domain\Model'],
                    []
                )
            );
        }
    }

    private function addDBALCustomType(ContainerBuilder $container): void
    {
        if (false === Type::hasType(self::TYPE_NAME))
        {
            Type::addType(self::TYPE_NAME, DateTimeImmutableMicrosecondsType::class);
        }

        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'types' => [
                    self::TYPE_NAME => DateTimeImmutableMicrosecondsType::class
                ]
            ]
        ]);
    }
}
