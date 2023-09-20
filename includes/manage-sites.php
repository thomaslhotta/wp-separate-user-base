<?php
/**
 * Add menu profile
 */
add_action( 'admin_menu', 'custom_menu' );
function custom_menu() {
	add_users_page(
		'Manage Sites',
		'Manage Sites',
		'import',
		'manage-site',
		'page_manage_site_callback_function',
		4
	);
}

/**
 * Manage list sites
 */
function page_manage_site_callback_function() {
	$message = false;
	if ( isset( $_POST['site_id'] ) ) {
		if ( wp_sub_remove_user_from_site( get_current_user_id(), (int) $_POST['site_id'] ) ) {
			$message = 'Delete site successfully';
		}
	} elseif ( isset( $_POST['add_site'] ) ) {
		if ( wp_sub_add_user_to_site( get_current_user_id(), (int) $_POST['add_site'] ) ) {
			$message = 'Add site successfully';
		}
	}

	$all_sites         = get_sites();
	$current_user_site = wp_sub_get_user_sites( get_current_user_id() );
	$data_site_format  = array();
	?>

	<?php if ( $message ) : ?>
	<div class="notice notice-success">
		<p><?php echo $message; ?></p>
	</div>
<?php endif; ?>

	<div class="wrap">
		<h1 class="wp-heading-inline">Manage Sites</h1>
		<ul class="subsubsub">
			<li><b>Sites</b></li>
		</ul>
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="action-selector-site" class="screen-reader-text">Site 1</label>
				<form method="post">
					<select name="add_site" id="action-selector-site" style="min-width: 200px;">
						<?php if ( count( $all_sites ) > 0 ) : ?>
							<?php foreach ( $all_sites as $site ) : ?>
								<?php $data_site_format[ $site->blog_id ] = $site; ?>
								<?php if ( ! in_array( $site->blog_id, $current_user_site, true ) ) : ?>
									<option value="<?php echo $site->blog_id; ?>">
										<?php echo $site->blogname; ?> <b>(ID: <?php echo $site->blog_id; ?>)</b>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<option >Empty site</option>
						<?php endif; ?>
					</select>
					<button type="button" id="add_site" class="button action">Add</button>
				</form>
			</div>
		</div>
		<table class="wp-list-table widefat fixed striped table-view-list posts" style="width:500px;">
			<tbody id="the-list">
			<?php if ( count( $current_user_site ) > 0 ) : ?>
				<?php foreach ( $current_user_site as $site_id ) : ?>
					<tr id="site-<?php echo $site_id; ?>" >
						<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
							<?php if ( isset( $data_site_format[ $site_id ] ) ) : ?>
								<?php echo $data_site_format[ $site_id ]->blogname; ?> <b>(ID: <?php echo $data_site_format[ $site_id ]->blog_id; ?>)</b>
							<?php else : ?>
								Site <?php echo $site_id; ?>
							<?php endif; ?>
						</td>
						<td class="action-remove-site" style="width: 80px;" >
							<form method="post">
								<input type="hidden" value="<?php echo $site_id; ?>" name="site_id">
								<button type="button" class="button action btn-remove-site">
									<span>Remove</span>
								</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="2">Empty site</td>
				</tr>
			<?php endif; ?>

			</tbody>
		</table>
	</div>
	<script>
		jQuery(document).ready(function(){
			jQuery('#add_site').on('click',function(){
				if(confirm('Are you sure you want to add new site ?')){
					jQuery(this).closest('form').submit();
				}
			})

			jQuery('.btn-remove-site').on('click',function(){
				if(confirm('Are you sure you want to delete this item ?')){
					jQuery(this).closest('form').submit();
				}
			})
		});
	</script>

	<?php
}
