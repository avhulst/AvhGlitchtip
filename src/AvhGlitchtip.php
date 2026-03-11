<?php declare(strict_types=1);

namespace AvhGlitchtip;

use Sentry\SentryBundle\SentryBundle;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AvhGlitchtip extends Plugin
{
    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        return [
            new SentryBundle(),
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->buildDefaultConfig($container);
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }
}
