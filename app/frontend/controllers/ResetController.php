<?php

namespace frontend\controllers;

use common\models\Reset;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

class ResetController extends BaseRestController
{
    /**
     * GET /reset/{uid}
     * Return a reset object by its uid.
     *
     * @param string $uid
     * @return Reset
     * @throws NotFoundHttpException
     */
    public function actionView(string $uid): Reset
    {
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException('Reset not found.', 1462989590);
        }

        return $reset;
    }

    /**
     * POST /reset
     * Initiate a password reset for the given username.
     * Finds (or creates) the user, finds (or creates) a reset record, and sends
     * the verification email. Returns the Reset object (with uid + masked methods).
     *
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionCreate(): Reset
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

        if ($user === null) {
            throw new NotFoundHttpException('User not found.', 1543338164);
        }

        $reset = Reset::findOrCreate($user);

        if ($reset->isExpired()) {
            $reset->restart();
        } else {
            $reset->send();
        }

        return $reset;
    }

    /**
     * PUT /reset/{uid}
     * Update the reset type/method and resend the verification email.
     *
     * @param string $uid
     * @return Reset
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionUpdate(string $uid): Reset
    {
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException('Reset not found.', 1462989591);
        }

        $type     = Yii::$app->request->getBodyParam('type');
        $methodId = Yii::$app->request->getBodyParam('id');

        if (empty($type)) {
            throw new BadRequestHttpException('type is required.', 1462989664);
        }

        $reset->setType($type, $methodId);
        $reset->send();

        return $reset;
    }

    /**
     * PUT /reset/{uid}/resend
     * Resend the verification email for an existing reset.
     *
     * @param string $uid
     * @return Reset
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws \Exception
     */
    public function actionResend(string $uid): Reset
    {
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException('Reset not found.', 1462989592);
        }

        $reset->send();

        return $reset;
    }

    /**
     * PUT /reset/{uid}/validate
     * Validate the reset code submitted by the user.
     * Returns 200 on success, 400 for wrong code, 410 for expired.
     *
     * @param string $uid
     * @return null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws HttpException
     * @throws TooManyRequestsHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function actionValidate(string $uid)
    {
        $reset = Reset::findOne(['uid' => $uid]);
        if ($reset === null) {
            throw new NotFoundHttpException('Reset not found.', 1462989593);
        }

        $code = trim((string) Yii::$app->request->getBodyParam('code', ''));
        if ($code === '') {
            throw new BadRequestHttpException('code is required.', 1462989866);
        }

        if ($reset->isUserProvidedCodeCorrect($code)) {
            if ($reset->isExpired()) {
                $reset->restart();
                throw new HttpException(410, 'Reset code has expired.');
            }

            Yii::warning([
                'action'    => 'validate reset',
                'reset_id'  => $reset->id,
                'user'      => $reset->user->getEmailAddress(),
                'status'    => 'success',
            ]);

            if (!$reset->delete()) {
                Yii::warning([
                    'action'   => 'delete reset after validation',
                    'reset_id' => $reset->id,
                    'status'   => 'error',
                    'error'    => $reset->getFirstErrors(),
                ]);
            }

            return null;
        }

        Yii::warning([
            'action'   => 'validate reset',
            'reset_id' => $reset->id,
            'user'     => $reset->user->getEmailAddress(),
            'status'   => 'error',
            'error'    => 'Incorrect reset code.',
        ]);

        throw new BadRequestHttpException('Incorrect reset code.', 1462991098);
    }
}
