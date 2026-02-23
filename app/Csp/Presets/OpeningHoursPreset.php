<?php

namespace App\Csp\Presets;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Keyword;

class OpeningHoursPreset implements Preset
{

    /**
     * {@inheritdoc}
     */
    public function configure(Policy $policy): void
    {
        $policy->add(Directive::SCRIPT, 'https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.3.0/zxcvbn.js')
        ->add(Directive::STYLE, 'https://fonts.googleapis.com/css')
        ->add(Directive::STYLE, 'https://unpkg.com/')
        ->add(Directive::STYLE, 'https://unpkg.com/vue-multiselect@3.0.0/dist/vue-multiselect.css')
        ->add(Directive::FONT, Keyword::SELF)
        ->add(Directive::FONT, 'https://fonts.gstatic.com/s/sourcesanspro/')
        ->add(Directive::IMG, 'data:')
        ->addNonce(Directive::SCRIPT)
        ->addNonce(Directive::STYLE)
        ->addNonce(Directive::FONT);
    }
}