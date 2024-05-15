<?php

namespace App\Csp\Policies;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Basic;

class OpeningHours extends Basic 
{

    /**
     * {@inheritdoc}
     */
    public function configure() 
    {
        parent::configure();

        $this->addDirective(Directive::SCRIPT, 'https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.3.0/zxcvbn.js');
        $this->addDirective(Directive::STYLE, 'https://fonts.googleapis.com/css');
        $this->addDirective(Directive::FONT, Keyword::SELF);
        $this->addDirective(Directive::FONT, 'https://fonts.gstatic.com/s/sourcesanspro/');
        $this->addDirective(Directive::IMG, 'data:');
    }
}
