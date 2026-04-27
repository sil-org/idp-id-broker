<?php

namespace frontend\controllers;

use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class ResetController extends BaseRestController
{
    /**
     * Create or retrieve an existing reset record for the given user.
     *
     * POST /reset
     * Body: { "employee_id": "..." }
     *
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionCreate(): Reset
    {
        $employeeId = Yii::$app->request->getBodyParam('employee_id');

        if (empty($employeeId)) {
            throw new BadRequestHttpException(
                'employee_id is required',
                1745712001
            );
        }

        $user = User::findOne(['employee_id' => $employeeId]);

        if ($user === null) {
            throw new NotFoundHttpException(
                'User not found for employee_id ' . var_export($employeeId, true),
                1745712002
            );
        }

        $reset = Reset::findOrCreate($user);

        Yii::info([
            'action' => 'reset/create',
            'status' => 'success',
            'employee_id' => $employeeId,
            'reset_uid' => $reset->uid,
        ], 'application');

        return $reset;
    }
}
