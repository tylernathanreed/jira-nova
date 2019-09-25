<?php

use App\Support\Database\Seeds\CsvSeeder;

class WorkflowStatusTypesTableSeeder extends CsvSeeder
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\WorkflowStatusType::class;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [
        'jira_id'
    ];

    /**
     * The select columns to replace with other selections.
     *
     * @var array
     */
    public $replacements = [
        'status_group_id' => 'workflow_status_groups.system_name as workflow_status_group_system_name'
    ];

    /**
     * The inverse select columns to replace with other selections.
     *
     * @var array
     */
    public $inverseReplacements = [
        'workflow_status_group_system_name' => 'workflow_status_groups.id as status_group_id'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'workflow_status_groups.system_name'
    ];

    /**
     * The columns to ignore for seeding.
     *
     * @var array
     */
    public $ignore = [
        'scope_type',
        'scope_id'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'jira_id' => 'asc'
    ];

    /**
     * The seedable model relations.
     *
     * @var array
     */
    public $joinRelations = [
        'group'
    ];

    /**
     * The inverse seedable model relations.
     *
     * @var array
     */
    public $inverseJoinRelations = [
        'groupFromSeed'
    ];
}
