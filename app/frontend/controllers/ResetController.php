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
     * Initiate a password reset for the given username, which can contain the actual username or the primary email
     * address (the 'email' property). It finds (or creates) the user, finds (or creates) a reset record, and sends
     * the verification email.
     *
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionCreate()
    {
        $username = trim((string) Yii::$app->request->getBodyParam('username', ''));
        if ($username === '') {
            throw new BadRequestHttpException('username is required.', 1543338160);
        }

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

        Reset::create($user);
    }
}
