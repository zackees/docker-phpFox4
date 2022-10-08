<?php
if ($forceFlavor = request()->get('force-flavor')) {
    define('PHPFOX_FORCE_FLAVOR_NAME', $forceFlavor);
}