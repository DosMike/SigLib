const action_importGameData_confirmed = () => {
    document.querySelector('.upload').style.display='block';
    document.querySelector('.upload').style.opacity=1;
    document.querySelector('.upload .progressbar').style.opacity=1;
    document.querySelector('.upload .progressbar .progress').style.width='50%';
	Request.POST("action.php?do=import", {}, document.querySelector('#uploadForm'))
		.then((result)=>{
            document.querySelector('.upload .progressbar .progress').style.width='100%';
            let inserted = JSON.parse(result.responseText);
            console.log('Import result on',document.querySelector('#uploadForm input[type="file"]').value,':',inserted);
            alerty.alert('<h3>Import completed</h3><small><br></small>'+
            'Check the web console for a detailed report.');
        }, (error)=>{
            document.querySelector('.upload .progressbar .progress').style.width='0%';
            try { alerty.alert(JSON.parse(error.responseText).Error); }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        }).finally(()=>{
            document.querySelector('.upload').style.opacity=0;
            window.setTimeout(()=>{
                document.querySelector('.upload').style.display='none';
                document.querySelector('.upload .progressbar').style.opacity=0;
                document.querySelector('.upload .progressbar .progress').style.width='0%';
            }, 200);
        });
}

const action_importGameData = () => {

    if (document.querySelector('#uploadForm input[name=gamedata]').files.length != 1 && document.querySelector('#uploadForm input[name=gamedata]').files[0].size <= 0) {
        alerty.alert('You did not select a file for upload');
        return;
    }
    if (document.querySelector('#uploadForm input[name=gamedata]').files[0].type != "text/plain") {
        alerty.alert('The file you have selected for upload is not a text file.<br>Please upload a gamedata file.');
        return;
    }
    // check if all prompted versions are OK / set: filter for empty version inputs and count
    let allOK = Array.from(document.querySelectorAll('.gamever')).filter((e)=>e.style.display!='none').map((e)=>e.querySelector('input')).filter((e)=>!e.value.trim()).length === 0;
    if (allOK) {
        action_importGameData_confirmed();
    } else {
        alerty.confirm('You did not specify a valid version.<br>Please use the server version from the drop down or check your <code>tf/steam.inf</code>-file.', {'bnOk':'Upload Anyway'}, action_importGameData_confirmed);
    }
}
const action_updateVersionList = (fileset) => {
    Array.from(document.querySelectorAll('#uploadForm .gamever')).forEach(e=>e.style.display='none');
    if (fileset.length==0) return;
    fileset[0].text().catch(e=>{return{};}).then(text=>vdfToJso(text)).then(gdata=>{
        if (gdata === false) {
            alerty.alert("The file you've choosen could not be parsed!");
            return;
        }
        gdata = vdfGetUnified(gdata, 'Games', false);
        if (gdata == null) return;
        console.log (gdata);
        Object.keys(gdata).map(game=>[game,vdfGetUnified(gdata, game, false)]).forEach(([game,data]) => {
            if (game != '#default') try { //try because querySelector might fail
                let versel = document.querySelector('#uploadForm .gamever.'+(game.replace(/^[$#]*/,'')));
                let offsets = vdfGetUnified(data, 'Offsets', false);
                let signatures = vdfGetUnified(data, 'Signatures', false);
                if (((offsets != null && Object.keys(offsets).length>0) || (signatures != null && Object.keys(signatures).length>0)) && versel) {
                    versel.style.display = 'flex';
                }
            } catch (except) {}
        });
    });
}
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelector('#uploadForm').onsubmit = (e)=>{ e.preventDefault(); action_importGameData(); return false; }
    document.querySelector('#uploadForm input[name=gamedata]').onchange = (e)=>{ action_updateVersionList(e.target.files); }
});