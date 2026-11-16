<?php

use Composer\InstalledVersions;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\VarExporter\ProxyHelper;

return static function (ContainerConfigurator $container): void {
    // DoctrineBundle 3 always uses native lazy objects and no longer has the proxy options
    if (version_compare((string) InstalledVersions::getVersion('doctrine/doctrine-bundle'), '3.0', '>=')) {
        return;
    }

    // symfony/var-exporter 8 removed LazyGhost, so ORM 3 needs native lazy objects there
    if (!method_exists(ProxyHelper::class, 'generateLazyGhost') && version_compare((string) InstalledVersions::getVersion('doctrine/orm'), '3.4', '>=')) {
        $container->extension('doctrine', ['orm' => ['enable_native_lazy_objects' => true]]);

        return;
    }

    $container->extension('doctrine', ['orm' => ['auto_generate_proxy_classes' => true]]);
};
