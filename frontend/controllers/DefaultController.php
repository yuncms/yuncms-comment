<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\comment\frontend\controllers;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yuncms\comment\models\Comment;
use yuncms\comment\frontend\models\CommentForm;


/**
 * Class DefaultController
 * @package yuncms\comment\controllers
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['list', 'store'],
                        'roles' => ['@', '?']
                    ],
                    [
                        'allow' => true,
                        'actions' => ['store'],
                        'roles' => ['@']
                    ],
                ],
            ],
        ];
    }

    /**
     * ajax加载评论
     * @param string $sourceType
     * @param int $sourceId
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionList($sourceType, $sourceId)
    {
        $source = null;
        if ($sourceType == 'question' && Yii::$app->hasModule('question')) {
            $source = \yuncms\question\models\Question::findOne($sourceId);
        } else if ($sourceType === 'answer' && Yii::$app->hasModule('question')) {
            $source = \yuncms\question\models\QuestionAnswer::findOne($sourceId);
        } else if ($sourceType == 'article' && Yii::$app->hasModule('article')) {
            $source = \yuncms\article\models\Article::findOne($sourceId);
        } else if ($sourceType == 'live' && Yii::$app->hasModule('live')) {
            $source = \yuncms\live\models\Stream::findOne($sourceId);
        } else if ($sourceType == 'code' && Yii::$app->hasModule('code')) {
            $source = \yuncms\live\models\Stream::findOne($sourceId);
        }//etc..

        if (!$source) {
            throw new NotFoundHttpException ();
        }

        $query = Comment::find()->where([
            'model_id' => $sourceId,
            'model' => get_class($source),
        ])->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        return $this->renderPartial('list', ['dataProvider' => $dataProvider]);
    }

    /**
     * 评论保存
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionStore()
    {
        $model_class = Yii::$app->request->post('model_class');
        $modelId = Yii::$app->request->post('model_id');
        /** @var null|\yii\db\ActiveRecord $source */
        $source = null;
        if ($model_class == 'question' && Yii::$app->hasModule('question')) {
            $source = \yuncms\question\models\Question::findOne($modelId);
            $notifySubject = $source->title;
            $notifyType = 'comment_question';
            $notify_refer_type = 'question';
            $notify_refer_id = 0;
        } else if ($model_class === 'answer' && Yii::$app->hasModule('question')) {
            $source = \yuncms\question\models\QuestionAnswer::findOne($modelId);
            $notifySubject = $source->question->title;
            $notifyType = 'comment_answer';
            $notify_refer_type = 'answer';
            $notify_refer_id = $source->question_id;
        } else if ($model_class == 'article' && Yii::$app->hasModule('article')) {
            $source = \yuncms\article\models\Article::findOne($modelId);
            $notifySubject = $source->title;
            $notifyType = 'comment_article';
            $notify_refer_type = 'article';
            $notify_refer_id = $source->id;
        } else if ($model_class == 'live' && Yii::$app->hasModule('live')) {
            $source = \yuncms\live\models\Stream::findOne($modelId);
            $notifySubject = $source->title;
            $notifyType = 'comment_live';
            $notify_refer_type = 'live';
            $notify_refer_id = $source->id;
        } else if ($model_class == 'code' && Yii::$app->hasModule('code')) {
            $source = \yuncms\code\models\Code::findOne($modelId);
            $notifySubject = $source->title;
            $notifyType = 'comment_code';
            $notify_refer_type = 'code';
            $notify_refer_id = $source->id;
        }//etc..

        if (!$source) {
            throw new NotFoundHttpException ();
        }

        $data = [
            'user_id' => Yii::$app->user->id,
            'model_id' => $source->id,
            'model_class' => get_class($source),
            'content' => Yii::$app->request->post('content'),
            'to_user_id' => Yii::$app->request->post('to_user_id'),
        ];
        $model = new Comment($data);
        if ($model->save()) {
            /*问题、回答、文章评论数+1*/
            $source->updateCounters(['comments' => 1]);
            if ($model->to_user_id > 0) {
                notify(Yii::$app->user->id, $model->to_user_id, 'reply_comment', $notifySubject, $source->id, $model->content, $notify_refer_type, $notify_refer_id);
            } else {
                notify(Yii::$app->user->id, $source->user_id, $notifyType, $notifySubject, $source->id, $model->content, $notify_refer_type, $notify_refer_id);
            }
            return $this->renderPartial('detail', ['model' => $model, 'model_class' => $model_class, 'model_id' => $source->id]);
        } else {
            throw new ForbiddenHttpException($model->getFirstError());
        }
    }
}