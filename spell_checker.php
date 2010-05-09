<?php
/**
 * Version 3.3 - 16.March.2009
 * @copyright 2005 - 2009 Garrison Locke 
 * @author Garrison Locke (gplocke at gmail dot com) http://www.broken-notebook.com
 * 
 */
require_once ("silpa_comp.php");
// User-configurable list of allowed HTML tags and attributes.
$allowed_html = '<strong><small><p><br><a><b><u><i><img><code><ul><ol><li>';

// Set the max number of suggestions to return at a time.
define('MAX_SUGGESTIONS', 10);

// Set whether to use a personal dictionary.
$usePersonalDict = false;

//Set whether users are allowed to update the personal dictionary.
$editablePersonalDict = true;

// If using a personal dictionary, set the path to it.  Default is in the
// personal_dictionary subdirectory of the location of spell_checker.php.
$path_to_personal_dictionary = dirname(__FILE__) . "/personal_dictionary/personal_dictionary.txt";

// Create and configure a link to the silpa module.

$silpaspell_module = silpaspell_create();

switch($_POST['action']) {
	case 'spellcheck':
		spellCheck($_POST['spellText']);
		break;

	case 'suggest':
		getSuggestions($_POST['suggestionText']);
		break;

	default:
		echo "Unknown Action";
		break;
}


/*************************************************************
 * showSuggestions($word, $id)
 *
 * The showSuggestions function creates the list of up to 10
 * suggestions to return for the given misspelled word.
 *
 * $word - The misspelled word that was clicked on
 * $id - The id of the span containing the misspelled word.
 *
 *************************************************************/
function getSuggestions($word) {

	global $editablePersonalDict; //bool to set editability of personal dictionary
	global $silpaspell_module; //the global link to the pspell module

	$retVal = "";

	//an array of all the suggestions that psepll returns for $word.
	$suggestions = silpaspell_suggest($silpaspell_module, $word);

	// If the number of suggestions returned by pspell is less than the maximum
	// number, just use the number of suggestions returned.
	$numSuggestions = count($suggestions);
	$tmpNum = min($numSuggestions, MAX_SUGGESTIONS);

	if ($tmpNum > 0) {
		//this creates the table of suggestions.
		for ($i=0; $i<$tmpNum; $i++) {
			$retVal .= '<div class="suggestion">' . $suggestions[$i] . '</div>';
		}

		if ($editablePersonalDict) {
			$retVal .= '<div class="addToDictionary">Add To Dictionary</div>';
		}
	} else {
		$retVal .= "No Suggestions";
	}

	echo $retVal;  //the return value - a string containing the table of suggestions.

}


/*************************************************************
 * spellCheck($string)
 *
 * The spellCheck function takes the string of text entered
 * in the text box and spell checks it.  It splits the text
 * on anything inside of < > in order to prevent html from being
 * spell checked.  Then any text is split on spaces so that only
 * one word is spell checked at a time.  This creates a multidimensional
 * array.  The array is flattened.  The array is looped through
 * ignoring the html lines and spell checking the others.  If a word
 * is misspelled, code is wrapped around it to highlight it and to
 * make it clickable to show the user the suggestions for that
 * misspelled word.
 *
 * $string - The string of text from the text box that is to be
 *           spell checked.
 *
 *************************************************************/
