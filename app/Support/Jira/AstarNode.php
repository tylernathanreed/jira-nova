<?php

namespace App\Support\Jira;

use InvalidArgumentException;

class AstarNode
{
	//////////////////
	//* Attributes *//
	//////////////////
	/**
	 * The internal state.
	 *
	 * @var mixed
	 */
	protected $state;

	/**
	 * The state key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * The move cost.
	 *
	 * @var integer|float
	 */
	protected $moveCost;

	/**
	 * The estimated cost.
	 *
	 * @var integer|float
	 */
	protected $estimatedCost;

	/**
	 * The parent node.
	 *
	 * @var static|null
	 */
	public $parent;

	/**
	 * The children nodes.
	 *
	 * @var array
	 */
	public $children = [];

	///////////////////
	//* Constructor *//
	///////////////////
	/**
	 * Creates a new A* instance.
	 *
	 * @param  array  $attributes
	 *
	 * @return static
	 */
	public function __construct($attributes = [])
	{
		foreach($attributes as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/////////////////////////////
	//* Calculated Attributes *//
	/////////////////////////////
	/**
	 * Returns the path cost of this node.
	 *
	 * @return integer|float
	 */
	public function pathCost()
	{
		// Initialize the cost
		$cost = $this->moveCost;

		// Add the move cost of each parent node
		while($parent = $this->parent; !is_null($parent); $parent = $parent->parent) {
			$cost += $parent->moveCost;
		}

		// Return the cost
		return $cost;
	}

	/**
	 * Returns the total cost of this node.
	 *
	 * @return integer|float
	 */
	public function cost()
	{
		return $this->pathCost + $this->estimatedCost;
	}

	/////////////////
	//* Accessors *//
	/////////////////
	/**
	 * Dynamically returns the specified attribute.
	 *
	 * @param  string  $attribute
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __get($attribute)
	{
		// Allow protected read access
		if(property_exists($this, $attribute)) {
			return $this->{$attribute};
		}

		// Check for calculated attributes
		if(in_array($attribute, ['pathCost', 'cost'])) {
			return $this->{$attribute}();
		}

		// Throw an exception
		throw new InvalidArgumentException('Undefined property: ' . static::class . '::$' . $attribute);
	}
}