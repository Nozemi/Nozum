<?php
    use ForumLib\ThemeEngine\MainEngine;

    class EldriosDropsEngine extends MainEngine {
        private $engine;

        public function __construct(MainEngine $_engine) {
            if($_engine instanceof MainEngine) {
                $this->engine = $_engine;
            }
        }

        public function customParse($_template) {

        }


        public static function hook_top(MainEngine $engine) {
            if(isset($_GET['page']) == 'drops' && isset($_GET['action']) == 'npc') {
                return array(
                    'content' => $engine->getTemplate('drops_npc', 'drops'),
                    'exit'    => true
                );
            }

            return array(
                'content' => false,
                'exit' => false,
                'deny404' => true
            );
        }
    }