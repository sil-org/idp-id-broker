<?php

namespace console\controllers;

use common\components\adapters\IdStoreInterface;
use common\components\Monitor;
use common\components\notify\NotifierInterface;
use common\sync\Synchronizer;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use Yii;
use yii\console\Controller;

class BatchController extends Controller
{
    /**
     * Run a full sync.
     * Compares all data from the ID Store against all data in the ID Broker database and performs any
     * needed creations, activations, or deactivations
     */
    public function actionFull()
    {
        $synchronizer = $this->getSynchronizer();
        $synchronizer->syncAllNotifyException();

        /* @var $monitor Monitor */
        $monitor = Yii::$app->monitor;
        $monitor->Heartbeat();
    }

    /**
     * Run an incremental sync, slightly overlapping with the time frame of the previous sync.
     */
    public function actionIncremental()
    {
        $synchronizer = $this->getSynchronizer();
        $synchronizer->syncUsersChangedSince(strtotime('-11 minutes'));

        $synchronizer = $this->getSynchronizer();
        $synchronizer->syncAllNotifyException();

        /* @var $monitor Monitor */
        $monitor = Yii::$app->monitor;
        $monitor->Heartbeat();
    }

    protected function getSynchronizer()
    {
        /* @var $idStore IdStoreInterface */
        $idStore = Yii::$app->idStore;

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
