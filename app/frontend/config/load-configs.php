<?php

use Sil\PhpEnv\Env;
use yii\helpers\ArrayHelper;

require(__DIR__ . '/../../vendor/autoload.php');

define('YII_ENV', Env::get('APP_ENV', 'prod'));
define('YII_DEBUG', YII_ENV === 'dev' || YII_ENV === 'test');

\Sentry\init([
    'dsn' => getenv('SENTRY_DSN'),

    // Set tracesSampleRate to 1.0 to capture 100%
    // of transactions for performance monitoring.
    'traces_sample_rate' => 1.0,
]);

require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

require(__DIR__ . '/../../common/config/bootstrap.php');

$config = ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../config/main.php')
);

return $config;
