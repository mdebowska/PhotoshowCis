<?php
/**
 * Application
 */
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SecurityServiceProvider;


$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

$app['config.photos_directory'] = __DIR__.'/../web/uploads/photos';

$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...
    $twig->addGlobal('photos_directory', $app['config.photos_directory']);

    return $twig;
});

use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\TranslationServiceProvider;

// ...
$app->register(new LocaleServiceProvider());
$app->register(
    new TranslationServiceProvider(),
    [
        'locale' => 'pl',
        'locale_fallbacks' => array('en'),
    ]
);
$app->extend('translator', function ($translator, $app) {
    $translator -> addResource('xliff', __DIR__.'/../translations/messages.en.xlf', 'en', 'messages');
    // $translator->addResource('xliff', __DIR__ . '/../translations/validators.en.xlf', 'en', 'validators');
    $translator -> addResource('xliff', __DIR__.'/../translations/messages.pl.xlf', 'pl', 'messages');
    // $translator->addResource('xliff', __DIR__ . '/../translations/validators.pl.xlf', 'pl', 'validators');

    return $translator;
});

require_once dirname(dirname(__FILE__)).'/config/db.php';


$app->register(
    new SecurityServiceProvider(),
    [
        'security.firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'pattern' => '^.*$',
                'form' => [
                    'login_path' => 'auth_login',
                    'check_path' => 'auth_login_check',
                    'default_target_path' => 'home_index',
                    'username_parameter' => 'login_type[login]',
                    'password_parameter' => 'login_type[password]',
                ],
                'anonymous' => true,
                'logout' => [
                    'logout_path' => 'auth_logout',
                    'target_url' => 'home_index',
                ],
                'users' => function () use ($app) {
                    return new Provider\UserProvider($app['db']);
                },
            ],
        ],
        'security.access_rules' => [
            ['^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/registration$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['.+/delete$', 'IS_AUTHENTICATED_FULLY'],
            ['.+/edit$', 'IS_AUTHENTICATED_FULLY'],
            ['.+/add$', 'IS_AUTHENTICATED_FULLY'],
        ],
        'security.role_hierarchy' => [
            'ROLE_ADMIN' => ['ROLE_USER'],
        ],
    ]
);

$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new SessionServiceProvider());

return $app;
