<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Kjero User Profile
 * Description:
 * Version:           1.0.0
 * Author:            Vlada Vojnovic
 */
/**
 * Add menu profile
 */
add_action('admin_menu', 'custom_menu');
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

function getAllSites ()
{
    global $wpdb;
    //return $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} left join ");
    $bp_prefix       = bp_core_get_table_prefix();
    $blog = "{$wpdb->blogs}";
    $blogMeta = "{$bp_prefix}bp_user_blogs_blogmeta";
    return $wpdb->get_results( "SELECT blog.*, meta.meta_value as blogname FROM $blog  as blog LEFT JOIN $blogMeta as meta on meta.blog_id = blog.blog_id WHERE meta.meta_key  = 'name'");
}
function page_manage_site_callback_function()
{
    $message = false;
    if(isset($_POST['site_id'])) {
        if(wp_sub_remove_user_from_site(get_current_user_id() , (int)$_POST['site_id'])){
            $message =  'Delete site successfully';
        }
    }else if(isset($_POST['add_site'])) {
        if(wp_sub_add_user_to_site(get_current_user_id() , (int)$_POST['add_site'])){
            $message = 'Add site successfully';
        }
    }

    $allSites = getAllSites();
    $currentUserSite = wp_sub_get_user_sites( get_current_user_id() );
    $dataSiteFormat = array();
    ?>

    <?php if($message): ?>
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
                        <?php if(count($allSites)>0):?>
                            <?php foreach ($allSites as $site): ?>
                                <?php $dataSiteFormat[$site->blog_id] = $site; ?>
                                <?php if( !in_array($site->blog_id,$currentUserSite)):?>
                                    <option value="<?php echo $site->blog_id ?>">
                                        <?php echo $site->blogname ?> <b>(ID: <?php echo $site->blog_id ?>)</b>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option >Empty site</option>
                        <?php endif; ?>
                    </select>
                    <button type="button" id="add_site" class="button action">Add</button>
                </form>
            </div>
        </div>
        <table class="wp-list-table widefat fixed striped table-view-list posts" style="width:500px;">
            <tbody id="the-list">
            <?php if(count($currentUserSite)>0):?>
                <?php  foreach ($currentUserSite as $siteKey => $siteId): ?>
                    <tr id="site-<?php echo $siteId ?>" >
                        <td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
                            <?php if(isset($dataSiteFormat[$siteId])): ?>
                                <?php echo $dataSiteFormat[$siteId]->blogname ?> <b>(ID: <?php echo $dataSiteFormat[$siteId]->blog_id ?>)</b>
                            <?php else: ?>
                                Site <?php echo $siteId ?>
                            <?php endif; ?>
                        </td>
                        <td class="action-remove-site" style="width: 80px;" >
                            <form method="post">
                                <input type="hidden" value="<?php echo $siteId ?>" name="site_id">
                                <button type="button" class="button action btn-remove-site">
                                    <span>Remove</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
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