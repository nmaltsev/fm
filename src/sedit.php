<?php
define('VERSION','1.2024.11.29');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// define('TMP_DIR', '/tmp');

function getfrom($array, $key, $default) {return isset($array[$key]) ? $array[$key] : $default;}

$path = getfrom($_GET, 'path', '');
$action = getfrom($_GET, 'action', 'index');

function layoutHead() {
    return '<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8"/>
        <meta name="robots" content="noindex, nofollow">
        <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
        <link rel="icon" href="./favicon.svg" type="image/svg+xml" />
        <style>
:root{}
body{margin:0;width:100vw;height:100vh;font:13px/15px Arial;}
        </style>
    </head>
    <body>';
}
function layoutTail() {
    return '</body></html>';
}
if ($action == 'index') {
	echo layoutHead();
echo '
<script>
customElements.define("sedit-form", class extends HTMLElement {
    #$root = null;
    
    constructor() {
        super();
    }

	/**
		@returns {string[]} list of attributes
	*/
    static get observedAttributes() {
        return []; 
    }

    attributeChangedCallback(name, oldValue, newValue) {
    }

    connectedCallback() {
        const $root = this.attachShadow({mode: "open"});
		$root.innerHTML = this.constructor.template;
        this.#$root = $root;
    }

    disconnectedCallback()  {
        // TODO
    }

    static template = `
<style>
:host {
    display: inline-flex;
}
</style>
	<form>
		<textarea></textarea>
	</form>
`
});
</script>
';	
	echo layoutTail();
} 
else if ($action == 'update') {
	
}
