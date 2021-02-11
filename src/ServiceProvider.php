<?php

namespace Codificar\PaymentGateways;

use Codificar\PaymentGateways\Libs\PaymentFactory;
use Omnipay\Common\GatewayFactory;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        // Load routes (carrega as rotas)
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/bancard.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/transbank.php');
        // Load laravel views (Carregas as views do Laravel, blade)
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'gateways');

        // Load Migrations (Carrega todas as migrations)
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        // Load trans files (Carrega tos arquivos de traducao) 
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'settingsTrans');

        // Publish the VueJS files inside public folder of main project (Copia os arquivos do vue minificados dessa biblioteca para pasta public do projeto que instalar essa lib)
        $this->publishes([
            __DIR__ . '/../public/js' => public_path('vendor/codificar/laravel-payment-gateways'),
        ], 'public_vuejs_libs');
        
        // Publish the tests files 
        $this->publishes([
            __DIR__ . '/../tests/' => base_path('tests/libs/gateways'),
        ], 'publishes_gateway_tests');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/omnipay.php';
        $this->publishes([$configPath => config_path('omnipay.php')]);

        $this->app->singleton('omnipay', function ($app) {
            $defaults = $app['config']->get('omnipay.defaults', array());
            return new GatewayManager($app, new GatewayFactory, $defaults);
        });

        $this->app->singleton('PaymentFactory', function ($app) {
            return new PaymentFactory();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('omnipay', 'PaymentFactory');
    }
}
