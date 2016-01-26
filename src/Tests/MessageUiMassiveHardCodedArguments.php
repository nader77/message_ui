<?php

/**
 * @file
 * Definition of Drupal\message_ui\Tests\MessageUiMassiveHardCodedArguments.
 */

namespace Drupal\message_ui\Tests;

use Drupal\message\Tests\MessageTestBase;
use Drupal\message\Entity\Message;

/**
 * Testing the update of the hard coded arguments in massive way.
 *
 * @group Message UI
 */
class MessageUiMassiveHardCodedArguments extends MessageTestBase {

  /**
   * The user object.
   * @var
   */
  public $user;

  /**
   * Modules to enable.
   *
   * @todo: is entity_token required in D8?
   *
   * @var array
   */
  public static $modules = ['message', 'message_ui'];

  public static function getInfo() {
    return array(
      'name' => 'Message UI arguments massive update',
      'description' => 'Testing the removing/updating an hard coded arguments.',
      'group' => 'Message UI',
      // 'dependencies' => array('entity_token'), // // @todo: is this required?
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser();
  }

  /**
   * Test removal of added arguments.
   */
  public function testRemoveAddingArguments() {
    // Create Message Type of 'Dummy Test.
    $this->createMessageType('dummy_message', 'Dummy test', 'This is a dummy message with a dummy message', array('Dummy message'));

    // @todo : validate / fix this config access.
    // Set a queue worker for the update arguments when updating a message type.
    $this->configSet('update_tokens.update_tokens', TRUE, 'message_ui.settings');
    $this->configSet('update_tokens.how_to_act', 'update_when_item', 'message_ui.settings');

    // Create a message.
    $message_type = $this->loadMessageType('dummy_message');
    $message = Message::create(array('type' => $message_type->id()))
      ->setAuthorId($this->user->id());
    $message->setAuthorId($this->user->id());
    $message->save();

    // @todo : check what args are returned in D7.
    $original_arguments = $message->getArguments();

    // @todo : validate / fix this config access.
    // Update message instance when removing a hard coded argument.
    $this->configSet('update_tokens.how_to_act', 'update_when_removed', 'message_ui.settings');

    // Set message text.
    $message_type->setData(array('text' => '[message:user:name].'));
    $message_type->save();

    // Fire the queue worker.
    $queue = \Drupal::queue('message_ui_arguments');
    $item = $queue->claimItem();
    // @todo : check the below calls MessageUiArgumentsWorker::processItem.
    $queue->createItem($item->data);

    // Verify the arguments has changed.
    $message = Message::load($message->id());
    $this->assertTrue($original_arguments != $message->getArguments(), 'The message arguments has changed during the queue worker work.');

    // Creating a new message and her hard coded arguments.
    $message = Message::create('dummy_message');
    $message->setAuthorId($this->user->id());
    $message->save();
    $original_arguments = $message->getArguments();

    // @todo : validate / fix this config access.
    // Process the message instance when adding hard coded arguments.
    $this->configSet('update_tokens.how_to_act', 'update_when_added', 'message_ui.settings');

    $message_type = $this->loadMessageType('dummy_message');
    $message_type->setData(array('text' => '@{message:user:name}.'));
    $message_type->save();

    // Fire the queue worker.
    $queue = \Drupal::queue('message_ui_arguments');
    $item = $queue->claimItem();
    // @todo : check the below calls MessageUiArgumentsWorker::processItem.
    $queue->createItem($item->data);

    // Verify the arguments has changed.
    $message = Message::load($message->id());
    $this->assertTrue($original_arguments == $message->getArguments(), 'The message arguments has changed during the queue worker work.');
  }
}
