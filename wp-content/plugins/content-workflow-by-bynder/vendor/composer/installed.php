<?php return array(
    'root' => array(
        'name' => 'content-workflow/wp-importer',
        'pretty_version' => '1.0.0',
        'version' => '1.0.0.0',
        'reference' => null,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'content-workflow/wp-importer' => array(
            'pretty_version' => '1.0.0',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'ralouphie/mimey' => array(
            'pretty_version' => '1.0.2',
            'version' => '1.0.2.0',
            'reference' => '2a0e997c733b7c2f9f8b61cafb006fd5fb9fa15a',
            'type' => 'library',
            'install_path' => __DIR__ . '/../ralouphie/mimey',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'techcrunch/wp-async-task' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '9bdbbf9df4ff5179711bb58b9a2451296f6753dc',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../techcrunch/wp-async-task',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
