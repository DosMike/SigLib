const action_requestAPIKey = (make) => {

    alerty.confirm(make
        ?'You are about to generate a new API Key.<br>Please Keep the API Key Secret!'
        :'Do you really want to destroy your API Key?<br>Applications using it will no longer be able to authenticate!'
        , {okLabel: make?'Generate':'Destroy'},
         ()=>{

	Request.POST("action.php?do=apikey", {}, {'action':make?'generate':'destroy'})
		.then((result)=>{
            let bnGen = document.querySelector('#bnApiKeyGenerate');
            let bnDest = document.querySelector('#bnApiKeyDestroy');
            let container = bnGen.parentElement;
            let input = (container.querySelector('input'));
            let key = JSON.parse(result.responseText).apiKey;
            if (key) {
                if (!input) {
                    input=document.createElement('input');
                    input.type='text';
                    input.classList.add('copyfrom');
                    container.insertBefore(input,bnDest);
                }
                input.value=key;
                bnGen.style.display='none';
                bnDest.style.display='';

                alerty.toasts("Done! Pass the API Key with a Basic Authorization header", {time:5000});
            } else {
                if (input) container.removeChild(input);
                bnGen.style.display='';
                bnDest.style.display='none';

                alerty.toasts("Done! The API Key was deleted", {fontColor:'#c80000', time:5000});
            }
        }, (error)=>{
            try { alerty.alert(JSON.parse(error.responseText).Error); }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        });

    });
}
const action_updatePrivacy = (form) => {
    Request.form(form).then((response)=>{
        alerty.toasts("Settings Updated!");
    },(error)=>{
        try { alerty.alert(JSON.parse(error.responseText).Error); }
        catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
    });
}
const action_clearProfile = () => {
    alerty.confirm('Do you really want to delete all contributions? This can not be undone!', ()=>{
        Request.POST('action.php?do=clearprofile').then((result)=>{
            document.location.search='';
        },(error)=>{
            try { alerty.alert(JSON.parse(error.responseText).Error); }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        })
    })
}

document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelector('#bnApiKeyGenerate').addEventListener('click', (e)=>action_requestAPIKey(true));
    document.querySelector('#bnApiKeyDestroy').addEventListener('click', (e)=>action_requestAPIKey(false));
    document.querySelector('form#privacy').addEventListener('submit', (e)=>{e.preventDefault(); action_updatePrivacy(e.target); return false;});
    document.querySelector('#bnClearProfile').addEventListener('click', (e)=>action_clearProfile());
});