<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property string $ipHash
 * @property string $cookieId
 * @property int $motionCommentId
 * @property int $likes
 *
 * @property MotionComment $motionComment
 */
class MotionCommentSupporter extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motionCommentSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionComment()
    {
        return $this->hasOne(MotionComment::className(), ['id' => 'motionCommentId']);
    }
}
