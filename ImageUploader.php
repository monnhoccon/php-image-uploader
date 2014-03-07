<?php
/**
 * A library for uploading image to some hosting services like picasa, imgur, imageshack, etc..
 *
 * @author     Phan Thanh Cong <ptcong90@gmail.com>
 * @copyright  2010-2014 Phan Thanh Cong.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    2.2
 * @release    Mar 07, 2014
 */
namespace ChipVN\ImageUploader;

class ImageUploader
{
    /**
     * Get a plugin instance.
     * @param  string                              $name
     * @return \ChipVN\ImageUploaderPlugins\Plugin
     */
    public function getPlugin($name)
    {
        $class = __NAMESPACE__ . '\Plugins\\' . $name;
        if (!class_exists($class, false)) {
            $this->loadPlugin($name);
        }

        return new $class;
    }

    /**
     * Create a plugin for uploading.
     *
     * @param  string                       $plugin
     * @return \ChipVN\ImageUploaderPlugins
     */
    public static function make($plugin)
    {
        $instance = new static;

        return $instance->getPlugin($plugin);
    }

    /**
     * Load a plugin by name, if file not found, system will load all plugins.
     * This to ensure that the plugin will be loaded to use.
     *
     * @param  string $name
     * @return void
     */
    protected function loadPlugin($name)
    {
        $pluginDir = __DIR__ . '/Plugins/';
        require_once $pluginDir . '../Plugin.php';

        if (file_exists($file = $pluginDir . $name . '.php')) {
            require_once $file;
        } else {
            foreach (glob($pluginDir . '*.php') as $file) {
                require_once $file;
            }
        }
    }
}
