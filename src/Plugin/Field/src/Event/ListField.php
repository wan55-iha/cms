<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since	 2.0.0
 * @author	 Christopher Castro <chris@quickapps.es>
 * @link	 http://www.quickappscms.org
 * @license	 http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
namespace Field;

use Cake\Event\Event;
use Field\Utility\TextToolbox;
use Field\Utility\FieldHandler;

/**
 * Text Field Handler.
 *
 * Defines list field types, used to create selection lists.
 */
class ListField extends FieldHandler {

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options Additional array of options
 * @return string HTML representation of this field
 */
	public function entityDisplay(Event $event, $field, $options = []) {
		$View = $event->subject;
		return $View->element('Field.list_field_display', compact('field', 'options'));
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @return string HTML containing from elements
 */
	public function entityEdit(Event $event, $field, $options = []) {
		$View = $event->subject;
		return $View->element('Field.list_field_edit', compact('field', 'options'));
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @return void
 */
	public function entityAfterSave(Event $event, $field, $options) {
		$value = $options['post'];
		$field->set('value', $value);
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @param \Cake\Validation\Validator $validator
 * @return boolean False will halt the save process
 */
	public function entityBeforeValidate(Event $event, $field, $options, $validator) {
		if ($field->metadata->required) {
			$validator
				->allowEmpty(":{$field->name}", false, __d('field', 'Field required.'))
				->add(":{$field->name}", 'validateRequired', [
					'rule' => function ($value, $context) use ($field) {
						return !empty($value);
					},
					'message' => __d('field', 'Field required.'),
				]);
		} else {
			$validator
				->allowEmpty(":{$field->name}", true);
		}

		return true;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @param \Cake\Validation\Validator $validator
 * @return boolean False will halt the save process
 */
	public function entityAfterValidate(Event $event, $field, $options, $validator) {
		return true;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @return boolean False will halt the delete process
 */
	public function entityBeforeDelete(Event $event, $field, $options) {
		return true;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\Field $field Field information
 * @param array $options
 * @return void
 */
	public function entityAfterDelete(Event $event, $field, $options) {
		return;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event
 * @return array
 */
	public function instanceInfo(Event $event) {
		return [
			'name' => __d('field', 'List'),
			'description' => __d('field', 'Defines list field types, used to create selection lists.'),
			'hidden' => false
		];
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return string HTML form elements for the settings page
 */
	public function instanceSettingsForm(Event $event, $instance, $options = []) {
		$View = $event->subject;
		return $View->element('Field.list_field_settings_form', compact('instance', 'options'));
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return array
 */
	public function instanceSettingsDefaults(Event $event, $instance, $options = []) {
		return [
			'type' => 'textarea',
			'text_processing' => 'full',
			'max_len' => '',
			'validation_rule' => '',
			'validation_message' => '',
		];
	}	

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return string HTML form elements for the settings page
 */
	public function instanceViewModeForm(Event $event, $instance, $options = []) {
		$View = $event->subject;
		return $View->element('Field.list_field_view_mode_form', compact('instance', 'options'));
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return array
 */
	public function instanceViewModeDefaults(Event $event, $instance, $options = []) {
		switch ($options['viewMode']) {
			default:
				return [
					'label_visibility' => 'above',
					'hooktags' => true,
					'hidden' => false,
					'formatter' => 'full',
					'trim_length' => '',
				];
		}
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return boolean False will halt the attach process
 */
	public function instanceBeforeAttach(Event $event, $instance, $options = []) {
		return true;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @return void
 */
	public function instanceAfterAttach(Event $event, $instance, $options = []) {
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return boolean False will halt the detach process
 */
	public function instanceBeforeDetach(Event $event, $instance, $options = []) {
		return true;
	}

/**
 * {@inheritdoc}
 *
 * @param \Cake\Event\Event $event The event that was fired
 * @param \Field\Model\Entity\FieldInstance $instance Instance information
 * @param array $options
 * @return void
 */
	public function instanceAfterDetach(Event $event, $instance, $options = []) {
	}

}
