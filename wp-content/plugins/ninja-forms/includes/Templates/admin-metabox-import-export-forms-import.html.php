<div class="wrap">

    <form action="" method="post" enctype="multipart/form-data">

        <table class="form-table">
            <tbody>
            <tr id="row_nf_import_form">
                <th scope="row">
                    <label for="nf_import_form"><?php echo __( 'Select a file', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="file" name="nf_import_form" id="nf_import_form" class="widefat">
                </td>
            </tr>
            <tr id="row_nf_import_form_submit">
                <th scope="row">
                    <label for="nf_import_form_submit"><?php _e( 'Import Form', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="submit" id="nf_import_form_submit" class="button-secondary" value="<?php echo __( 'Import Form', 'ninja-forms' ) ;?>">
                </td>
            </tr>
            </tbody>
        </table>

    </form>

</div>