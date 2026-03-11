<?php declare(strict_types=1);

namespace AvhGlitchtip\Subscriber;

use Sentry\Event;
use Sentry\EventHint;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class SentryConfigSubscriber implements EventSubscriberInterface
{
    private const CONFIG_PREFIX = 'AvhGlitchtip.config.';

    private const IGNORED_EXCEPTIONS = [
        NotFoundHttpException::class,
        CustomerNotLoggedInException::class,
        BadCredentialsException::class,
        ProductNotFoundException::class,
    ];

    private bool $initialized = false;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly string $shopwareVersion,
        private readonly string $kernelEnvironment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 4096],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->initialized || !$event->isMainRequest()) {
            return;
        }

        $this->initialized = true;

        $enabled = $this->systemConfigService->getBool(self::CONFIG_PREFIX . 'enabled');
        $dsn = $this->systemConfigService->getString(self::CONFIG_PREFIX . 'dsn');

        if ($enabled && $dsn !== '') {
            $this->initFromAdminConfig($dsn);
        } elseif ($enabled && $dsn === '') {
            // Enabled but no DSN in admin — let sentry.yaml env-based config handle it
            return;
        } else {
            // Not enabled via admin — check if there's explicit admin config at all
            $configValue = $this->systemConfigService->get(self::CONFIG_PREFIX . 'enabled');
            if ($configValue !== null) {
                // Admin explicitly disabled — override any env config
                \Sentry\init(['dsn' => '']);
            }
            // No admin config set at all — let sentry.yaml env-based config handle it
        }
    }

    private function initFromAdminConfig(string $dsn): void
    {
        $environment = $this->systemConfigService->getString(self::CONFIG_PREFIX . 'environment');
        $release = $this->systemConfigService->getString(self::CONFIG_PREFIX . 'release');
        $errorSampleRate = $this->systemConfigService->getFloat(self::CONFIG_PREFIX . 'errorSampleRate');
        $tracesSampleRate = $this->systemConfigService->getFloat(self::CONFIG_PREFIX . 'tracesSampleRate');
        $sendDefaultPii = $this->systemConfigService->getBool(self::CONFIG_PREFIX . 'sendDefaultPii');

        \Sentry\init([
            'dsn' => $dsn,
            'environment' => $environment !== '' ? $environment : $this->kernelEnvironment,
            'release' => $release !== '' ? $release : 'shopware@' . $this->shopwareVersion,
            'sample_rate' => $errorSampleRate,
            'traces_sample_rate' => $tracesSampleRate,
            'send_default_pii' => $sendDefaultPii,
            'before_send' => $this->beforeSend(...),
        ]);
    }

    private function beforeSend(Event $event, ?EventHint $hint): ?Event
    {
        if ($hint !== null && $hint->exception !== null) {
            foreach (self::IGNORED_EXCEPTIONS as $ignoredClass) {
                if ($hint->exception instanceof $ignoredClass) {
                    return null;
                }
            }
        }

        return $event;
    }
}
