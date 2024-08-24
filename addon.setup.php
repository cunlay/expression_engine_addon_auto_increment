<?php

return array(
    'author'      => 'David',
    'author_url'  => 'https://david.nmd.cc/',
    'name'        => 'Auto Increment',
    'description' => 'A fieldtype for creating channel-specific auto-incrementing fields.',
    'version'     => '1.0',
    'namespace'   => 'David\AutoIncrement',
    'settings_exist' => FALSE,
    'docs_url'    => 'https://david.nmd.cc/auto_increment/icon.png',
    'fieldtypes' => array(
        'auto_increment' => array(
            'name' => 'Auto Increment',
            'compatibility' => 'text',
        )
    ),
    'extensions' => array(
        'auto_increment_ext' => array(
            'class'    => 'Auto_increment_ext',
            'method'   => 'after_channel_entry_insert',
            'hook'     => 'after_channel_entry_insert',
            'settings' => serialize(array()),
            'priority' => 10,
            'version'  => '1.0',
            'enabled'  => 'y'
        ),
        'auto_increment_ext' => array(
            'class'    => 'Auto_increment_ext',
            'method'   => 'after_channel_entry_update',
            'hook'     => 'after_channel_entry_update',
            'settings' => serialize(array()),
            'priority' => 10,
            'version'  => '1.0',
            'enabled'  => 'y'
        ),
        'auto_increment_ext' => array(
            'class'    => 'Auto_increment_ext',
            'method'   => 'after_channel_entry_delete',
            'hook'     => 'after_channel_entry_delete',
            'settings' => serialize(array()),
            'priority' => 10,
            'version'  => '1.0',
            'enabled'  => 'y'
        ),
        'auto_increment_ext' => array(
            'class'    => 'Auto_increment_ext',
            'method'   => 'after_channel_entry_bulk_delete',
            'hook'     => 'after_channel_entry_bulk_delete',
            'settings' => serialize(array()),
            'priority' => 10,
            'version'  => '1.0',
            'enabled'  => 'y'
        ),
    ),
);
