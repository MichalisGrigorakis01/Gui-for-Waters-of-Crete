<?php
/**
 * @version 1.8
 */
global $orgid;

function render_table_waters( $atts ) {
	$orgid = $atts['orgid'];
	
	extract( shortcode_atts( array(
		'column' => ''    // Default to an empty string
	), $atts ) );

	
	$items = watersform($orgid, $page = 0, $size = 5, $id = NULL);
	
	
	if (empty($items) || count($items) == 2) {
		echo "<p style='text-align: center; font-size: 18px;'>Please fill the form above.</p>";
		return; // Stop execution, do not render the table
	}
	
	

	// utility function calls
	$org_name = get_org_name();
	$coasts = get_coasts();

	if ( ! empty( $items ) ){
		//$total_items =$items['result']['total'];
		$total_items = get_total_items();	 
	}
	// Get current page and rows per page from query parameters
	$current_page_D = isset( $_GET['table_page_D'] ) ? intval( $_GET['table_page_D'] ) : 1;
	$rows_per_page_D = isset( $_GET['rows_per_page_D'] ) ? intval( $_GET['rows_per_page_D'] ) : 10;

	// Ensure rows per page is a valid number (5, 10, or 20)
	if ( ! in_array( $rows_per_page_D, [ 5, 10, 20 ] ) ) {
		$rows_per_page_D = 10; // Default to 10
	}

	$total_pages = ceil( $total_items / $rows_per_page_D );

	// Get the current URL without query parameters
	$base_url = strtok( $_SERVER["REQUEST_URI"], '?' );

	// Build a function to append query parameters
	if ( ! function_exists( 'build_query_string' ) ) {
		function build_query_string( $params ) {
			return '?' . http_build_query( $params );
		}
	}
	ob_start(); // Start output buffering
	?>

	<div id="woc-plugin-container">
		<!-- Header Section -->
		<header class="woc-plugin-header">
			<div class="woc-logo-frame">
				<?php
				$logo = plugins_url( '../public/images/ydata.jpg', __FILE__ );
				echo
					"<a href='https://data.apdkritis.gov.gr/el/dataset/' target='_blank'>
                <img src={$logo}  alt='Logo'></a>"
					?>
			</div>
			<div class="woc-column-frame">
				<h1 class="woc-h1-class">
					<?php
					echo "<a href='https://data.apdkritis.gov.gr/el/dataset/' target='_blank'> {$org_name} </a>" ?>
				</h1>
			</div>
		</header>

		<!-- Rows Per Page Selector and Search Bar Above the Table -->
		<div class="woc-controls">
			<div class="woc-rows-selector">
				<form method="get" action="<?php echo esc_url( $base_url ); ?>">
					<label for="rows_per_page_D">Στοιχεία ανά Σελίδα:</label>
					<select id="rows_per_page_D" name="rows_per_page_D" onchange="this.form.submit()">
						<option value="5" <?php if ($rows_per_page_D == 5) echo 'selected'; ?>>5</option>
						<option value="10" <?php if ($rows_per_page_D == 10) echo 'selected'; ?>>10</option>
						<option value="20" <?php if ($rows_per_page_D == 20) echo 'selected'; ?>>20</option>
					</select>
					<input type="hidden" name="table_page_D" value="1">
					<?php
					// Preserve other query parameters
					foreach ( $_GET as $key => $value ) {
						if ( $key !== 'rows_per_page_D' && $key !== 'table_page_D' ) {
							echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
						}
					}
					?>
				</form>
			</div>
		</div>

		<!-- Table Section -->
		<div class="woc-table-container">
			<div class="table-responsive">
				<table id="table">
					<thead>
						<tr>
							<?php
							// If the column is not empty, split it into an array
							if ( ! empty( $column ) ) {
								$column = explode( ',', $column );
							} else {
								// Use default columns if none provided
								$column = array( 'Ακτή', 'Περιγραφή', 'Νομός', 'Δήμος', 'StationCode', 'Καθαριότητα', 'Κύμα', 'Ημ. δείγματος', 'Ημ. Ανάλυσης');
							}
							$column_translation = array(
									'Ημ. δείγματος'   				=> 'from_sampledate',
									'Καθαριότητα'          			=> 'clean',
									'Περιγραφή' 			  		=> 'description',
									'StationCode'			  		=> 'id',
									'Δήμος'							=> 'municipal',
									'Ακτή'							=> 'coast',
									'Κύμα'							=> 'wave', 
									'Ημ. Ανάλυσης'					=> 'analysedate',
									'Σύνδεσμοι'						=> 'download',
									'Νομός'							=> 'perunit'
							);
							// Start building the table output
							$output = '<table>';
							$output .= '<tr>';
							
							// Generate the table headers
							foreach ( $column as $column_column ) {
								echo '<th class="woc-' . htmlspecialchars( trim( $column_translation[$column_column] ) )
								. '">' . htmlspecialchars( trim( $column_column ) ) . '</th>';
							}
							echo '<tr>';
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						if($current_page_D > $total_pages){
							$current_page_D = 1;
						}
						// Generate rows of table data for the current page
						$start_row = ($current_page_D - 1) * $rows_per_page_D;
						$paged_items = array_slice($items, $start_row, $rows_per_page_D);

						if( !empty($id)){
							$items['actualSize'] = count($id);
							foreach ( $id as $curr_id ){
								$items = watersform($orgid,$page,$size,$curr_id);
								display_table($items, $column, $column_translation, $coasts, $items['actualSize'], $showtimes);
							}
							
						}else{
							$items = watersform( $orgid, $page, $size );
							display_table($paged_items, $column, $column_translation, $coasts, $rows_per_page_D, $showtimes);
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Pagination -->
		<div class="woc-pagination">
			<ul class="woc-pagination-list">
				<?php
				if( empty($id)){
					if($current_page_D > $total_pages){
						$current_page_D = 1;
					}
					// First and Previous links
					if ( $current_page_D > 1 ) {
						echo '<li><a href="' . esc_url( $base_url
						. build_query_string( array_merge( $_GET, [ 'table_page_D' => 1 ] ) ) )
						. '" class="woc-page-link">««</a></li>';
						echo '<li><a href="' . esc_url( $base_url
						. build_query_string( array_merge( $_GET, [ 'table_page_D' => $current_page_D - 1 ] ) ) )
						. '" class="woc-page-link">«</a></li>';
					}

					// Page number links
					$page_range = paginate_range( $current_page_D, $total_pages );

					foreach ( $page_range as $page ) {
						if ( $page === '...' ) {
							echo '<li><span class="woc-page-link">...</span></li>';
						} elseif ( $page == $current_page_D ) {
							echo '<li><span class="woc-page-link disabled">' . $page . '</span></li>';
						} else {
							echo '<li><a href="' . esc_url( $base_url . build_query_string( array_merge( $_GET, [ 
								'table_page_D' => $page
								 ] ) ) )
							. '" class="woc-page-link">' . $page . '</a></li>';
						}
					}
					// Next and Last links
					if ( $current_page_D < $total_pages ) {
						echo '<li><a href="' . esc_url( $base_url
						. build_query_string( array_merge( $_GET, [ 'table_page_D' => $current_page_D + 1 ] ) ) )
						. '" class="woc-page-link">»</a></li>';
						echo '<li><a href="' . esc_url( $base_url 
						. build_query_string( array_merge( $_GET, [ 'table_page_D' => $total_pages ] ) ) )
						. '" class="woc-page-link">»»</a></li>';
					}
				}else{
					echo ":D";
				}
				?>
			</ul>
		</div>
	</div>
	
	<?php

	return ob_get_clean(); // Return the output buffer contents
}


