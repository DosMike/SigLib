
const processCommentForm = (form) => {
    Request.form(form).then((result)=>{
        let response = JSON.parse(result.responseText);
        let cmnt = document.createElement('p');
            cmnt.classList.add('comment');
            let hdr = document.createElement('span');
                let usr = document.querySelector('header>span>a').cloneNode(true);
                    usr.classList.add('inlineuser');
                    hdr.appendChild(usr);
                hdr.appendChild(new Text( ' - Just Now'));
                let del = document.createElement('button');
                    del.innerText = 'Delete';
                    del.classList.add('critical');
                    del.dataset.type = form.type.value;
                    del.dataset.id = response['ID'];
                    del.addEventListener('click', deleteComment);
                    hdr.appendChild(del);
                cmnt.appendChild(hdr);
            let msg = document.createElement('span');
                msg.innerHTML = response['HTML'];
                cmnt.appendChild(msg);
        let group = form.nextElementSibling; //comment group after form
        let latest = group.firstElementChild; //most recent comment
        group.insertBefore(cmnt, latest);
        if (!latest.classList.contains('comment')) group.removeChild(latest);
        form.querySelector('textarea').value = "";
    }, (error)=>{
        try { alerty.alert(JSON.parse(error.responseText).Error); }
        catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
    })
}

const processDeleteComment = (button) => {
    Request.POST('action.php?do=delcomment', {}, { 'type': button.dataset.type, 'id': button.dataset.id })
        .then((result)=>{
            let comment = button.parentElement.parentElement; //button->header->comment
            comment.innerHTML = '<p>'+((Math.floor(Math.random()*100)!=0) ? 'Deleted' : 'Caroline deleted.')+'</p>';
        }, (error)=>{
            try { alerty.alert(JSON.parse(error.responseText).Error); }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        })
}

const processDupe = (button) => {
    let dupes = parseInt(button.innerText.split('|')[1].trim());
    let make = !button.classList.contains('in');
    let type = 'symbol' in button.dataset ? 'symbol' : 'value';
    let id = button.dataset[type];
    
    Request.POST('action.php?do=ratemod', {}, { 'type': type, 'id': id, 'dupes': make?'make':'destroy' })
        .then((result)=>{

            dupes += make?1:-1;
            button.innerText = button.innerText.split('|')[0] + '| ' + dupes;
            if (type == 'value') {
                //go up to entry container to update counter in table view
                let content = button;
                while (!content.classList.contains('entry')) content = content.parentElement;
                content.querySelector('[name="dupes"]').innerText = dupes;
            }

            let dupers = document.querySelector('[data-target="'+type[0]+id+'"].dupeslist');
            if (make) {
                button.classList.add('in');

                let meduper = document.querySelector('header>span>a').cloneNode(true);
                meduper.classList.add('inlineuser');
                dupers.appendChild(meduper);
            } else {
                button.classList.remove('in');

                let meid = parseInt(document.querySelector('header>span>a').dataset.user);
                let meduper = dupers.querySelector('[data-user="'+meid+'"].inlineuser');
                if (meduper) dupers.removeChild(meduper);
            }
            
        }, (error)=>{
            try {
                let jresponse = JSON.parse(error.responseText);
                if ('Deleted' in jresponse) {
                    if (type == 'symbol') {
                        let content = document.querySelector('content');
                        content.innerHTML = content.firstElementChild.outerHTML + "<p>"+jresponse.Error+"</p>" ;
                    } else {
                        let content = button;
                        while (!content.classList.contains('valdetails')) content = content.parentElement;
                        content.innerHTML = "<p>"+jresponse.Error+"</p>" ;
                    }
                }
                alerty.alert(jresponse.Error);
            }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        })
}

const processRating = (button) => {
    let bnUp = button;
    if (bnUp.id == 'bnRateDown') bnUp = bnUp.previousElementSibling;
    let bnDown = bnUp.nextElementSibling;

    let score = parseInt(bnUp.innerText.split('|')[1].trim());
    let rate = (button.classList.contains('in')) ? 0 : ((button == bnUp) ? 1 : -1);
    let type = 'symbol' in button.dataset ? 'symbol' : 'value';
    let id = button.dataset[type];
    
    Request.POST('action.php?do=ratemod', {}, { 'type': type, 'id': id, 'rate': rate })
        .then((result)=>{

            if (bnUp.classList.contains('in')) { bnUp.classList.remove('in'); score-=1; }
            if (bnDown.classList.contains('in')) { bnDown.classList.remove('in'); score+=1; }

            score += rate;
            bnUp.innerText = bnUp.innerText.split('|')[0] + '| ' + score;
            if (type == 'value') {
                //go up to entry container to update counter in table view
                let content = button;
                while (!content.classList.contains('entry')) content = content.parentElement;
                content.querySelector('[name="score"]').innerText = score;
            }

            if (rate>0) bnUp.classList.add('in');
            else if (rate<0) bnDown.classList.add('in');

        }, (error)=>{
            try {
                let jresponse = JSON.parse(error.responseText);
                if ('Deleted' in jresponse) {
                    if (type == 'symbol') {
                        let content = document.querySelector('content');
                        content.innerHTML = content.firstElementChild.outerHTML + "<p>"+jresponse.Error+"</p>" ;
                    } else {
                        let content = button;
                        while (!content.classList.contains('valdetails')) content = content.parentElement;
                        content.innerHTML = "<p>"+jresponse.Error+"</p>" ;
                    }
                }
                alerty.alert(jresponse.Error);
            }
            catch (ignore) { alerty.alert('<a href="https://http.cat/'+error.status+'" target="_blank">' + error.status+' - '+error.statusText + '</a>'); }
        })
}

const postComment = (event) => {
    event.preventDefault();
    processCommentForm(event.target);
    return false;
}

const deleteComment = (event) => {
    alerty.confirm("Do you really want to delete this comment?", ()=>processDeleteComment(event.target));
}

const dupeElement = (event) => {
    processDupe(event.target);
}

const rateElement = (event) => {
    processRating(event.target);
}

const openDetails = (event) => {
    document.querySelectorAll('.valgrid .entry.open').forEach(elem=>elem.classList.remove('open'));
    let target = event.target;
    while (!target.classList.contains('entry')) target = target.parentElement;//go up to entry
    target.classList.add('open');
}

document.addEventListener('DOMContentLoaded', ()=>{
    if (typeof back2front !== 'undefined' && back2front['toast']) {
        alerty.toasts(back2front['toast']);
    }
    document.querySelectorAll('form.comment').forEach(form=>form.addEventListener('submit', postComment));
    document.querySelectorAll('div.commentgroup>.comment button.critical').forEach(button=>button.addEventListener('click', deleteComment));
    document.querySelectorAll('#bnDupe').forEach(button=>button.addEventListener('click', dupeElement));
    document.querySelectorAll('#bnRateUp').forEach(button=>button.addEventListener('click', rateElement));
    document.querySelectorAll('#bnRateDown').forEach(button=>button.addEventListener('click', rateElement));
    document.querySelectorAll('.valgrid .entry').forEach(entry=>entry.addEventListener('click', openDetails));
});