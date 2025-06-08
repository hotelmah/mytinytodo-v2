<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use App\Utility\Authentication;
use App\Core\MTTExtension;
use App\Core\MTTExtensionSettingsInterface;
use App\Core\MTTExtensionLoader;
use App\Lang\Lang;
use monolog\Logger;

class ExtSettingsController extends BaseControllerApi
{
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);
        $this->log = $this->log->withName('ExtSettingsController');
    }

    /**
     * Get extension settings page
     * @return void
     * @throws Exception
     */
    public function get(string $ext): ResponseInterface
    {
        Authentication::checkWriteAccess();

        /** @var MTTExtension|MTTExtensionSettingsInterface $instance */
        $instance = $this->extInstance($ext);

        if (!$instance) {
            return (new Psr17Factory())->createResponse(404)
            ->withBody((new Psr17Factory())->createStream('Extension not found'))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        }

        $meta = MTTExtension::extMetaInfo($ext);

        if (!$meta || !isset($meta['name'])) {
            return (new Psr17Factory())->createResponse(404)
            ->withBody((new Psr17Factory())->createStream('Extension metadata not found'))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        }

        $data = $instance->settingsPage();

        $lang = Lang::instance();
        $nameKey = 'ext.' . $ext . '.name';

        if ($lang->hasKey($nameKey)) {
            $name = htmlspecialchars($lang->get($nameKey));
        } else {
            $name = htmlspecialchars($meta['name']);
        }

        $escapedExt = htmlspecialchars($ext);
        $e = function ($s) use ($lang) {
            return (new Psr17Factory())->createResponse(200)
            ->withBody((new Psr17Factory())->createStream(htmlspecialchars($lang->get($s))))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        };

        $formStart = '';
        $formEnd = '';
        $formButtons = '';

        if ($instance->settingsPageType() == 0) {
            $formStart = "<form id='ext_settings_form' data-ext='$escapedExt'>";
            $formEnd = "</form>";
            $formButtons = <<<EOD
            <div class="tr form-bottom-buttons">
                <button type="submit">{$e('set_submit')}</button>
                <button type="button" class="mtt-back-button">{$e('set_cancel')}</button>
            </div>
            EOD;
        }

        $data = <<<EOD
        <h3 class="page-title"><a class="mtt-back-button"></a> $name </h3>
        <div id="settings_msg" style="display:none"></div>
        $formStart
        <div class="mtt-settings-table">
            $data
            $formButtons
        </div>
        $formEnd
        EOD;

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($data));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'text/html; charset=utf-8');
    }

    /**
     * Save extension settings
     * @return void
     * @throws Exception
     */
    public function put(ServerRequestInterface $request, string $ext): ResponseInterface
    {
        Authentication::checkWriteAccess();

        /** @var MTTExtension|MTTExtensionSettingsInterface $instance */
        $instance = $this->extInstance($ext);

        if (!$instance) {
            return (new Psr17Factory())->createResponse(404)
            ->withBody((new Psr17Factory())->createStream('Extension not found'))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        }

        //$userError = '';
        $saved = $instance->saveSettings($request->getParsedBody() ?? [], $userError);
        $a = [ 'saved' => (int)$saved ];

        if ($userError) {
            $a['msg'] = $userError;
        }

        /* ===================================================================================================================== */

        $psr17Factory = new Psr17Factory();

        $responseBody = $psr17Factory->createStream(json_encode($a));
        return $psr17Factory->createResponse(200)->withBody($responseBody)->withHeader('Content-type', 'application/json');
    }

    /* ===================================================================================================================== */

    private function extInstance(string $ext): MTTExtensionSettingsInterface|ResponseInterface
    {
        $instance = MTTExtensionLoader::extensionInstance($ext);

        if (!$instance) {
            return (new Psr17Factory())->createResponse(404)
            ->withBody((new Psr17Factory())->createStream(json_encode(['msg' => 'Unknown extension'])))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        }

        if (!($instance instanceof MTTExtensionSettingsInterface)) {
            return (new Psr17Factory())->createResponse(500)
            ->withBody((new Psr17Factory())->createStream(json_encode(['msg' => 'No settings page for extension'])))
            ->withHeader('Content-type', 'text/plain; charset=utf-8');
        }
        return $instance;
    }
}
