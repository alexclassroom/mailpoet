<?php
/**
 * This file is part of the MailPoet Email Editor package.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\PersonalizationTags;

/**
 * The class represents a personalization tag that contains  all necessary information
 * for replacing the tag with its value and displaying it in the UI.
 */
class Personalization_Tag {
	/**
	 * The name of the tag displayed in the UI.
	 *
	 * @var string
	 */
	private string $name;
	/**
	 * The token which is used in HTML_Tag_Processor to replace the tag with its value.
	 *
	 * @var string
	 */
	private string $token;
	/**
	 * The category of the personalization tag for categorization on the UI.
	 *
	 * @var string
	 */
	private string $category;
	/**
	 * The callback function which returns the value of the personalization tag.
	 *
	 * @var callable
	 */
	private $callback;
	/**
	 * The attributes which are used in the Personalization Tag UI.
	 *
	 * @var array
	 */
	private array $attributes;

	/**
	 * Personalization_Tag constructor.
	 *
	 * Example usage:
	 *   $tag = new Personalization_Tag(
	 *     'First Name',
	 *     'user:first_name',
	 *     'User',
	 *      function( $context, $args ) {
	 *        return $context['user_firstname'] ?? 'user';
	 *      },
	 *      array( default => 'user' )
	 *   );
	 *
	 * @param string   $name The name of the tag displayed in the UI.
	 * @param string   $token The token used in HTML_Tag_Processor to replace the tag with its value.
	 * @param string   $category The category of the personalization tag for categorization on the UI.
	 * @param callable $callback The callback function which returns the value of the personalization tag.
	 * @param array    $attributes The attributes which are used in the Personalization Tag UI.
	 */
	public function __construct(
		string $name,
		string $token,
		string $category,
		callable $callback,
		array $attributes = array()
	) {
		$this->name       = $name;
		$this->token      = $token;
		$this->category   = $category;
		$this->callback   = $callback;
		$this->attributes = $attributes;
	}

	/**
	 * Returns the name of the personalization tag.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Returns the token of the personalization tag.
	 *
	 * @return string
	 */
	public function get_token(): string {
		return $this->token;
	}

	/**
	 * Returns the category of the personalization tag.
	 *
	 * @return string
	 */
	public function get_category(): string {
		return $this->category;
	}

	/**
	 * Returns the attributes of the personalization tag.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Executes the callback function for the personalization tag.
	 *
	 * @param mixed $context The context for the personalization tag.
	 * @param array $args The additional arguments for the callback.
	 * @return string The value of the personalization tag.
	 */
	public function execute_callback( $context, $args = array() ): string {
		return call_user_func( $this->callback, ...array_merge( array( $context ), array( $args ) ) );
	}
}