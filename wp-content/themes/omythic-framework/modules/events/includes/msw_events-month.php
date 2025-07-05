<?php get_header(); ?>

<?php 

	$current_year = date('Y');
	$current_month = date('m');
	$current_date = date('d');

	if(isset($_GET['yr']) && isset($_GET['mo'])){
		$view_year = $_GET['yr'];
		$view_month = $_GET['mo'];
	}
	else{
		$view_year = $current_year;
		$view_month = $current_month;
	}
	$view_month_name = MakespaceFrameworkEventsCalendar::get_month_name($view_month);
	
	/* previous month */
	$previous_month = MakespaceFrameworkEventsCalendar::get_previous_month( $view_year . $view_month . '01');
	$previous_month_num = $previous_month->format('m');
	$previous_month_name = $previous_month->format('F');
	$previous_month_year = $previous_month->format('Y');
	$previous_month_link = $events_archive_link . '?yr=' . $previous_month_year . '&mo=' . $previous_month_num;

	// if(isset($_GET['state'])){
	// 	$previous_month_link .= '&state=' . $state_filtered;
	// }
	if(isset($_GET['event_category'])){
		$previous_month_link .= '&event_category=' . $cat_filtered_term->slug;
	}
	
	/* next month */
	$next_month = MakespaceFrameworkEventsCalendar::get_next_month( $view_year . $view_month . '01');
	$next_month_num = $next_month->format('m');
	$next_month_name = $next_month->format('F');
	$next_month_year = $next_month->format('Y');
	$next_month_link = $events_archive_link . '?yr=' . $next_month_year . '&mo=' . $next_month_num;
	
	// if(isset($_GET['state'])){
	// 	$next_month_link .= '&state=' . $state_filtered;
	// }
	if(isset($_GET['event_category'])){
		$next_month_link .= '&event_category=' . $cat_filtered_term->slug;
	}

?>

<div class="msw-calendar">
	<div class="msw-calendar-header">
		<div class="center">
			<h2 class="msw-calendar-title">
				<span class="month"><?php echo $view_month_name; ?></span>
				<span class="year"><?php echo $view_year; ?></span>
			</h2>
			
			<?php /* ?>
			<div class="msw-calendar-views-nav">
				<a href="<?php echo $events_archive_link; ?>" class="month-view-link <?php echo $list_view === false ? 'active' : ''; ?>"><i class="far fa-calendar-alt"></i><span class="screen-reader-text">Month View</span></a>
				<a href="<?php echo $events_archive_link; ?>?view=list" class="list-view-link <?php echo $list_view === true ? 'active' : ''; ?>"><i class="fas fa-list-ul"></i><span class="screen-reader-text">List View</span></a>
			</div>
			*/ ?>
		</div>
		<a href="<?php echo $previous_month_link; ?>" class="msw-calendar-nav-link msw-calendar-prev-month">
			<i class="arrow fal fa-angle-left"></i>
			<span class="month"><?php echo $previous_month_name; ?></span>
			<span class="year"><?php echo $previous_month_year; ?></span>
		</a>
		<a href="<?php echo $next_month_link; ?>" class="msw-calendar-nav-link msw-calendar-next-month">
			<span class="month"><?php echo $next_month_name; ?></span>
			<span class="year"><?php echo $next_month_year; ?></span>
			<i class="arrow fal fa-angle-right"></i>
		</a>
	</div>
	<?php echo MakespaceFrameworkEventsCalendar::draw_calendar($view_month,$view_year); ?>
</div>
	