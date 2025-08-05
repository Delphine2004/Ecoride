<?php

define('ROOT_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR); // Répertoire racine du projet (au-dessus de public)

// Chemins des dossiers principaux
define('SRC_PATH', ROOT_PATH . 'src' . DIRECTORY_SEPARATOR); // C:\wamp64\www\ECF\src\
define('CORE_PATH', SRC_PATH . 'Core' . DIRECTORY_SEPARATOR); // C:\wamp64\www\ECF\src\Core\
define('VIEW_PATH', SRC_PATH . 'View' . DIRECTORY_SEPARATOR);
define('PAGES_PATH', VIEW_PATH . 'Pages' . DIRECTORY_SEPARATOR);
define('LAYOUT_PATH', VIEW_PATH . 'Layout' . DIRECTORY_SEPARATOR);
define('ROUTES_PATH', SRC_PATH . 'Routes' . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', SRC_PATH . 'Config' . DIRECTORY_SEPARATOR);
define('CONTROLLER_PATH', SRC_PATH . 'Controller' . DIRECTORY_SEPARATOR);







/* Front */
define('PUBLIC_PATH', '/ECF/ecoride/public/');
define('CSS_PATH', PUBLIC_PATH . 'assets/css/');
define('IMG_PATH', PUBLIC_PATH . 'assets/img/');
define('JS_PATH', PUBLIC_PATH . 'assets/js/');
