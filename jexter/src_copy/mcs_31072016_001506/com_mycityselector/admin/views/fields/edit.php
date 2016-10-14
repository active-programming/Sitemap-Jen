<?php
/**
 * MyCitySelector
 * @author Konstantin Kutsevalov
 * @version 2.0.0
 *
 * @formatter:off
 *
 * @var $this \adamasantares\jxmvc\JxView
 * @var $sidebar string
 * @var $data array
 * @var $model CountryModel
 */
defined('_JEXEC') or die(header('HTTP/1.0 403 Forbidden') . 'Restricted access');

use adamasantares\jxforms\JxField;

//$options['placeholder_text_multiple'] = JText::_('JGLOBAL_SELECT_SOME_OPTIONS');
//$options['placeholder_text_single'] = JText::_('JGLOBAL_SELECT_AN_OPTION');
//$options['no_results_text'] = JText::_('JGLOBAL_SELECT_NO_RESULTS_MATCH');
//$options_str = json_encode($options);

$this->addJsDeclaration('
        // todo init select2
');

?>
<div id="j-sidebar-container" class="span2">
    <?= $sidebar ?>
</div>
<div id="j-main-container" class="span10">
    <h3><?= JText::_('COM_mycityselector_field') ?></h3>
    <div id="system-message-container"><?= $this->getMessage() ?></div>
    <form action="index.php?option=<?= $this->getComponentName() ?>" method="post" name="adminForm" id="adminForm">

        <?= JxField::text($model->getFieldName('name'), JText::_('COM_MYCITYSELECTOR_FORM_TITLE_NAME'), $data['name'], [
            'id' => 'com_mycityselector_name',
            'required' => true,
            'inline' => true,
            'size' => 40,
        ]) ?>

        <?= JxField::radio($model->getFieldName('published'), JText::_('COM_MYCITYSELECTOR_FORM_TITLE_STATUS'), $data['published'], [
            'options' => [
                '0' => JText::_('COM_MYCITYSELECTOR_ITEM_UNPUBLISHED'),
                '1' => JText::_('COM_MYCITYSELECTOR_ITEM_PUBLISHED'),
            ],
            'inline' => true
        ]) ?>

        <div class="field-values">
            <?php
            // default value first
            foreach ($data['fieldValues'] as $fieldValue) {
                if ($fieldValue['default'] == 1) {
                    ?><div class="field-value row-fluid">
                        <div class="span3">
                            <index type="hidden" name="<?= $model->getFieldName('id') ?>" value="<?= $fieldValue['value'] ?>"/>
                            <label><?= JText::_('COM_MYCITYSELECTOR_DEFAULTVALUE') ?></label>
                        </div>
                        <div class="span9">

                            <?php var_dump($model->getFieldName('value')) ?>

                            <?= JxField::editor($model->getFieldName('value'), '', $fieldValue['value'], [
                                'width' => '99%', 'height' => '100px'
                            ]) ?>
                        </div>
                    </div><?php
                }
            }
            // other values
            foreach ($data['fieldValues'] as $fieldValue) {
                if ($fieldValue['default'] != 1) {
                    ?><div class="field-value row-fluid">
                    <div class="span3">
                        <label>
                            <index type="hidden" name="<?= $model->getFieldName('id') ?>" value="<?= $data['value'] ?>"/>
                            <?= JText::_('COM_MYCITYSELECTOR_CITIES_TITLE') ?>
                            <?= JxField::text($model->getFieldName('name'), '') ?>
                        </label>
                    </div>
                    <div class="span9">
                        <?= JxField::editor($model->getFieldName('default_value'), '', $data['default_value'], [
                            'width' => '99%', 'height' => '150px'
                        ]) ?>
                    </div>
                    </div><?php
                }
            }
            ?>
            <button id="addform" onclick="return false;"><?= JText::_('COM_MYCITYSELECTOR_ADDFORM'); ?></button>
        </div>

        <input type="hidden" name="<?= $model->getFieldName('id') ?>" value="<?= $data['id'] ?>"/>

        <?= $this->formControllerName() ?>
        <?= $this->formOption() ?>
        <?= $this->formTask() ?>
        <?= $this->formToken() ?>
    </form>

</div>

