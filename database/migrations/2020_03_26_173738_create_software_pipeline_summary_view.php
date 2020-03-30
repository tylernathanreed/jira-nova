<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoftwarePipelineSummaryView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('drop view if exists vw_software_pipeline_summary');

        DB::statement("create view vw_software_pipeline_summary as
            select
                case
                    when exists (
                        select *
                        from vw_software_pipeline_steps as alternatives
                        where alternatives.is_baseline_brand = 0
                            and alternatives.version_name != baselines.version_name
                            and (
                                alternatives.environment_tier_name = baselines.environment_tier_name
                                or (
                                    alternatives.environment_tier_name is null
                                    and baselines.environment_tier_name is null
                                )
                            )
                            and (
                                alternatives.branch_tier_name = baselines.branch_tier_name
                                or (
                                    alternatives.branch_tier_name is null
                                    and baselines.branch_tier_name is null
                                )
                            )
                    ) then 1
                    else 0
                end as has_alternative,
                baselines.*
            from vw_software_pipeline_steps as baselines
            where is_baseline_brand = 1

            union all

            select
                null as has_alternative,
                alternatives.*
            from vw_software_pipeline_steps as alternatives
            where is_baseline_brand = 0
                and not exists (
                    select *
                    from vw_software_pipeline_steps as baselines
                    where baselines.is_baseline_brand = 1
                        and baselines.version_name = alternatives.version_name
                        and (
                            baselines.environment_tier_name = alternatives.environment_tier_name
                            or (
                                baselines.environment_tier_name is null
                                and alternatives.environment_tier_name is null
                            )
                        )
                        and (
                            baselines.branch_tier_name = alternatives.branch_tier_name
                            or (
                                baselines.branch_tier_name is null
                                and alternatives.branch_tier_name is null
                            )
                        )
                )
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('drop view if exists vw_software_pipeline_summary');
    }
}
