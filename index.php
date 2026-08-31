<?php

/**
 * Front controller when the vhost document root is the project folder
 * (OVH default) rather than public/. Requests that already hit public/
 * never load this file.
 */
require __DIR__.'/public/index.php';
