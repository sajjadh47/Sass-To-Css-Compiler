<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb09697ec00114c14ab5d30b8b1f230ce
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ScssPhp\\ScssPhp\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ScssPhp\\ScssPhp\\' => 
        array (
            0 => __DIR__ . '/..' . '/scssphp/scssphp/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb09697ec00114c14ab5d30b8b1f230ce::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb09697ec00114c14ab5d30b8b1f230ce::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
