<?php
return function (Phpfox_Installer $Installer) {
    // import phrase for theme-material.
    if(file_exists($filename = PHPFOX_DIR_SITE . 'flavors/material/phrase.json')){
        \Core\Lib::phrase()->addPhrase(json_decode(file_get_contents($filename),true));
    }
};
