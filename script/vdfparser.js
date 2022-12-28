/** tries to unify access to members where you can not be certain wether they are repeated or not. tries to auto-merge keys.
 * if keys repeat like object.sub.key=value where sub and key are doubled, later occurances of .sub.key will overwrite earlier isntances.
 * later in this context refers to the internal object representation, not a parsed order
 */
const vdfGetUnified = (jso, key, asArray) => {
    if (!(jso)) return asArray ? [] : null;
    if (key in jso) {
        let val = jso[key];
        if (Array.isArray(val)) {
            if (asArray) return val;
            else { //merge entries
                let flat={};
                //merge all child {} kvs into one kv
                val.forEach(elem=>{
                    if (typeof elem === "object") {
                        Object.entries(elem).forEach(([subkey,subvalue])=>{flat[subkey]=subvalue;})
                    } else {
                        flat['___value___\n']=elem;
                    }
                });
                //if there's only one plain value, return that without map wrapper
                if (Object.keys(flat).length == 0) return null;
                else if (Object.keys(flat).length == 1 && ('___value___\n' in flat)) return flat['___value___\n'];
                else return flat;
            }
        } else {
            if (asArray) return [val];
            else return val;
        }
    }
}
/** converts a VDF string into a JavaScript Object
 * repeated keys are read as arrays
 * carefull, you need to match the escape option to your dataset or parsing might break
 * @param data the VDF structure as string
 * @param escaped true to parse escape sequences, false otherwise
 * @return [object Object] or false
 */
const vdfToJso = (data,escaped) => {

    /** left-trim spaces and c-comments */
    let ltrimex = (data) => {
        return data.replace(/^(\s+|[/]([/].*?[\r\n]+|[*].*?[*][/]))*/s, '');
    }
    let getString = (data,escaped) => {
        if (data.length == 0) {
            return false;
        }
        if (data[0] == '"') {
            let token = '';
            for (let i=1; i<data.length; i+=1) {
                if (escaped && data[i]=='\\') {
                    i+=1;
                    if (data[i] == 'r') token+='\r';
                    else if (data[i] == 'n') token+='\n';
                    else if (data[i] == 't') token+='\t';
                    else token+=data[i];
                } else if (data[i] == '"') {
                    i+=1; //skip closing quote
                    let right = (i<data.length) ? data.slice(i) : '';
                    return [token, right];
                } else token+=data[i];
            }
            return false; //run into eot
        } else {
            let found = data.match(/^[^\s]+/s);
            if (found.length != 1) return false;
            let right = (found[0].length<data.length) ? data.slice(found[0].length) : '';
            return [found[0],right];
        }
    }
    let getToken = (data) => {
        if (data[0] == '{') return '{';
        else if (data[0] == '}') return '}';
        else return false;
    }
    let addTo = (object, key, value) => {
        if (key in object) {
            //duplicate key = array
            if (Array.isArray(object[key])) {
                //key is already array, append
                object[key].concat(value);
            } else {
                //make key array
                object[key]=[object[key],value];
            }
        } else {
            //add as direct value
            object[key]=value;
        }
        return object;
    }
    let getKV = (data,escaped,issub) => {
        let token;
        let key;
        let retval = {};
        
        data = ltrimex(data);
        if (data.length == 0) return false;
        while (true) {

            token = getToken(data);
            if (token == '}' && issub) { console.log("object end"); return [retval, data.slice(1)]; } //block end
            else if (token !== false) return false; //invalid token
            
            let result = getString(data, escaped);
            if (!result) return false;
            else { key = result[0]; data = ltrimex(result[1]); }
            console.log("key",key);
            
            if (data.length == 0) return false; //no value
            token = getToken(data);
            if (token == '{') {
                console.log("object");
                data = data.slice(1);

                result = getKV(data, escaped, true);
                if (result === false) return false; //parse error in sub key
                else {
                    retval=addTo(retval,key,result[0]);
                    data = result[1];
                }
            } else if (token !== false) return false; //invalid token
            else {
                result = getString(data, escaped);
                if (result === false) return false;
                retval=addTo(retval,key,result[0]);
                console.log("value", result[0]);
                data = result[1];
            }

            data = ltrimex(data);
            if (data.length == 0) {
                if (issub) return false; //eot in sub-block
                else return [retval,''];
            }

        }

    }

    let result = getKV(data, escaped, false);
    if (result === false) return false;
    else return result[0];

}