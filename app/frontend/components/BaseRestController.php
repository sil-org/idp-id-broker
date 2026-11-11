<?php

namespace frontend\components;

use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\UnprocessableEntityHttpException;

class BaseRestController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Always respond in JSON (set in the 'response' component config) instead of negotiating on the Accept
        // header. Negotiation runs before routing and authentication, so it would answer clients that don't
        // accept JSON (e.g. crawlers) with 406 Not Acceptable for every URL, rather than 404 or 401.
        unset($behaviors['contentNegotiator']);

        // Use request header-> 'Authorization: Bearer <token>'
        $behaviors['authenticator']['class'] = HttpBearerAuth::class;

        return $behaviors;
    }

    protected function save(ActiveRecord $record)
    {
        if (!$record->save()) {
            throw new UnprocessableEntityHttpException(current($record->getFirstErrors()));
        }
    }
}
