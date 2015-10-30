<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\forms\MotionMergeAmendmentsForm;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var MotionMergeAmendmentsForm $form
 * @var array $amendmentStati
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->addBreadcrumb($motion->motionType->titleSingular, UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));
$layout->loadCKEditor();
$layout->loadFuelux();
$layout->addOnLoadJS('$.Antragsgruen.motionMergeAmendmentsForm();');

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, \Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

echo '<h1>' . Html::encode($motion->getTitleWithPrefix()) . '</h1>';

echo '<div class="motionData">';

if (!$motion->consultation->getSettings()->minimalisticUI) {
    include(__DIR__ . DIRECTORY_SEPARATOR . 'view_motiondata.php');
}

echo $controller->showErrors();

echo '<div id="draftHint" class="hidden alert alert-info" role="alert"
    data-new-motion-id="' . $form->newMotion->id . '" data-orig-motion-id="' . $form->origMotion->id . '">' .
    \Yii::t('amend', 'unsaved_drafts') . '<ul></ul>
</div>';

echo '</div>';


echo Html::beginForm('', 'post', ['class' => 'motionMergeForm fuelux']);


echo '<section class="newMotion">
<h2 class="green">' . \Yii::t('amend', 'merge_new_text') . '</h2>
<div class="content">';

$changesets = [];

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($form->newMotion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}

foreach ($motion->getSortedSections(false) as $section) {
    $type = $section->consultationSetting;
    if ($section->consultationSetting->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        /** @var TextSimple $simpleSection */
        $simpleSection = $section->getSectionType();

        $nameBase = 'sections[' . $type->id . ']';
        $htmlId   = 'sections_' . $type->id;

        echo '<div class="form-group wysiwyg-textarea" id="section_holder_' . $type->id . '" data-fullHtml="0">';
        echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

        echo '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        echo '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
            'title="' . Html::encode($type->title) . '"></textarea>';
        echo '<div class="texteditor boxed" data-track-changed="1" data-track-changed-tooltips="1" ' .
            'id="' . $htmlId . '_wysiwyg" title="' . Html::encode($type->title) . '">';

        if (isset($newSections[$section->sectionId])) {
            echo $newSections[$section->sectionId]->dataRaw;
        } else {
            echo $simpleSection->getMotionTextWithInlineAmendments($changesets);
        }

        echo '</div>';

        echo '<div class="mergeActionHolder" style="margin-top: 5px; margin-bottom: 5px;">';
        echo '<button type="button" class="acceptAllChanges btn btn-small btn-default">' .
            \Yii::t('amend', 'merge_accept_all') . '</button> ';
        echo '<button type="button" class="rejectAllChanges btn btn-small btn-default">' .
            \Yii::t('amend', 'merge_reject_all') . '</button>';
        echo '<button type="button" class="deactivateTracking btn btn-small btn-default" style="float:right;">' .
            \Yii::t('amend', 'merge_track_deactivate') . '</button>';
        echo '</div>';
        echo '<div class="mergeTrackingDisabled hidden">' . \Yii::t('amend', 'merge_track_deactivated') . '</div>';

        echo '</div>';
    } else {
        if (isset($newSections[$section->sectionId])) {
            echo $newSections[$section->sectionId]->getSectionType()->getMotionFormField();
        } else {
            echo $section->getSectionType()->getMotionFormField();
        }
    }
}

echo '</div></section>';

$jsStati = [
    'accepted'          => Amendment::STATUS_ACCEPTED,
    'rejected'          => Amendment::STATUS_REJECTED,
    'modified_accepted' => Amendment::STATUS_MODIFIED_ACCEPTED,
];

echo '<section class="newAmendments" data-stati="' . Html::encode(json_encode($jsStati)) . '">';
\app\views\motion\LayoutHelper::printAmendmentStatusSetter($motion->getVisibleAmendments(), $amendmentStati);
echo '</section>';


echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">
    <span class="glyphicon glyphicon-chevron-right"></span> ' . \Yii::t('amend', 'go_on') . '
</button></div>';

echo Html::endForm();
