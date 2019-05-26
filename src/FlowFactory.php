<?php

namespace DarkGhostHunter\Laraflow;

use DarkGhostHunter\FlowSdk\Adapters\GuzzleAdapter;
use DarkGhostHunter\FlowSdk\Flow;
use DarkGhostHunter\Laraflow\FlowHelpersServiceProvider as Helper;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Psr\Log\LoggerInterface;

/**
 * Class FlowFactory
 * This class is in charge of creating a Flow instance, rather than using the Service Provider.
 *
 * @package DarkGhostHunter\Flow
 */
class FlowFactory
{
    /**
     * Flow instance
     *
     * @var \DarkGhostHunter\FlowSdk\Flow
     */
    protected $flow;

    /**
     * Application Container
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Configuration Repository
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * FlowFactory constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Application $app, LoggerInterface $logger, Repository $config)
    {
        $this->flow = new Flow($logger);
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * @return \DarkGhostHunter\FlowSdk\Flow
     * @throws \Exception
     */
    public function configure()
    {
        $this->setEnvironment();

        $this->setCredentials();

        $this->setReturnUrls();

        $this->setWebhooks();

        $this->setAdapter();

        return $this->flow;
    }

    /**
     * Sets the Environment
     *
     * @return void
     */
    protected function setEnvironment()
    {
        $this->flow->isProduction($this->config->get('flow.environment') === 'production');
    }

    /**
     * Sets the Credentials
     *
     * @throws \Exception
     */
    protected function setCredentials()
    {
        $this->flow->setCredentials($this->app['config']['flow.credentials']);
    }

    /**
     * Set the Return URLs
     *
     * @throws \Exception
     */
    protected function setReturnUrls()
    {
        $this->flow->setReturnUrls(array_filter($this->app['config']['flow.returns']));
    }

    /**
     * Set the Webhooks and the Secret key for them
     *
     * @return void
     * @throws \Exception
     */
    protected function setWebhooks()
    {
        $webhooks = $this->config->get('flow.webhooks');

        if ($this->config->get('flow.webhooks-defaults')) {
            /** @var \Illuminate\Contracts\Routing\UrlGenerator $url */
            $url = $this->app->make(UrlGenerator::class);

            $webhooks = array_merge([
                'payment.urlConfirmation' => $url->to(Helper::WEBHOOK_PATH . '/payment'),
                'refund.urlCallBack' => $url->to(Helper::WEBHOOK_PATH . '/refund'),
                'plan.urlCallback' => $url->to(Helper::WEBHOOK_PATH . '/plan'),
            ], $webhooks);
        }

        $this->flow->setWebhookUrls(array_filter($webhooks));

        if ($secret = $this->config->get('flow.webhook-secret')) {
            $this->flow->setWebhookSecret($secret);
        }
    }

    /**
     * Set the Flow Adapter
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function setAdapter()
    {
        $this->flow->setAdapter(($adapter = $this->config->get('flow.adapter'))
            ? $this->app->make($adapter, [$this->flow])
            : new GuzzleAdapter($this->flow)
        );
    }
}