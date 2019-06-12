<?php

namespace App\Support;

use Closure;
use Storage;
use League\Flysystem\Adapter\Local;

class CsvWriter
{
	/**
	 * The local path to the file.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The name of the storage disk.
	 *
	 * @var string|null
	 */
	protected $disk;

	/**
	 * The file stream resource.
	 *
	 * @var resource
	 */
	protected $resource;

	/**
	 * The field delimiter.
	 *
	 * @var string
	 */
	protected $delimiter = ',';

	/**
	 * The field enclosure.
	 *
	 * @var string
	 */
	protected $enclosure = '"';

	/**
	 * The escape character for the field enclosure.
	 *
	 * @var string
	 */
	protected $escape_char = "\\";

	/**
	 * Creates a new csv writer.
	 *
	 * @param  string       $path
	 * @param  string|null  $disk
	 *
	 * @return $this
	 */
	public function __construct($path, $disk = null)
	{
		$this->path = ltrim($path, '\\/');
		$this->disk = $disk;

		if(!$this->isLocal()) {
			throw new InvalidArgumentException('The disk implementation must use a local disk driver.');
		}
	}

	/**
	 * Opens the file for writing.
	 *
	 * @param  string  $mode
	 *
	 * @return boolean
	 */
	public function open($mode = 'w')
	{
		// Open the file
		$handle = fopen($this->getAbsoluteFilePath(), $mode);

		// Remember the resource
		$this->resource = $handle !== false ? $handle : null;

		// Return whether or not the opening succeeded
		return $handle !== false;
	}

	/**
	 * Opens the file if it isn't already open.
	 *
	 * @param  string  $mode
	 *
	 * @return boolean
	 */
	public function openIfNotOpen($mode = 'w')
	{
		// If a resource already exists, return success
		if(!is_null($this->resource)) {
			return true;
		}

		// Open the file
		return $this->open($mode);
	}

	/**
	 * Adds the specified fields to the file.
	 *
	 * @param  array  $fields
	 *
	 * @return integer|boolean
	 */
	public function put($fields)
	{
		return fputcsv($this->resource, $fields, $this->delimiter, $this->enclosure, $this->escape_char);
	}

	/**
	 * Maps the specified list of rows to fields, and writes them to the file.
	 *
	 * @param  array     $rows
	 * @param  \Closure  $callback
	 * @param  boolean   $header
	 * @param  boolean   $close
	 *
	 * @return integer|boolean
	 */
	public function map($rows, Closure $callback, $header = false, $close = false)
	{
		// Iterate through the rows
		$rows = array_map($callback, $rows);

		// Write the rows to the file
		return $this->rows($rows, $header, $close);
	}

	/**
	 * Writes the specified rows to the file, optionally including a key-based header.
	 *
	 * @param  array    $rows
	 * @param  boolean  $header
	 * @param  boolean  $close
	 *
	 * @return integer|boolean
	 */
	public function rows($rows, $header = false, $close = false)
	{
		// Open the file if it hasn't already been opened
		$this->openIfNotOpen();

		// Check if the header option was enabled
		if($header && count($rows) > 0) {

			// Write the keys of the first row to the file
			if($this->put(array_keys(head($rows))) === false) {
				return false;
			}

		}

		// Iterate through the rows
		foreach($rows as $row) {

			// Write each row to the file
			if(($response = $this->put($row)) === false) {
				return false;
			}

		}

		// Close the file if prompted
		if($close) {
			$this->close();
		}

		// Return the most recent response
		return $response ?? false;
	}

	/**
	 * Closes the current file.
	 *
	 * @return boolean
	 */
	public function close()
	{
		// Make sure a resource exists
		if(is_null($this->resource)) {
			return false;
		}

		// Close the file
		$success = fclose($this->resource);

		// Forget the resource
		$this->resource = $success ? null : $this->resource;

		// Return whether or not the closing succeeded
		return $success;
	}

	/**
	 * Returns the absolute file path.
	 *
	 * @return string
	 */
	public function getAbsoluteFilePath()
	{
		return rtrim($this->getAdapter()->getPathPrefix(), '\\/') . DIRECTORY_SEPARATOR . $this->path;
	}

	/**
	 * Returns the storage disk implementation.
	 *
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	public function getDisk()
	{
		return Storage::disk($this->disk);
	}

	/**
	 * Returns the storage disk adapter implementation.
	 *
	 * @return mixed
	 */
	public function getAdapter()
	{
		return $this->getDisk()->getAdapter();
	}

	/**
	 * Returns whether or not the storage disk is on this machine.
	 *
	 * @return boolean
	 */
	public function isLocal()
	{
		return $this->getAdapter() instanceof Local;
	}
}