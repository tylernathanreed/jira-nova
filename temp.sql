select
	next_hierarchies.schedule_id,
	next_hierarchies.previous_association_id,
	next_hierarchies.previous_template_id,
	next_hierarchies.previous_start_date,
	next_hierarchies.previous_end_date,
	next_hierarchies.next_start_date,
	schedule_associations.end_date as next_end_date,
	next_hierarchies.next_hierarchy,
	schedule_associations.id as next_association_id,
	schedule_associations.schedule_week_template_id as next_template_id
from (
	select
		next_start_dates.schedule_id,
		next_start_dates.previous_association_id,
		next_start_dates.previous_template_id,
		next_start_dates.previous_start_date,
		next_start_dates.previous_end_date,
		next_start_dates.next_start_date,
		max(schedule_associations.hierarchy) as next_hierarchy
	from (
		select
			schedule_associations.schedule_id,
			schedule_associations.id as previous_association_id,
			schedule_associations.schedule_week_template_id as previous_template_id,
			schedule_associations.start_date as previous_start_date,
			schedule_associations.end_date as previous_end_date,
			min(next_associations.start_date) as next_start_date
		from schedule_associations
			left join schedule_associations as next_associations
				on next_associations.schedule_id = schedule_associations.schedule_id
					and next_associations.hierarchy > schedule_associations.hierarchy
					and (
						next_associations.start_date is null
						or next_associations.start_date <= schedule_associations.end_date
						or schedule_associations.end_date is null
					)
					and (
						next_associations.end_date is null
						or next_associations.end_date > schedule_associations.start_date
						or schedule_associations.start_date is null
					)
		where schedule_associations.deleted_at is null
		group by
			schedule_associations.schedule_id,
			schedule_associations.id,
			schedule_associations.schedule_week_template_id,
			schedule_associations.start_date,
			schedule_associations.end_date
	) as next_start_dates
		left join schedule_associations
			on schedule_associations.schedule_id = next_start_dates.schedule_id
				and schedule_associations.start_date = next_start_dates.next_start_date
				and schedule_associations.id != next_start_dates.previous_association_id
	where (
		next_start_dates.previous_start_date < next_start_dates.next_start_date
		or (
			next_start_dates.previous_start_date is null
			and next_start_dates.next_start_date is not null
		)
	)
	group by
		next_start_dates.schedule_id,
		next_start_dates.previous_association_id,
		next_start_dates.previous_template_id,
		next_start_dates.previous_start_date,
		next_start_dates.previous_end_date,
		next_start_dates.next_start_date
) as next_hierarchies
	left join schedule_associations
		on schedule_associations.schedule_id = next_hierarchies.schedule_id
			and schedule_associations.start_date = next_hierarchies.next_start_date
			and schedule_associations.hierarchy = next_hierarchies.next_hierarchy
			and schedule_associations.id != next_hierarchies.previous_association_id
order by next_hierarchies.next_start_date