<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $position
 * @property string $title
 * @property int $cssicon
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 */
class ConsultationSettingsTag extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationSettingsTag';
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
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['id' => 'motionId'])->viaTable('motionTag', ['tagId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    public function getCSSIconClass(): string
    {
        switch ($this->cssicon) {
            default:
                return 'glyphicon glyphicon-file';
        }
    }

    /**
     * @param Motion[] $motions
     * @return array
     */
    public static function getMostPopularTags($motions)
    {
        $tags = [];
        foreach ($motions as $motion) {
            foreach ($motion->tags as $tag) {
                if (!isset($tags[$tag->id])) {
                    $tags[$tag->id] = [
                        'id'    => $tag->id,
                        'title' => $tag->title,
                        'num'   => 0,
                    ];
                }
                $tags[$tag->id]['num']++;
            }
        }
        $tags = array_values($tags);
        usort($tags, function ($tag1, $tag2) {
            if ($tag1['num'] > $tag2['num']) {
                return -1;
            }
            if ($tag1['num'] < $tag2['num']) {
                return 1;
            }
            return 0;
        });
        return $tags;
    }
}
