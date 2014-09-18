<?php

defined('ABSPATH') or die("No script kiddies please!");

/**
 * @package NoSSL
 * @version 1.0
 *
 */
/*
Plugin Name: NoSSL - protect your website
Plugin URI: http://www.nossl.net/
Description: NoSSL is an open-source software to encrypt the data sent between browser and webserver to protect it from hackers, internet service providers and spies. It is a simple-to-implement library written in PHP and JavaScript, which you can easily integrate into your website. It will protect your login forms, contact forms and posts.
Author: Smart In Media (c)2014
Version: 1.1
Author URI: http://www.nossl.net/
License: GPL3
*/
/*
########################################################################################

## NoSSL V1.1 - Encryption between browser and server

########################################################################################

## Copyright (C) 2013 - 2014 Smart In Media GmbH & Co. KG

##

## http://www.nossl.net

##

########################################################################################



THIS PROGRAM IS LICENSED FOR PRIVATE USE UNDER THE GPL LICENSE



FOR COMMERCIAL USE, PLEASE INQUIRE THROUGH www.nossl.net



########################################################################################
*/


add_action('init', 'process_nossl');
add_action('wp_enqueue_scripts', 'add_meta_files');

/**
 *
 */
function process_nossl()
{
    require_once(__DIR__ . '/nossl/nossl_start.php');
}

/**
 *
 */
function add_meta_files()
{
    wp_enqueue_style('nossl-style', plugins_url('/nossl/style/nossl.css', __FILE__), array(), '1.0');
    wp_enqueue_script('nossl-js', plugins_url('/nossl/javascript/nossl_start.min.js', __FILE__), array(), '1.0');
}
