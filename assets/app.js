/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

const $ = require('jquery');
global.$ = global.jQuery = $;

import "bootstrap";
import "@popperjs/core";
import "admin-lte";
import bsCustomFileInput from "bs-custom-file-input/dist/bs-custom-file-input";
import "select2";

import dt from "datatables.net-responsive-bs4";

// start the Stimulus application
import './bootstrap';

bsCustomFileInput.init();

$('[data-toggle="tooltip"]').tooltip();
$('.select2').select2();