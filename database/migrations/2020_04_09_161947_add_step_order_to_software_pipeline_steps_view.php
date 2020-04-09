<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStepOrderToSoftwarePipelineStepsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('drop view if exists vw_software_pipeline_steps');

        DB::statement("
            create view vw_software_pipeline_steps as
                select
                    software_branches.id as branch_id,
                    software_branches.name as branch_name,
                    case
                        when (
                            software_applications.name = 'UAS North Star'
                            and (
                                software_brands.name is null
                                or software_brands.name = 'UAS'
                            )
                            and (
                                software_environment_tiers.name is null
                                or software_environment_tiers.name = software_branch_tiers.name
                            )
                        ) then 1
                        else 0
                    end as is_baseline_branch,
                    software_applications.id as application_id,
                    software_applications.name as application_name,
                    software_branch_tiers.id as branch_tier_id,
                    software_branch_tiers.name as branch_tier_name,
                    software_branch_tiers.pipeline_order as branch_tier_order,
                    software_environments.id as environment_id,
                    software_environments.name as environment_name,
                    software_brands.id as brand_id,
                    software_brands.name as brand_name,
                    case
                        when (
                            software_applications.name = 'UAS North Star'
                            and (
                                software_brands.name is null
                                or software_brands.name = 'UAS'
                            )
                        ) then 1
                        else 0
                    end as is_baseline_brand,
                    software_environment_tiers.id as environment_tier_id,
                    software_environment_tiers.name as environment_tier_name,
                    software_environment_tiers.pipeline_order as environment_tier_order,
                    ifnull(software_environment_tiers.pipeline_order, software_branch_tiers.pipeline_order) as step_order,
                    versions.id as version_id,
                    versions.name as version_name
                from software_branches
                    inner join software_applications
                        on software_applications.id = software_branches.application_id
                            and software_applications.deleted_at is null
                    inner join software_branch_tiers
                        on software_branch_tiers.id = software_branches.branch_tier_id
                            and software_branch_tiers.deleted_at is null
                    inner join versions
                        on versions.id = software_branches.target_version_id
                    left join software_environments
                        on software_environments.branch_id = software_branches.id
                            and software_environments.deleted_at is null
                    left join software_brands
                        on software_brands.id = software_environments.brand_id
                            and software_brands.deleted_at is null
                    left join software_environment_tiers
                        on software_environment_tiers.id = software_environments.environment_tier_id
                            and software_environment_tiers.deleted_at is null
                where software_branches.deleted_at is null
        ");

        require database_path('migrations/2020_03_26_173738_create_software_pipeline_summary_view.php');
        app(CreateSoftwarePipelineSummaryView::class)->up();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        require database_path('migrations/2020_03_26_173738_create_software_pipeline_summary_view.php');
        app(CreateSoftwarePipelineStepsView::class)->up();
    }
}