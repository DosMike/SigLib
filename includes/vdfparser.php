<?php

/**
 * Write out vdf data. In contrast to json, VDF has a root key instead of a root container.
 * in most cases there is on root key, used for validation, but there can be multiple root entries.
 * This function will only write one root section, but you can simply concat multiple outputs from
 * this function with a line break if you need to.
 * @param key    the root key
 * @param value  the data to serialize
 * @param escape if true quotes, backslashes and line breaks are not escaped. disabled by default (in the game as well)
 * @param indent do not use
 * @return string stringified rep
 */
function write_vdf($key, $value, $escape=false, $indent=0) {
	if (is_array($value)) {
		if (array_is_list($value)) {
			// arrays are written out as repeated keys (see valve map files)
			foreach ($value as $element) {
				write_vdf($key, $element, $escape, $indent);
			}
		} else {
			echo str_repeat(' ',$indent).vdfQuoteString($key, $escape)." {\n";
			foreach ($value as $subkey=>$element) {
				write_vdf($subkey, $element, $escape, $indent+1);
			}
            echo str_repeat(' ',$indent)."}\n";
		}
	} else if ($value != null) {
        echo str_repeat(' ',$indent).vdfQuoteString($key, $escape).' '.vdfQuoteString($value, $escape)."\n";
	}
}

function vdfQuoteString($value, $escape=false) {
    if (empty($value)) return '""';
	return '"'.str_replace(["\\", "\n", "\r", "\""],["\\\\","\\n","\\r","\\\""],$value).'"';
}

/**
 * parse a value keyvalue file into a php array
 * @param data    input string of data
 * @param escapes wether to parse escape sequences (\") or not. not parsing escaped will break parsing if a string contains quotes.
 * @return array|false  with the parsed vdf data or false if parsing failed
 */
function parse_vdf($data, $escapes=false) {
    $fptr = fopen("php://memory", "r+");
    fputs($fptr, $data);
    rewind($fptr);

    $data = parse_vdfkv($fptr, $escapes);

    fclose($fptr);
    return $data;
}

/** internal parsing function, but can be used to parse arbitrary file streams. see parse_vdf for args. */
function parse_vdfkv($fptr, $escapes=false, $issub=false) {
    // echo "::parse_vdfkv\n";
    $kv=[];

    while (!feof($fptr)) {
        while (_fseeks($fptr) || _fskipcomment($fptr)) /* skip */;
        if (feof($fptr)) break; // no key to expect, we reached EOF after trailing white spaces
        //end of objects for non-root objects appear in a key-position
        if (_fpeek($fptr) == '}') {
            if ($issub) {
                fgetc($fptr);
                return $kv;
            } else {
                // echo "} without {\n";
                return false;
            }
        }
        //If it's not the end of a block, read key instead
        $key = _fgetsvdf($fptr,$escapes);
        if ($key === false) {
            // echo "Could not read key\n";
            return false; //idk
        }
        while (_fseeks($fptr) || _fskipcomment($fptr)) /* skip */;
        if (feof($fptr)) {
            // echo "Unexpected EOF after key $key\n";
            return false;
        }
        $c = _fpeek($fptr);
        if ($c === false) {
            // echo "Could not peek value type for key $key\n";
            return false;
        } elseif ($c == '{') {
            fgetc($fptr); //take {
            $value = parse_vdfkv($fptr,$escapes,true); //parse sub keys
        } elseif ($c == '}') {
            // echo "Unexpected }\n";
            return false;
        } else {
            $value = _fgetsvdf($fptr,$escapes); //plain kv
        }
        if ($value === false) {
            // echo "Could not read value for key $key\n";
            return false; //error reading value
        }
        // handle array values that usually duplicate keys when writing out (see valve map files)
        if (array_key_exists($key, $kv)) {
            if (is_array($kv[$key])) $kv[$key][] = $value; //append array if we already converted
            else $kv[$key]=[$kv[$key],$value]; //convert the value to an array and append
        } else {
            $kv[$key] = $value;
        }
    }
    if ($issub) {
        // echo "{ without }\n";
        return false;
    }
    return $kv;
    
}

function _fpeek($fptr) {
    // echo "::_fpeek\n";
    if (feof($fptr)) return false;
    $c = fgetc($fptr);
    fseek($fptr, -1, SEEK_CUR);
    return $c;
}

/** skip over space characters */
function _fseeks($fptr) {
    $skipped=0;
    // echo "::_fseeks\n";
    while (($c = fgetc($fptr))!==false) {
        if (!ctype_space($c)) {
            fseek($fptr,-1,SEEK_CUR);
            return $skipped;
        }
        $skipped++;
    }
    return false; //was eof
}
/** skip c-style comments */
function _fskipcomment($fptr) {
    // echo "::_fskipcomment\n";
    if (feof($fptr)) return false;
    $c = fgetc($fptr);
    if ($c != '/' || feof($fptr)) { fseek($fptr, -1, SEEK_CUR); return false; }
    
    $c = fgetc($fptr);
    if ($c == '/') { // line comment
        if (!feof($fptr)) fgets($fptr);
        return true;
    }
    elseif ($c != '*') { // not a block comment
        fseek($fptr, -2, SEEK_CUR);
        return false;
    }
    //read until block comment ends */
    $prev=false;
    while (($c = fgetc($fptr))!==false) {
        if ($prev && $c == '/') {
            return true;
        }
        $prev = ($c == '*');
    }
    return false;
}
/**
 * get the next VDF valid string (unqoted without space or otherwise quoted)
 * assumes that the file cursor is at the token.
 * if a token failes to read, the file pointer will be at an arbitrary position within the stream.
 * @param fptr the file pointer
 * @param escapes if false, \ will be treated as literal. if true \\ \r \n \t \" are parsed correctly.
 *        this means that quotes in strings without the escape option will break the parser.
 * @return string|false a string if a token was found, false otherwise.
 */
function _fgetsvdf($fptr, $escapes=false) {
    // echo "::_fgetsvdf\n";
    if (feof($fptr)) return false;
    $c = fgetc($fptr);
    $token = '';
    if ($c == '"') {
        for (;!feof($fptr);) {
            $c = fgetc($fptr);
            if ($c == '\\' && $escapes) {
                if (feof($fptr)) {
                    // echo "Incomplete escape before EOF\n";
                    return false;
                }
                $c = fgetc($fptr);
                if ($c == 'r') $token.="\r";
                elseif ($c == 'n') $token.="\n";
                elseif ($c == 't') $token.="\t";
                elseif ($c == '\\') $token.="\\";
                elseif ($c == '"') $token.="\"";
                else $token.="\\$c"; //not an escape, push both
            } elseif ($c == '"') {
                // echo '"'.$token."\"\n";
                return $token;
            } else {
                $token.=$c;
            }
        }
        // echo "Unterminated string\n";
    } elseif (!(ctype_space($c) || ctype_cntrl($c))) {
        $token.=$c;
        for (;!feof($fptr);) {
            $c = fgetc($fptr);
            if (ctype_space($c)) { 
                fseek($fptr,-1,SEEK_CUR);
                break;
            }
            else $token.=$c;
        }
        if ($token == '}' || $token == '{') { //this is not a valid unqoted token
            fseek($fptr,-1,SEEK_CUR);
            return false;
        }
        // echo '"'.$token."\"\n";
        return $token;
    } //else echo "Invalid cahr '$c'\n";
    return false;
}
