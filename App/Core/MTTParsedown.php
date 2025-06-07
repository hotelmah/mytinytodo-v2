<?php

declare(strict_types=1);

namespace App\Core;

use App\Utility\Info;
use Parsedown;

class MTTParsedown extends Parsedown
{
    protected $toExternal;

    public function __construct()
    {
        $this->toExternal = false;

        $this->InlineTypes['#'][] = 'TaskId';
        $this->inlineMarkerList .= '#';
    }

    public function setToExternal(bool $v)
    {
        $this->toExternal = $v;
    }

    protected function inlineTaskId($excerpt)
    {
        if (preg_match('/^#(\d+)/', $excerpt['text'], $matches)) {
            $attrs = array(
                'href' => Info::getMttinfo('url') . '?task=' . $matches[1],
                'target' => '_blank',
            );
            if (!$this->toExternal) {
                $attrs['class'] = 'mtt-link-to-task';
                $attrs['data-target-id'] = $matches[1];
            }
            return array(

                // How many characters to advance the Parsedown's
                // cursor after being done processing this tag.
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => '#' . $matches[1],
                    'attributes' => $attrs,
                ),

            );
        }
    }

    protected function inlineLink($Excerpt)
    {
        $a = parent::inlineLink($Excerpt);
        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
        }
        return $a;
    }

    protected function inlineUrl($Excerpt)
    {
        $a = parent::inlineUrl($Excerpt);

        if (is_array($a) && isset($a['element']['attributes']['href'])) {
            $a['element']['attributes']['target'] = '_blank';
        }

        return $a;
    }
}
