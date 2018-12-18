<?php
/**
 * ping
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Ping extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->response('pong');
    }
}
