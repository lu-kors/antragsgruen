<?php

namespace app\plugins\member_petitions;

use app\models\db\{Consultation, Motion, Site};
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\models\siteSpecificBehavior\DefaultBehavior;
use app\plugins\ModuleBase;
use yii\base\Event;
use yii\web\Controller;

class Module extends ModuleBase
{
    public function init(): void
    {
        parent::init();

        Event::on(Motion::class, Motion::EVENT_MERGED, [Tools::class, 'onMerged']);
        Event::on(Motion::class, Motion::EVENT_PUBLISHED_FIRST, [Tools::class, 'onPublishedFirst']);
    }

    /**
     * @param Controller $controller
     * @return \yii\web\AssetBundle[]|string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getActiveAssetBundles(Controller $controller)
    {
        return [
            Assets::class
        ];
    }

    protected static function getMotionUrlRoutes(): array
    {
        return [
            'write-petition-response' => 'member_petitions/backend/write-response',
        ];
    }

    /**
     * @param Site $site
     * @return null|DefaultBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return SiteSpecificBehavior::class;
    }

    /**
     * @param Consultation $consultation
     * @return string|ConsultationSettings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsClass(Consultation $consultation)
    {
        return ConsultationSettings::class;
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }
}
