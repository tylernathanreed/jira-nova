<?php

namespace App\Support\Astar;

use Closure;
use RuntimeException;

class Algorithm
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
	 * The key resolver.
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
	 * The move resolver.
	 *
	 * @var \Closure|null
	 */
	protected $moveResolver;

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
		$this->queue = new Queue;
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
	 * Sets the move resolver.
	 *
	 * @param  \Closure  $callback
	 *
	 * @return
	 */
	public function setMoveResolver(Closure $callback)
	{
		$this->moveResolver = $callback;

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
		$this->queue = new Queue;

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
		// Reset the algorithm
		$this->reset();

		// Determine the initial state node
		$initial = $this->initial;

		// Add the initial state node to the queue
		$this->queue->insert($initial, $initial->cost);

		// Process the top of the queue until the goal state is reached (or we're out of moves)
		for($i = 0; !$this->queue->isEmpty(); $i++) {

			// Determine the next node
			$next = $this->queue->extract();

			dump($next->key);

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

					// Replace the parents of the previous children
					foreach($previous->children as $child) {
						$child->parent = $next;
					}

				}

				// Skip this state, since we've already visited it
				continue;

			}

			// Otherwise, mark the state as visited
			else {
				$this->visited[$next->key] = $next;
			}

			// Determine the child next state nodes
			$children = $this->getStateNodeMoves($next);

			// Add the children to the queue
			foreach($children as $child) {
				$this->queue->insert($child, $child->cost);
			}

		}

		// If we don't have a goal state here, then we failed
		if(!isset($goal)) {
			return false;
		}

		// Initialize the path
		$path = [];

		// Walk backwards up the parent tree to obtain the reverse path
		for($step = $goal; !is_null($step->parent); $step = $step->parent) {
			$path[] = $step->state;
		}

		// Reverse the path
		return array_reverse($path);
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
		$node = new Node(compact('state', 'key', 'parent', 'moveCost', 'estimatedCost'));

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

	/**
	 * Returns the states accessible from the specified node.
	 *
	 * @param  \App\Support\Jira\AstarNode  $node
	 *
	 * @return array
	 *
	 * @throws \RuntimeException
	 */
	public function getStateNodeMoves($node)
	{
		// Make sure the resolver is defined
		if(is_null($resolver = $this->moveResolver)) {
			throw new RuntimeException("The move resolver has not been set.");
		}

		// Determine the available moves
		$moves = $resolver($node->state, $node->depth);

		// Convert each move into a state node
		return array_map(function($move) use ($node) {

			// Make sure a cost and state were provided
			if(!isset($move['cost']) || !isset($move['state'])) {
				throw new RuntimeException("The available moves must contain a cost and state.");
			}

			// Determine the cost and state
			$cost = $move['cost'];
			$child = $move['state'];

			// Create and return the child state node
			return $this->getStateNode($child, $cost, $node);

		}, $moves);
	}
}