<div id="nf-sub-fields">

    <table class="nf-sub-custom-fields-table">

        <thead>
            <tr>
                <th><?php _e( 'Field', 'ninja-forms' ); ?></th>
                <th><?php _e( 'Value', 'ninja-forms' ); ?></th>
            </tr>
        </thead>

        <tbody>
        <?php foreach( $fields as $field ): ?>

            <?php if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue; ?>
            <?php if( ! isset( Ninja_Forms()->fields[ $field->get_setting( 'type' ) ] ) ) continue; ?>
            
            <?php $field_class = Ninja_Forms()->fields[ $field->get_setting( 'type' ) ]; ?>
            <?php if( ! $field_class ) continue; ?>

            <tr>
                <td><?php echo ( $field->get_setting( 'admin_label' ) ) ? $field->get_setting( 'admin_label' ) : $field->get_setting( 'label' ) ; ?></td>
                <td><?php echo $field_class->admin_form_element( $field->get_id(), $sub->get_field_value( $field->get_id() ) ); ?></td>
            </tr>

        <?php endforeach; ?>
        </tbody>

    </table>

    <!-- TODO: Move to Style Sheet -->
    <style>
        .nf-sub-custom-fields-table {
            width: 100%;
            border-spacing: 0;
        }
        .nf-sub-custom-fields-table thead {
            background-color: #f1f1f1;
        }
        .nf-sub-custom-fields-table thead th {
            text-align: left;
        }
        .nf-sub-custom-fields-table th,
        .nf-sub-custom-fields-table td {
            padding: 10px 0 10px 10px;
            vertical-align: top;
        }
        .nf-sub-custom-fields-table textarea {
            height: 150px;
        }
    </style>

</div>