<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Actions\Action as NovaAction;

// class Action extends NovaAction implements ShouldQueue
class Action extends NovaAction
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Creates a new action instance.
     *
     * @return $this
     */
    public function __construct()
    {
    	$this->canRun(function() {
    		return true;
    	});
    }
}
