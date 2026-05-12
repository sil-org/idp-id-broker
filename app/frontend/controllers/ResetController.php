<?php

namespace frontend\controllers;

use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\db\Exception;
use yii\web\BadRequestHttpException;

class ResetController extends BaseRestController
{
    /**
     * POST /reset
     * Initiate a password reset for the given employee_id.
     * Finds (or creates) the user, finds (or creates) a reset record, and sends
     * the verification email.
     *
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionCreate()
    {
        $employeeId = trim((string) Yii::$app->request->getBodyParam('employee_id', ''));
        if ($employeeId === '') {
            throw new BadRequestHttpException('employee_id is required.', 1543338160);
        }

        $user = User::findOne(['employee_id' => $employeeId]);

        // To prevent user enumeration, always return 204
        Yii::$app->response->statusCode = 204;

        if ($user === null) {
            return null;
        }

        Reset::create($user);
    }
}
