<?php

namespace app\models\db;

use app\components\UrlHelper;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $siteId
 * @property string $category
 * @property string $textId
 * @property string $title
 * @property string $breadcrumb
 * @property string $text
 * @property string $editDate
 *
 * @property Consultation $consultation
 * @property Site $site
 */
class ConsultationText extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationText';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['category', 'textId'], 'required'],
            [['category', 'textId', 'text', 'breadcrumb', 'title'], 'safe'],
        ];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $params = ['pages/show-page', 'pageSlug' => $this->textId];
        if ($this->consultationId) {
            $params['consultationPath'] = $this->consultation->urlPath;
        }
        return UrlHelper::createUrl($params);
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        $saveParams = ['pages/save-page', 'pageSlug' => $this->textId];
        if ($this->consultation) {
            $saveParams['consultationPath'] = $this->consultation->urlPath;
        }
        if ($this->id) {
            $saveParams['pageId'] = $this->id;
        }
        return UrlHelper::createUrl($saveParams);
    }

    /**
     * @return string
     */
    public function getUploadUrl()
    {
        $saveParams = ['pages/upload', 'consultationPath' => $this->consultation->urlPath];
        return UrlHelper::createUrl($saveParams);
    }

    /**
     * @return string
     */
    public function getImageBrowseUrl()
    {
        return UrlHelper::createUrl(['pages/browse-images']);
    }

    /**
     * @return string[]
     */
    public static function getDefaultPages()
    {
        return [
            'maintenance' => \Yii::t('pages', 'content_maint_title'),
            'help'        => \Yii::t('pages', 'content_help_title'),
            'legal'       => \Yii::t('pages', 'content_imprint_title'),
            'privacy'     => \Yii::t('pages', 'content_privacy_title'),
            'welcome'     => \Yii::t('pages', 'content_welcome'),
            'login'       => \Yii::t('pages', 'content_login'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getSitewidePages()
    {
        return ['legal', 'privacy', 'login'];
    }

    /**
     * Pages that have a fallback for the whole system. Only relevant in multi-site-setups.
     *
     * @return string[]
     */
    public static function getSystemwidePages()
    {
        return ['legal', 'privacy'];
    }

    /**
     * @param $pageKey
     * @return ConsultationText
     */
    public static function getDefaultPage($pageKey)
    {
        $data           = new ConsultationText();
        $data->textId   = $pageKey;
        $data->category = 'pagedata';
        switch ($pageKey) {
            case 'maintenance':
                $data->title      = \Yii::t('pages', 'content_maint_title');
                $data->breadcrumb = \Yii::t('pages', 'content_maint_bread');
                $data->text       = \Yii::t('pages', 'content_maint_text');
                break;
            case 'help':
                $data->title      = \Yii::t('pages', 'content_help_title');
                $data->breadcrumb = \Yii::t('pages', 'content_help_bread');
                $data->text       = \Yii::t('pages', 'content_help_place');
                break;
            case 'legal':
                $data->title      = \Yii::t('pages', 'content_imprint_title');
                $data->breadcrumb = \Yii::t('pages', 'content_imprint_bread');
                $data->text       = '<p>' . \Yii::t('pages', 'content_imprint_title') . '</p>';
                break;
            case 'privacy':
                $data->title      = \Yii::t('pages', 'content_privacy_title');
                $data->breadcrumb = \Yii::t('pages', 'content_privacy_bread');
                $data->text       = '';
                break;
            case 'welcome':
                $data->title      = \Yii::t('pages', 'content_welcome');
                $data->breadcrumb = \Yii::t('pages', 'content_welcome');
                $data->text       = \Yii::t('pages', 'content_welcome_text');
                break;
            case 'login':
                $data->title      = \Yii::t('pages', 'content_login');
                $data->breadcrumb = \Yii::t('pages', 'content_login');
                $data->text       = '';
                break;
        }
        return $data;
    }

    /**
     * @param Site|null $site
     * @param Consultation|null $consultation
     * @param string $pageKey
     * @return ConsultationText
     */
    public static function getPageData($site, $consultation, $pageKey)
    {
        $foundText = null;
        if (!in_array($pageKey, static::getSitewidePages())) {
            foreach ($consultation->texts as $text) {
                if ($text->category == 'pagedata' && $text->textId == $pageKey) {
                    $foundText = $text;
                }
            }
        }
        if (!$foundText) {
            $siteId    = ($site ? $site->id : null);
            $foundText = ConsultationText::findOne([
                'siteId'         => $siteId,
                'consultationId' => null,
                'category'       => 'pagedata',
                'textId'         => $pageKey,
            ]);
        }
        if (!$foundText && in_array($pageKey, static::getSystemwidePages())) {
            $template              = ConsultationText::findOne([
                'siteId'   => null,
                'category' => 'pagedata',
                'textId'   => $pageKey,
            ]);
            if (!$template) {
                $template = static::getDefaultPage($pageKey);
            }
            $foundText             = new ConsultationText();
            $foundText->category   = 'pagedata';
            $foundText->textId     = $pageKey;
            $foundText->text       = $template->text;
            $foundText->breadcrumb = $template->breadcrumb;
            $foundText->title      = $template->title;
            if ($site) {
                $foundText->siteId = $site->id;
            }
        }
        $defaultPage = static::getDefaultPage($pageKey);
        if (!$foundText) {
            $foundText = $defaultPage;
            if (!in_array($pageKey, static::getSystemwidePages())) {
                $foundText->siteId = ($site ? $site->id : null);
            }
            if (!in_array($pageKey, static::getSitewidePages())) {
                $foundText->consultationId = ($consultation ? $consultation->id : null);
            }
        } else {
            if (!$foundText->breadcrumb && $defaultPage) {
                $foundText->breadcrumb = $defaultPage->breadcrumb;
            }
            if (!$foundText->title && $defaultPage) {
                $foundText->title = $defaultPage->title;
            }
        }
        return $foundText;
    }

    /**
     * @param Site $site
     * @param Consultation|null $consultation
     * @return ConsultationText[]
     */
    public static function getAllPages($site, $consultation)
    {
        /** @var ConsultationText[] $text */
        $pages = ConsultationText::findAll(['siteId' => $site->id, 'consultationId' => null, 'category' => 'pagedata']);
        if ($consultation) {
            $pages = array_merge(
                $pages,
                ConsultationText::findAll(['consultationId' => $consultation->id, 'category' => 'pagedata'])
            );
        }
        usort($pages, function ($page1, $page2) {
            return strnatcasecmp($page1->title, $page2->title);
        });
        return $pages;
    }
}
