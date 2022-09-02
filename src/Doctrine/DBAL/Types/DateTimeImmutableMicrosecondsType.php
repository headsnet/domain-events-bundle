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

namespace Headsnet\DomainEventsBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class DateTimeImmutableMicrosecondsType extends VarDateTimeImmutableType
{
    public function getName(): string
    {
        return 'datetime_immutable_microseconds';
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (isset($column['version']) && $column['version']) {
            return 'TIMESTAMP';
        }

        return 'DATETIME(6)';
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof \DateTimeImmutable &&
            ($platform instanceof PostgreSqlPlatform || $platform instanceof MySQLPlatform)
        ) {
            $dateTimeFormat = $platform->getDateTimeFormatString(); // @phpstan-ignore-line

            return $value->format("{$dateTimeFormat}.u");
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
