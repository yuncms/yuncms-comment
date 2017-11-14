<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace yuncms\comment\frontend\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use yuncms\comment\frontend\models\CommentForm;

/**
 * Class Comment
 * @package yuncms\comment\widgets
 */
class Comment extends Widget
{
    /**
     * @var string 内容类型
     */
    public $source_type;

    /**
     * @var int 关系ID
     */
    public $source_id;

    public $hide_cancel = false;

    /** @var bool */
    public $validate = true;

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
        if (empty ($this->source_type)) {
            throw new InvalidConfigException ('The "source_type" property must be set.');
        }
        if (empty ($this->source_id)) {
            throw new InvalidConfigException ('The "source_id" property must be set.');
        }
        Url::remember('', 'actions-redirect');
    }

    /**
     * 注册翻译
     */
    public function registerTranslations()
    {
        if (!isset(Yii::$app->i18n->translations['widgets/comment/*'])) {
            Yii::$app->i18n->translations['widgets/comment/*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => __DIR__ . '/messages',
                'fileMap' => [
                    'widgets/comment/comment' => 'comment.php',
                ],
            ];
        }
    }

    /**
     * 显示语言包
     * @param string $category
     * @param string $message
     * @param array $params
     * @param null $language
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('widgets/comment/' . $category, $message, $params, $language);
    }

    /** @inheritdoc */
    public function run()
    {
        $model = new CommentForm([
            'source_type' => $this->source_type,
            'source_id' => $this->source_id
        ]);
        return $this->render('comment', [
            'model' => $model,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'hide_cancel' => $this->hide_cancel,
        ]);
    }
}