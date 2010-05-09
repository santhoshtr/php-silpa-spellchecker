<?php
/**
 * silpa compatibility wrapper
 *
 * This library provides some of the functions from the pspell PHP extension
 * by wrapping them to calls to the aspell binary
 *
 * It can be simply dropped into code written for the pspell extension like
 * the following
 *
 * @author   Santhosh Thottingal <santhosh.thottingal@gmail.com>
 *
 * Copyright (c) 2010, Santhosh Thottingal
 * GPLV3
 */
require_once 'jsonRPCClient.php';
if(!defined('SILPA_JSONRPC_SERVICE_URL')) define('SILPA_JSONRPC_SERVICE_URL','http://localhost:8080/JSONRPC');

function silpaspell_create(){
    return new SilpaSpell();
}


function silpaspell_check(&$dict,$word){
    return $dict->check($word);
}

function silpaspell_suggest(&$dict, $word){
    return $dict->suggest($word);
}

/**
 * Class to provide pspell functionality through silpa
 *
 * Needs PHP 5 or greater
 */
class SilpaSpell
{


    /**
     * Constructor. Works like pspell_config_create()
     *
     * @author   Andreas Gohr <andi@splitbrain.org>
     * @todo     $spelling isn't used
     */
    public function __construct(){

        $this->service = new jsonRPCClient(SILPA_JSONRPC_SERVICE_URL);
    }


    /**
     * Checks a word for correctness
     *
     * @returns array of suggestions on wrong spelling, or true on no spellerror
     *
     */
    function suggest($word)
    {
		$word = trim($word);

        if (empty($word)) {
            return true;
        }
        $suggestions = json_decode($this->service->execute('modules.Spellchecker.suggest',array($word)));
        return $suggestions;
    }

    /**
     * Check if a word is misspelled 
     *
     */
    function check($word)
    {
		$word = trim($word);

        if (empty($word)) {
            return true;
        }
		if( json_decode($this->service->execute('modules.Spellchecker.check',array($word)))){
			return true;
		}
		return false;
    }
}
?>
