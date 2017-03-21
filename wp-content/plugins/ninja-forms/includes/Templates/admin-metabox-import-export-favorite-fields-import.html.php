<div class="wrap">

    <form action="" method="post" enctype="multipart/form-data">

        <table class="form-table">
            <tbody>
            <tr id="row_nf_import_fields">
                <th scope="row">
                    <label for="nf_import_fields"><?php echo __( 'Select a file', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="file" name="nf_import_fields" id="nf_import_fields" class="widefat">
                </td>
            </tr>
            <tr id="row_nf_import_fields_submit">
                <th scope="row">
                    <label for="nf_import_fields_submit"><?php _e( 'Import Fields', 'ninja-forms' ); ?></label>
                </th>
                <td>
                    <input type="submit" id="nf_import_fields_submit" class="button-secondary" value="<?php echo __( 'Import Fields', 'ninja-forms' ) ;?>">
                </td>
            </tr>
            </tbody>
        </table>

    </form>

</div>