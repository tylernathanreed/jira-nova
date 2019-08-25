<?php

namespace App\Nova\Actions;

use App\Models\User;
use App\Models\Issue;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Exceptions\MissingActionHandlerException;

class SaveSwimlaneChanges extends Action
{
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection     $issues
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $issues)
    {
        // Whenever it comes to ranking, we zipper several lists together.
        // We want to undo this zippering on the backend so that we do
        // not actually update ranks to map the result of zippering.

        // Determine the assignees
        $users = User::whereIn('jira_key', $issues->pluck('assignee')->unique()->all())->get()->keyBy('jira_key');

        // Split the issues apart from their zipper groups
        $groups = $issues->groupBy(function($issue) use ($users) {

            // Determine the assignee's schedule
            $schedule = ($users[$issue['assignee']] ?? new User)->getSchedule();

            // Determine the criteria for the group
            $criteria = [
                'assignee' => $issue['assignee'],
                'type' => $schedule->type,
                'focus' => $schedule->type == Schedule::TYPE_SIMPLE ? 'all' : $issue['focus']
            ];

            // Return the encoded criteria
            return json_encode($criteria);

        });

        // Iterate through each group
        foreach($groups as $key => $group) {

            // Since the "other" group is able to allocate time from either
            // other group, we'll want to rank them in as well. This will
            // mean that we skip it in the loop, and merge it instead.

            // Determine the key criteria
            $criteria = json_decode($key, true);

            // If this group is the "other" group, skip it
            if($criteria['focus'] == Issue::FOCUS_OTHER) {
                continue;
            }

            // Determine the "other" group for the same assignee
            $other = $groups[json_encode(['assignee' => $criteria['assignee'], 'type' => Schedule::TYPE_ADVANCED, 'focus' => Issue::FOCUS_OTHER])] ?? collect();

            // Merge the other group into this one
            $group = $group->merge($other);

            // Order the issues by rank
            $group = $group->sortBy('rank');

            // With each group now separated, we'll want to rank them. This
            // involves figuring out their old and new order, plus a way
            // to move the issues into their new order within jira.

            // Determine the old and new orders
            $oldOrder = $group->sortBy('original.order')->pluck('key')->toArray();
            $newOrder = $group->sortBy('order')->pluck('key')->toArray();

            // Determine the subtasks
            $subtasks = $groups->where('is_subtask', '=', 1)->pluck('parent_key', 'key')->toArray();

            // Perform the ranking operations to sort the old list into the new list
            Issue::updateOrderByRank($oldOrder, $newOrder, $subtasks);

        }

        // Unlike ranking, the attributes of each issue can be updated in
        // bulk. We don't have to worry about zipper groups, so we will
        // just perform a massive update to all issues within jira.

        // Determine the issues with new estimates
        $estimates = $issues->filter(function($issue) {
            return $issue['est'] != $issue['original']['est'];
        })->pluck('est', 'key');

        // Update the estimated completion dates
        Issue::updateEstimates($estimates);

    }

    /**
     * Execute the action for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\ActionRequest  $request
     *
     * @return mixed
     *
     * @throws \Laravel\Nova\Exceptions\MissingActionHandlerExceptionMissingActionHandlerException
     */
    public function handleRequest(ActionRequest $request)
    {
        // Determine the action method
        $method = ActionMethod::determine($this, $request->targetModel());

        // Make sure the method exists
        if(!method_exists($this, $method)) {
            throw MissingActionHandlerException::make($this, $method);
        }

        // Resolve the action fields
        $fields = $request->resolveFields();

        // Determine the resource data
        $resources = collect(json_decode($request->resourceData, true));

        // Handle the request
        $results = [
            $this->handle($fields, $resources)
        ];

        // Return the results
        return $this->handleResult($fields, $results);
    }

    /**
     * Returns the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
