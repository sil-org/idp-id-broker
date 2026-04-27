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
     * Initiate a password reset for the given username.
     * Finds (or creates) the user, finds (or creates) a reset record, and sends
     * the verification email. Returns the Reset object (with uid + masked methods).
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

        // Support both username and email-style lookups
        if (str_contains($username, '@')) {
            $user = User::findByEmail($username);
        } else {
            $user = User::findByUsername($username);
        }

        // To prevent user enumeration, always return 204
        Yii::$app->response->statusCode = 204;

        if ($user === null) {
            return null;
        }

        Reset::create($user);
    }
}
