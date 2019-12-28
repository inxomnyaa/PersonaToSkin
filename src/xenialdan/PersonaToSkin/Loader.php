<?php

namespace xenialdan\PersonaToSkin;

use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;

class Loader extends PluginBase
{
    /** @var Loader */
    private static $instance = null;
    private static $skins = [];

    /**
     * @throws PluginException
     */
    public function onLoad()
    {
        if (!extension_loaded('gd')) {
            throw new PluginException("gd is not enabled!");
        }
        self::$instance = $this;
        $this->saveDefaultConfig();
        SkinAdapterSingleton::set(new SkinAdapterPersona());
        // Skins
        if ($this->customSkins()) $skinPaths = glob($this->getDataFolder() . "*.png");
        if (!$this->customSkins() || empty($skinPaths)) {
            $this->saveResource("steve.png");
            $skinPaths = glob($this->getDataFolder() . "steve.png");
        }
        if (is_array($skinPaths)) {
            foreach ($skinPaths as $id => $skinPath) {
                set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($skinPath) {
                    $this->getLogger()->warning("Skin " . basename($skinPath) . " could not be loaded. Error: #$errno - $errstr");
                });
                $img = imagecreatefrompng($skinPath);
                restore_error_handler();
                if ($img === false) {
                    continue;//just continue, log via error handler above
                }
                self::$skins[] = new Skin("personatoskin." . basename($skinPath), self::fromImage($img), "", "geometry.humanoid.custom");
                @imagedestroy($img);
            }
        }
        if (empty(self::$skins)) {
            throw new PluginException("Could not get skin files, disabling!");
        }
    }

    /**
     * Returns an instance of the plugin
     * @return Loader
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * from skinapi
     * @param resource $img
     * @return string
     */
    public static function fromImage($img)
    {
        $bytes = '';
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $bytes;
    }

    public static function getRandomSkin(): Skin
    {
        return self::$skins[array_rand(self::$skins)];
    }

    public function customSkins(): bool
    {
        return $this->getConfig()->get("custom-skin", false);
    }
}