function spellCheck($string)
{
	global $silpaspell_module; //the global link to the pspell module
	$retVal = "";

   	$string = removeMicrosoftWordCharacters($string);

   	//make all the returns in the text look the same
	$string = preg_replace("/\r?\n/", "\n", $string);

   	//splits the string on any html tags, preserving the tags and putting them in the $words array
   	$words = preg_split("/(<[^<>]*>)/", $string, -1, PREG_SPLIT_DELIM_CAPTURE);

   	$numResults = count($words); //the number of elements in the array.

	$misspelledCount = 0;

	//this loop looks through the words array and splits any lines of text that aren't html tags on space, preserving the spaces.
	for ($i=0; $i<$numResults; $i++) {
		// Words alternate between real words and html tags, starting with words.
		if (($i & 1) == 0) { // Even-numbered entries are word sets.

			$words[$i] = preg_split("/(\s+)/", $words[$i], -1, PREG_SPLIT_DELIM_CAPTURE); //then split it on the spaces

			// Now go through each word and link up the misspelled ones.
			$numWords = count($words[$i]);
			for ($j=0; $j<$numWords; $j++) {
				#preg_match("/[A-Z'0-9]+/i", $words[$i][$j], $tmp); //get the word that is in the array slot $i
				preg_match("/[^,!\?\.-]+/i", $words[$i][$j], $tmp); //get the word that is in the array slot $i
				$tmpWord = $tmp[0]; //should only have one element in the array anyway, so it's just assign it to $tmpWord

				//And we replace the word in the array with the span.
				if (!silpaspell_check($silpaspell_module, $tmpWord)) {
					$words[$i][$j] = str_replace($tmpWord, '<span>' . $tmpWord . '</span>', $words[$i][$j]);
					$misspelledCount++;
				}

				$words[$i][$j] = str_replace("\n", "<br />", $words[$i][$j]); //replace any breaks with <br />'s, for html display
			}//end for $j
		} else { //otherwise, we wrap all the html tags in comments to make them not displayed
			$words[$i] = str_replace("<", "<!--<", $words[$i]);
			$words[$i] = str_replace(">", ">-->", $words[$i]);
		}
	}//end for $i

	if ($misspelledCount == 0) {
	    echo 0;
	    return;
	} else {

    	$words = flattenArray($words); //flatten the array to be one dimensional.
    	$numResults = count($words); //the number of elements in the array after it's been flattened.

    	$string = ""; //return string

    	// Concatenate all the words/tags/etc. back into a string and append it to the result.
    	$string .= implode('', $words);

    	//remove comments from around all html tags except
    	//we want the html to be rendered in the div for preview purposes.
    	$string = preg_replace("/<!--<br( [^>]*)?>-->/i", "<br />", $string);
    	$string = preg_replace("/<!--<p( [^>]*)?>-->/i", "<p>", $string);
    	$string = preg_replace("/<!--<\/p>-->/i", "</p>", $string);
    	$string = preg_replace("/<!--<b( [^>]*)?>-->/i", "<b>", $string);
    	$string = preg_replace("/<!--<\/b>-->/i", "</b>", $string);
    	$string = preg_replace("/<!--<strong( [^>]*)?>-->/i", "<strong>", $string);
    	$string = preg_replace("/<!--<\/strong>-->/i", "</strong>", $string);
    	$string = preg_replace("/<!--<i( [^>]*)?>-->/i", "<i>", $string);
    	$string = preg_replace("/<!--<\/i>-->/i", "</i>", $string);
    	$string = preg_replace("/<!--<small( [^>]*)?>-->/i", "<small>", $string);
    	$string = preg_replace("/<!--<\/small>-->/i", "</small>", $string);
    	$string = preg_replace("/<!--<ul( [^>]*)?>-->/i", "<ul>", $string);
    	$string = preg_replace("/<!--<\/ul>-->/i", "</ul>", $string);
    	$string = preg_replace("/<!--<li( [^>]*)?>-->/i", "<li>", $string);
    	$string = preg_replace("/<!--<\/li>-->/i", "</li>", $string);
    	$string = preg_replace("/<!--<img (?:[^>]+ )?src=\"?([^\"]*)\"?[^>]*>-->/i", "<img src=\"\\1\" />", $string);
    	$string = preg_replace("/<!--<a (?:[^>]+ )?href=\"?([^\"]*)\"?[^>]*>-->/i", "<a href=\"\\1\" />", $string);
    	$string = preg_replace("/<!--<\/a>-->/i", "</a>", $string);

    	echo $string;  //return value - string containing all the markup for the misspelled words.
    	return;
	}

}




/*************************************************************
 * flattenArray($array)
 *
 * The flattenArray function is a recursive function that takes a
 * multidimensional array and flattens it to be a one-dimensional
 * array.  The one-dimensional flattened array is returned.
 *
 * $array - The array to be flattened.
 *
 *************************************************************/
function flattenArray($array)
{
	$flatArray = array();
	foreach ($array as $subElement) {
    	if (is_array($subElement)) {
			$flatArray = array_merge($flatArray, flattenArray($subElement));
		} else {
			$flatArray[] = $subElement;
		}
	}

	return $flatArray;
}


/*************************************************************
 * removeMicrosoftWordCharacters($t)
 *
 * This function strips out all the crap that Word tries to
 * add to it's text in the event someone pastes in code from
 * Word.
 *
 * $t - The text to be cleaned
 *
 *************************************************************/
function removeMicrosoftWordCharacters($t)
{
	$a=array(
        	"\xe2\x80\x9c"=>'"',
        	"\xe2\x80\x9d"=>'"',
        	"\xe2\x80\x99"=>"'",
        	"\xe2\x80\xa6"=>"...",
        	"\xe2\x80\x98"=>"'",
        	"\xe2\x80\x94"=>"---",
        	"\xe2\x80\x93"=>"--",
        	"\x85"=>"...",
        	"\221"=>"'",
        	"\222"=>"'",
        	"\223"=>'"',
        	"\224"=>'"',
        	"\x97"=>"---",
        	"\x96"=>"--"
	       );

	foreach ($a as $k=>$v) {
		$oa[]=$k;
		$ra[]=$v;
	}

	$t=trim(str_replace($oa,$ra,$t));
	return $t;

}

?>
