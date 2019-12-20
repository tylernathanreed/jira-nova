<?php

namespace App\Jobs\Cache;

use App\Jobs\Job;
use App\Models\Cache;

class CacheJob extends Job
{
    /**
     * The cache model instance.
     *
     * @var \App\Models\Cache
     */
    public $cache;

    /**
     * The cache method name.
     *
     * @var string
     */
    public $method;

    /**
     * Creates a new job instance.
     *
     * @param  \App\Models\Cache  $cache
     * @param  string             $method
     *
     * @return void
     */
    public function __construct(Cache $cache, $method)
    {
        $this->cache = $cache;
        $this->method = $method;
    }

    /**
     * Handles this job.
     *
     * @return void
     */
    public function handle()
    {
        call_user_func([$this->cache, $this->method]);
    }
}