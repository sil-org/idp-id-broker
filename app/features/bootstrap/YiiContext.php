<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeSuite;
use Sil\Psr3Adapters\Psr3StdOutLogger;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailer;
use Sil\SilIdBroker\Behat\Context\fakes\FakeLogTarget;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Application;

class YiiContext implements Context
{
    /** @var FakeEmailer */
    protected $fakeEmailer;

    /** @var FakeLogTarget */
    protected $fakeLogTarget;

    private array $savedParams = [];

    private static $application;

    #[BeforeScenario]
    public function setupLogger()
    {
        $yiiCommonConfig = require __DIR__ . '/../../common/config/main.php';
        $yiiEmailerConfig = $yiiCommonConfig['components']['emailer'];
        unset($yiiEmailerConfig['class']);

        $this->fakeEmailer = new FakeEmailer(ArrayHelper::merge(
            $yiiEmailerConfig,
            [
                'logger' => new Psr3StdOutLogger(),
            ]
        ));
        $this->fakeEmailer->emailRepeatDelayDays = 31;
        $this->fakeEmailer->minimumBackupCodesBeforeNag = 4;

        Yii::$app->set('emailer', $this->fakeEmailer);

        $this->addFakeLogTarget();
    }

    #[BeforeScenario]
    public function saveParameters()
    {
        // Snapshot params individual scenarios may toggle, then restore them in
        // restoreParameters(), so a change can't leak via the shared (static) application
        // instance — and config/test.env stays the single source of truth.
        foreach (['accountMailAdminsCcOnInvite', 'accountMailAdminsCcFallback'] as $key) {
            $this->savedParams[$key] = Yii::$app->params[$key] ?? null;
        }
    }

    #[AfterScenario]
    public function restoreParameters()
    {
        foreach ($this->savedParams as $key => $value) {
            Yii::$app->params[$key] = $value;
        }
    }

    protected function addFakeLogTarget()
    {
        $this->fakeLogTarget = new FakeLogTarget([
            'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
            'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
        ]);
        Yii::$app->log->targets[] = $this->fakeLogTarget;
    }

    #[BeforeSuite]
    public static function loadYiiApp()
    {
        if (empty(self::$application)) {
            $config = require(__DIR__ . '/../../frontend/config/load-configs.php');

            self::$application = new Application($config);
        }
    }
}
