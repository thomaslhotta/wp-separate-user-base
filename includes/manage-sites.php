<?php
add_action( 'edit_user_profile', 'manage_site_options', 1 );
add_action( 'show_user_profile', 'manage_site_options', 1 );
add_action( 'personal_options_update', 'manage_site_options_update' );
add_action( 'edit_user_profile_update', 'manage_site_options_update' );

function manage_site_options() {
    $all_sites         = get_sites();
    $current_user_site = wp_sub_get_user_sites( get_current_user_id() );
    $data_site_format  = array();
    ?>
    <h2>Manage Sites</h2>
    <div class="wrap">
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
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
                <button type="button" id="add_site" class="button action" onclick="addRowSite('list_site')">Add</button>
            </div>
        </div>
        <table id="list_site" class="wp-list-table widefat fixed striped table-view-list posts" style="width:500px;">
            <tbody id="the-list">
            <?php if ( count( $current_user_site ) > 0 ) : ?>
                <?php foreach ( $current_user_site as $site_id ) : ?>
                    <tr id="site-<?php echo $site_id; ?>" >
                        <td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
                            <?php if ( isset( $data_site_format[ $site_id ] ) ) : ?>
                                <?php echo $data_site_format[ $site_id ]->blogname; ?> (ID: <?php echo $data_site_format[ $site_id ]->blog_id; ?>)
                            <?php else : ?>
                                Site <?php echo $site_id; ?>
                            <?php endif; ?>
                        </td>
                        <td class="action-remove-site" style="width: 80px;" >
                            <input class="input-text" type="hidden" value="<?php echo $site_id; ?>" name="site_id[<?php echo $site_id; ?>]" />
                            <button type="button" class="button action btn-remove-site" onclick="delRowSite(this)">
                                <span>Remove</span>
                            </button>
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
        function addRowSite(table){
            var select = document.getElementById('action-selector-site');
            var id=document.getElementById(table).getElementsByTagName('tbody')[0];
            var newRow=id.insertRow();
            var selectedElement = select.options[select.selectedIndex];
            newRow.innerHTML='<tr id="site-" >' +
                '<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">' +
                selectedElement.innerHTML +
                '</td>' +
                '<td class="action-remove-site" style="width: 80px;" >' +
                '<input class="input-text" type="hidden" value="'+selectedElement.value+'" name="site_id['+selectedElement.value+']" />' +
                ' <button type="button" class="button action btn-remove-site" onclick="delRowSite(this)"> ' +
                '<span>Remove</span> ' +
                '</button> ' +
                '</td>' +
                '</tr>';
            select.remove(select.selectedIndex);
        }
        function delRowSite(btn) {
            var deleteRow = btn.parentNode.parentNode;
            var select = document.getElementById('action-selector-site');
            var text = deleteRow.getElementsByClassName('column-title')[0].innerHTML;
            var value = deleteRow.getElementsByClassName('input-text')[0].value;
            select.options[select.options.length] = new Option(text, value);
            deleteRow.parentNode.removeChild(deleteRow);

        }
    </script>

    <?php
}

function manage_site_options_update($user_id) {
    $current_user_site = wp_sub_get_user_sites($user_id);

    $siteIds = [];
    if ( isset( $_POST['site_id'] ) ) {
        $siteIds = $_POST['site_id'];
    }
    if (!empty($current_user_site)) {
        foreach ($current_user_site as $siteId) {
            if (!in_array($siteId,$siteIds)) {
                wp_sub_remove_user_from_site($user_id,$siteId);
            }
        }
    }

    if (!empty($siteIds)) {
        foreach ($siteIds as $siteId) {
            if (!in_array($siteId,$current_user_site)) {
                wp_sub_add_user_to_site($user_id,$siteId);
            }
        }
    }
}
