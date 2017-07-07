<?php

namespace smartsites\yii2\utils;

use Yii;
use yii\base\Theme;

/**
 * Yii2 component to use in place of [[yii\base\Theme]] that allows having the desktop and mobile versions of the
 * website. Chooses the actual theme based on the client device.
 */
class DesktopMobileThemeFork extends Theme
{

    /** @var string Path to the desktop/tablet theme */
    public $desktopTabletPath = "@app/views/desktop-tablet";

    /** @var string Path to the mobile theme */
    public $mobilePath = "@app/views/mobile";

    /** @var bool If true, uses the desktop/tablet theme for all types of devices */
    public $useOnlyDesktopTheme = false;

    public function init()
    {
        parent::init();
        if (!$this->useOnlyDesktopTheme && $this->isMobileDevice()) {
            $viewsPath = $this->mobilePath;
        } else {
            $viewsPath = $this->desktopTabletPath;
        }
        $this->pathMap = [
            "@app/views" => $viewsPath . "/pages",
            "@app/views/layouts" => $viewsPath . "/layouts",
            "@app/widgets/views" => $viewsPath . "/widgets",
            "@app/views/partials" => $viewsPath . "/partials",
            "@dektrium/user/views" => $viewsPath . "/pages/dektrium-yii2-user",
        ];
    }

    /**
     * You can override this method to choose a different strategy for detecting a mobile device.
     */
    protected function isMobileDevice()
    {
        return Yii::$app->devicedetect->isMobile();
    }

}
