<?php
    use ForumLib\ThemeEngine\MainEngine;

    class EldriosHighscoresEngine extends MainEngine {
        private $engine;

        public function __construct(MainEngine $_engine) {
            if($_engine instanceof MainEngine) {
                $this->engine = $_engine;
            }
        }

        public function customParse($_template) {
            $matches = $this->engine->getPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[2]) {
                    case 'hsBlock':
                        $html = '';
                        for($i = 0; $i < 5; $i++) {
                            $html .= $this->engine->getTemplate('portal_highscores_skill', 'portal');
                        }
                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;

                    case 'skillName':
                        $_template = $this->engine->replaceVariable($match, $_template, 'Test');
                        break;
                    case 'topPlayerUrl':
                        $_template = $this->engine->replaceVariable($match, $_template, '#');
                        break;
                    case 'topPlayerLevel':
                        $_template = $this->engine->replaceVariable($match, $_template, 99);
                        break;
                    case 'topPlayerName':
                        $_template = $this->engine->replaceVariable($match, $_template, 'Nozemi');
                        break;
                    case 'skillImg':
                        $_template = $this->engine->replaceVariable($match, $_template, $this->engine->directory . '/_assets/img/eldrios/highscores/prayer.png');
                        break;
                }
            }

            return $_template;
        }
    }