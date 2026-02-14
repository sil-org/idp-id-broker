<?php

namespace console\controllers;

use common\components\adapters\IdStoreInterface;
use common\components\Monitor;
use common\components\notify\NotifierInterface;
use common\sync\Synchronizer;
use Sentry\CheckInStatus;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use Yii;
use yii\console\Controller;
use function Sentry\captureCheckIn;

class BatchController extends Controller
{
    public function actionFull()
    {
        $sentryMonitorSlug = Yii::$app->params['sentryMonitorSlug'];
        if ($sentryMonitorSlug !== "") {
            $checkInId = captureCheckIn(
                slug: $sentryMonitorSlug,
                status: CheckInStatus::inProgress()
            );
        }

        $synchronizer = $this->getSynchronizer();
        $synchronizer->syncAllNotifyException();

        if ($sentryMonitorSlug != "") {
            captureCheckIn(
                slug: $sentryMonitorSlug,
                status: CheckInStatus::ok(),
                checkInId: $checkInId,
            );
        }

        /* @var $monitor Monitor */
        $monitor = Yii::$app->monitor;
        $monitor->Heartbeat();
    }

    protected function getSynchronizer()
    {
        /* @var $idStore IdStoreInterface */
        $idStore = Yii::$app->idStore;

        /* @var $idBroker IdBrokerInterface */
        $idBroker = Yii::$app->idBroker;

        /* @var $notifier NotifierInterface */
        $notifier = Yii::$app->notifier;

        $logger = new Psr3Yii2Logger();
        $syncSafetyCutoff = Yii::$app->params['syncSafetyCutoff'];

        $enableNewUserNotification = Yii::$app->params['enableNewUserNotification'] ?? false;

        return new Synchronizer(
            $idStore,
            $logger,
            $notifier,
            $syncSafetyCutoff,
            $enableNewUserNotification
        );
    }
}
