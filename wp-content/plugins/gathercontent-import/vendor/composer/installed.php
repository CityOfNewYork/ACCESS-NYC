<?php return array(
    'root' => array(
        'pretty_version' => '3.2.1',
        'version' => '3.2.1.0',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'gathercontent/wp-importer',
        'dev' => false,
    ),
    'versions' => array(
        'gathercontent/wp-importer' => array(
            'pretty_version' => '3.2.1',
            'version' => '3.2.1.0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
        'techcrunch/wp-async-task' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../techcrunch/wp-async-task',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => '9bdbbf9df4ff5179711bb58b9a2451296f6753dc',
            'dev_requirement' => false,
        ),
    ),
);
