<?php

namespace app\models\db;

use app\components\diff\AmendmentRewriter;
use app\components\diff\Diff;
use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

/**
 * @package app\models\db
 *
 * @property int $amendmentId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $cache
 * @property string $metadata
 *
 * @property AmendmentSection
 */
class AmendmentSection extends IMotionSection
{
    use CacheTrait;

    /** @var null|MotionSection */
    private $originalMotionSection = null;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'amendmentSection';
    }

    /**
     * @return ConsultationSettingsMotionSection|null
     */
    public function getSettings()
    {
        $section = $this->getOriginalMotionSection();
        if ($section) {
            return $section->getSettings();
        } else {
            /** @var ConsultationSettingsMotionSection $section */
            return ConsultationSettingsMotionSection::findOne($this->sectionId);
        }
    }

    /**
     * @return Amendment|null
     */
    public function getAmendment()
    {
        return $this->getCachedConsultation()->getAmendment($this->amendmentId);
    }

    /**
     * @return Motion|null
     */
    public function getMotion()
    {
        if ($this->originalMotionSection) {
            return $this->originalMotionSection->getMotion();
        }
        if ($this->amendmentId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getMotion($this->getAmendment()->motionId);
    }

    /**
     * @return Consultation|null
     */
    public function getCachedConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current) {
            $amend = $current->getAmendment($this->amendmentId);
            if ($amend) {
                return $current;
            }
        }
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($this->amendmentId);
        if ($amendment) {
            return $amendment->getMyConsultation();
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'sectionId'], 'required'],
            [['amendmentId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    /**
     * @return \string[]
     * @throws Internal
     */
    public function getTextParagraphs()
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }
        return HTMLTools::sectionSimpleHTML($this->data);
    }

    /**
     * @return MotionSection|null
     */
    public function getOriginalMotionSection()
    {
        if ($this->originalMotionSection === null) {
            $motion = $this->getMotion();
            if ($motion) {
                foreach ($this->getMotion()->getActiveSections() as $section) {
                    if ($section->sectionId == $this->sectionId) {
                        $this->originalMotionSection = $section;
                    }
                }
            } else {
                return null;
            }
        }
        return $this->originalMotionSection;
    }

    /**
     * @param MotionSection $motionSection
     */
    public function setOriginalMotionSection(MotionSection $motionSection)
    {
        $this->originalMotionSection = $motionSection;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        $cached = $this->getCacheItem('getFirstLineNumber');
        if ($cached !== null) {
            return $cached;
        }

        $first = $this->getMotion()->getFirstLineNumber();
        foreach ($this->getAmendment()->getSortedSections() as $section) {
            /** @var AmendmentSection $section */
            if ($section->sectionId == $this->sectionId) {
                $this->setCacheItem('getFirstLineNumber', $first);
                return $first;
            }
            if (!$section || !$section->getOriginalMotionSection()) {
                throw new Internal('Did not find myself');
            }
            $first += $section->getOriginalMotionSection()->getNumberOfCountableLines();
        }
        throw new Internal('Did not find myself');
    }

    /**
     * @param string[] $origParagraphs
     * @param bool $splitListItems
     * @return MotionSectionParagraphAmendment[]
     */
    public function diffToOrigParagraphs($origParagraphs, $splitListItems = true)
    {
        $cached = $this->getCacheItem('diffToOrigParagraphs');
        if ($cached !== null) {
            return $cached;
        }

        $firstLine = $this->getFirstLineNumber();

        $amParagraphs = [];
        $newSections  = HTMLTools::sectionSimpleHTML($this->data, $splitListItems);
        $diff         = new Diff();
        $diffParas    = $diff->compareHtmlParagraphs($origParagraphs, $newSections, DiffRenderer::FORMATTING_CLASSES);

        foreach ($diffParas as $paraNo => $diffPara) {
            $firstDiffPos = DiffRenderer::paragraphContainsDiff($diffPara);
            if ($firstDiffPos !== false) {
                $unchanged             = mb_substr($diffPara, 0, $firstDiffPos);
                $firstDiffLine         = $firstLine + mb_substr_count($unchanged, '###LINENUMBER###') - 1;
                $amSec                 = new MotionSectionParagraphAmendment(
                    $this->amendmentId,
                    $this->sectionId,
                    $paraNo,
                    $diffPara,
                    $firstDiffLine
                );
                $amParagraphs[$paraNo] = $amSec;
            }
            $firstLine += mb_substr_count($diffPara, '###LINENUMBER###');
        }

        $this->setCacheItem('diffToOrigParagraphs', $amParagraphs);
        return $amParagraphs;
    }

    /**
     * @param string $newMotionHtml
     * @param string[] $overrides
     * @return bool
     * @throws Internal
     */
    public function canRewrite($newMotionHtml, $overrides = [])
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Rewriting is only possible for simple text');
        }
        $oldMotionHtml = $this->getOriginalMotionSection()->data;
        return AmendmentRewriter::canRewrite($oldMotionHtml, $newMotionHtml, $this->data, $overrides);
    }

    /**
     * @param string $newMotionHtml
     * @param bool $asDiff
     * @return \string[]
     * @throws Internal
     */
    public function getRewriteCollissions($newMotionHtml, $asDiff = false)
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Rewriting is only possible for simple text');
        }
        $oldMotionHtml = $this->getOriginalMotionSection()->data;
        return AmendmentRewriter::getCollidingParagraphs($oldMotionHtml, $newMotionHtml, $this->data, $asDiff);
    }

    /**
     * @param string $newMotionHtml
     * @param string[] $overrides
     */
    public function performRewrite($newMotionHtml, $overrides = [])
    {
        $oldMotionHtml = $this->getOriginalMotionSection()->data;
        $this->data    = AmendmentRewriter::performRewrite($oldMotionHtml, $newMotionHtml, $this->data, $overrides);
    }
}
