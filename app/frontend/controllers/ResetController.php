<?php

namespace frontend\controllers;

use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class ResetController extends BaseRestController
{
    /**
     * POST /reset
     * Initiate a password reset for the given username, which can contain the actual username or the primary email
     * address (the 'email' property). It finds (or creates) the user, finds (or creates) a reset record, and sends
     * the verification email.
     *
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        $username = trim((string) Yii::$app->request->getBodyParam('username', ''));
        if ($username === '') {
            throw new BadRequestHttpException('username is required.', 1543338160);
        }

        $includeManager = trim((string) Yii::$app->request->getBodyParam('include_manager', 'no'));

        // Support both username and email lookups
        if (str_contains($username, '@')) {
            $user = User::findByEmail($username);
        } else {
            $user = User::findByUsername($username);
        }

        // To prevent user enumeration, always return 204
        Yii::$app->response->statusCode = 204;

        if ($user === null) {
            Yii::info("password reset attempted, but username '$username' not found");
            return null;
        }

        Reset::create($user, $includeManager === 'yes');
    }

    /**
     * PUT /reset/{uuid}/verify
     * Verifies a reset. If found, its expiration time will be set to the current time to prevent it from being used
     * a second time.
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionVerify(string $uuid): array
    {
        /** @var Reset $reset */
        $reset = Reset::findOne(['uuid' => $uuid]);
        if ($reset === null || $reset->isExpired()) {
            throw new NotFoundHttpException();
        }

        $reset->verify();

        return [
            'employee_id' => $reset->user->employee_id,
        ];
    }
}
