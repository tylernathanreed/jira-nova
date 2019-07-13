<?php

namespace App\Support\Jira;

use Closure;
use RuntimeException;
use SplPriorityQueue;

class Astar
{
	//////////////////
	//* Attributes *//
	//////////////////
	/**
	 * The initial state.
	 *
	 * @var mixed
	 */
	protected $initial;

	/**
	 * The state key resolver.
	 *
	 * @var \Closure|null
	 */
	protected $keyResolver;

	/**
	 * The cost resolver.
	 *
	 * @var \Closure|null
	 */
	protected $costResolver;

	/**
	 * The states that have been visited.
	 *
	 * @var array
	 */
	protected $visited = [];

	/**
	 * The priority queue.
	 *
	 * @var \SplPriorityQueue
	 */
	protected $queue;

	///////////////////
	//* Constructor *//
	///////////////////
	/**
	 * Creates a new A* instance.
	 *
	 * @return static
	 */
	public function __construct()
	{
		$this->queue = new SplPriorityQueue;
	}

	/////////////
	//* Setup *//
	/////////////
	/**
	 * Sets the initial state.
	 *
	 * @param  mixed  $state
	 *
	 * @return $this
	 */
	public function setInitialState($state)
	{
		$this->initial = $this->getStateNode($state);

		return $this;
	}

	/**
	 * Sets the state key resolver.
	 *
	 * @param  \Closure  $callback
	 *
	 * @return $this
	 */
	public function setKeyResolver(Closure $callback)
	{
		$this->keyResolver = $callback;

		return $this;
	}

	/**
	 * Sets the estimated cost resolver.
	 *
	 * @param  \Closure  $callback
	 *
	 * @return $this
	 */
	public function setEstimatedCostResolver(Closure $callback)
	{
		$this->costResolver = $callback;

		return $this;
	}

	/**
	 * Resets the algorithm to run again.
	 *
	 * @return $this
	 */
	public function reset()
	{
		// Clear the visited states
		$this->visited = [];

		// Flush the queue
		$this->queue = new SplPriorityQueue;

		// Allow chaining
		return $this;
	}

	////////////////
	//* Solution *//
	////////////////
	/**
	 * Solves the A* problem and returns the solution path.
	 *
	 * @return array|boolean
	 */
	public function solve()
	{
		// Determine the initial state node
		$initial = $this->initial;

		// Add the initial state node to the queue
		$this->queue->insert($initial, $initial->cost);

		// Process the top of the queue until the goal state is reached (or we're out of moves)
		while(!$this->queue->isEmpty()) {

			// Determine the next node
			$next = $this->queue->extract();

			// Check if the node is the goal state
			if($next->estimatedCost == 0) {

				// Assign the goal state
				$goal = $next;

				// Break the loop
				break;

			}

			// Check if the state has been visited
			if(!is_null($previous = ($this->visited[$next->key] ?? null))) {

				// If the cost of this state is cheaper than the previous state, replace it
				if($next->cost < $previous->cost) {

					// Replace the state with this one
					$this->visited[$next->key] = $next;

				}

			}

		}

		// If we don't have a goal state here, then we failed
		if(!isset($goal)) {
			return false;
		}

		dd(compact('goal'));
	}

	/**
	 * Returns the node for the specified state.
	 *
	 * @param  mixed           $state     The specified state.
	 * @param  integer|float   $moveCost  The cost of moving to the specified state from the parent node.
	 * @param  \stdClass|null  $parent    The parent node.
	 *
	 * @return \stdClass
	 */
	public function getStateNode($state, $moveCost = null, $parent = null)
	{
		// Determine the state key
		$key = $this->getStateKey($state);

		// Determine the estimated cost
		$estimatedCost = $this->getStateEstimatedCost($state);

		// Initialize the children
		$children = [];

		// Determine the state node
		$node = new AstarNode(compact('state', 'key', 'parent', 'moveCost', 'estimatedCost'));

		// Add this node to the parent
		if(!is_null($parent)) {
			$parent->children[$key] = $node;
		}

		// Return the state node
		return $node;
	}

	/**
	 * Returns the key for the specified state.
	 *
	 * @param  mixed  $state
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	public function getStateKey($state)
	{
		// Make sure the resolver is defined
		if(is_null($resolver = $this->keyResolver)) {
			throw new RuntimeException("The key resolver has not been set.");
		}

		// Determine the state key
		$key = $resolver($state);

		// Make sure a string was returned
		if(!is_string($key)) {
			throw new RuntimeException("The key resolver must return a string.");
		}

		// Return the key
		return $key;
	}

	/**
	 * Returns the estimated cost from the specified state to the goal state.
	 *
	 * @param  mixed  $state
	 *
	 * @return integer|float
	 *
	 * @throws \RuntimeException
	 */
	public function getStateEstimatedCost($state)
	{
		// Make sure the resolver is defined
		if(is_null($resolver = $this->costResolver)) {
			throw new RuntimeException("The estimated cost resolver has not been set.");
		}

		// Determine the estimated cost
		$cost = $resolver($state);

		// Make sure an integer or float was returned
		if(!is_integer($cost) && !is_float($cost)) {
			throw new RuntimeException("The estimated cost resolver must return an integer or float.");
		}

		// Make sure the cost is greater than zero
		if($cost < 0) {
			throw new RuntimeException("The estimated cost cannot be negative.");
		}

		// Return the cost
		return $cost;
	}
}