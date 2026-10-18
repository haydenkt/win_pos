'use strict';
const $=id=>document.getElementById(id);
let db,account=null,csrf='',current=null,photos=[],dirty=false,busy=false,photoBusy=false;
const message=text=>{$('message').textContent=text;};
const openDB=()=>new Promise((resolve,reject)=>{const r=indexedDB.open('win-pos-survey-mobile-v1',1);r.onupgradeneeded=()=>{r.result.createObjectStore('drafts',{keyPath:'id'});r.result.createObjectStore('meta');};r.onsuccess=()=>resolve(r.result);r.onerror=()=>reject(r.error);});
function dbOp(store,mode,action){return new Promise((resolve,reject)=>{const tx=db.transaction(store,mode);const r=action(tx.objectStore(store));let result;r.onsuccess=()=>{result=r.result;};tx.oncomplete=()=>resolve(result);tx.onerror=()=>reject(tx.error);tx.onabort=()=>reject(tx.error||new Error('Local save aborted'));});}
const put=r=>dbOp('drafts','readwrite',s=>s.put(r));
const all=()=>dbOp('drafts','readonly',s=>s.getAll());
function uid(){const bytes=new Uint8Array(16);crypto.getRandomValues(bytes);return Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join('');}
function localDate(){const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;}
async function allowLeave(){return !dirty||await posDialog.confirm('You have unsaved changes. Leave without saving them?');}
function button(text,action){const b=document.createElement('button');b.type='button';b.textContent=text;b.onclick=()=>run(action);return b;}
async function run(action){try{await action();}catch(e){message(e.message||'Something went wrong. Your saved drafts have not been removed.');}}
async function request(options={}){
 const response=await fetch('api.php',{cache:'no-store',credentials:'same-origin',...options,signal:AbortSignal.timeout(45000)});
 let data;try{data=await response.json();}catch{throw new Error('Could not read the server response. Keep your draft and retry.');}
 if(!response.ok){const e=new Error(data.error||'Server unavailable.');e.status=response.status;throw e;}return data;
}
function customerOptions(){
 $('customer').replaceChildren(new Option('New customer','0'));
 for(const c of account?.customers||[]) $('customer').add(new Option(`${c.name}${c.phone?' · '+c.phone:''}`,String(c.id)));
 $('account').textContent=account?`Phone workspace: ${account.name}. Customer list last refreshed ${new Date(account.updated).toLocaleString()}.`:'Connect once while online before creating drafts.';
 $('new').disabled=!account;
}
async function connect(){
 if(busy||photoBusy)return;if(!(await allowLeave()))return;
 message('Connecting…');const data=await request();
 account={id:Number(data.user_id),name:data.name,customers:data.customers,updated:Date.now()};csrf=data.csrf;
 await dbOp('meta','readwrite',s=>s.put(account,'account'));
 current=null;dirty=false;$('editor').hidden=true;customerOptions();await list();
 const persistent=await navigator.storage?.persist?.().catch(()=>false);
 message(`Connected. Drafts are kept on this device until you sync.${persistent?' Persistent storage granted.':' Export backups regularly; browser storage is not guaranteed.'}`);
}
function addMeasurement(data={}){
 const box=document.createElement('div');box.className='measurement';
 const fields=[['item_name','Item / location','text',100],['width','Width (mm)','number'],['height','Height (mm)','number'],['quantity','Quantity','number'],['glass_type','Glass type','text',100],['frame_color','Frame colour','text',100],['opening_type','Opening type','text',100],['notes','Measurement notes','text',5000]];
 const grid=document.createElement('div');grid.className='grid';
 for(const [key,title,type,max] of fields){const label=document.createElement('label');label.textContent=title;const input=document.createElement('input');input.dataset.field=key;input.type=type;input.value=data[key]??(key==='quantity'?1:'');if(max)input.maxLength=max;if(['item_name','width','height','quantity'].includes(key))input.required=true;if(type==='number'){input.min=key==='quantity'?1:0.01;input.max=key==='quantity'?10000:99999999.99;input.step=key==='quantity'?1:0.01;}label.append(input);grid.append(label);}
 box.append(grid,button('Remove measurement',()=>{if($('items').children.length===1)throw new Error('Keep at least one measurement.');box.remove();dirty=true;}));$('items').append(box);
}
function renderPhotos(){
 $('photos').replaceChildren();photos.forEach((src,index)=>{const box=document.createElement('div');box.className='photo';const img=document.createElement('img');img.src=src;img.alt=`Site photo ${index+1}`;box.append(img);if(current?.status==='draft')box.append(button('Remove photo',()=>{photos.splice(index,1);dirty=true;renderPhotos();}));$('photos').append(box);});
}
async function openRecord(record){
 if(busy||photoBusy||!(await allowLeave()))return;
 current=structuredClone(record);const d=current.data;photos=[...d.photos];customerOptions();
 $('customer').value=String(d.customer_id);if($('customer').value==='')$('customer').add(new Option(`Customer #${d.customer_id}`,String(d.customer_id),true,true));
 for(const key of ['name','phone','address','notes'])$(key).value=d[key]||'';$('date').value=d.survey_date;
 $('items').replaceChildren();d.items.forEach(addMeasurement);$('editor').hidden=false;dirty=false;renderPhotos();lockEditor();$('editor').scrollIntoView({behavior:'smooth'});
}
function lockEditor(){
 const locked=current?.status!=='draft';$('fields').disabled=locked||busy;$('save').disabled=locked||busy;$('sync').disabled=current?.status==='synced'||busy;$('sync').textContent=locked?'Retry sync':'Save & sync to POS';
 $('editorTitle').textContent=current?.status==='synced'?`Synced survey #${current.survey_id}`:locked?'Upload awaiting confirmation':'Survey draft';
 $('saveState').textContent=locked?'This submission is locked to make retries safe. Retry to confirm its server result.':'Press Save on this phone before leaving. Nothing uploads until you choose Sync.';
 if(current?.status==='synced')$('saveState').textContent='Saved in the desktop POS. This phone copy is retained as a backup.';
}
function capture(){
 if(photoBusy)throw new Error('Wait for the photos to finish processing.');
 const data={id:current.id,owner:current.owner,customer_id:Number($('customer').value),name:$('name').value.trim(),phone:$('phone').value.trim(),survey_date:$('date').value,address:$('address').value.trim(),notes:$('notes').value.trim(),items:[],photos:[...photos]};
 for(const box of $('items').children){const row={};box.querySelectorAll('[data-field]').forEach(input=>{row[input.dataset.field]=input.value;});data.items.push(row);}
 return data;
}
async function save(){
 if(!current||current.status!=='draft')return;
 const record=current,data=capture();await put({...record,data,updated:Date.now()});if(current===record){current.data=data;dirty=false;$('saveState').textContent='Saved on this phone. Not yet uploaded.';}await list();message('Local draft saved.');
}
async function sync(){
 if(busy||photoBusy||!current||current.status==='synced')return;
 if(current.status==='draft'&&!$('editor').reportValidity())return;
 busy=true;lockEditor();
 try{
  if(current.status==='draft')await save();
  const auth=await request();csrf=auth.csrf;
  if(Number(auth.user_id)!==current.owner)throw new Error('Sign in using the account that created this draft.');
  if(current.status==='draft'){
   current={...current,status:'pending',body:JSON.stringify(current.data)};
   await put(current);lockEditor();
  }
  message('Uploading… Keep this page open.');
  const result=await request({method:'POST',headers:{'Content-Type':'application/json','X-Survey-CSRF':csrf},body:current.body});
  current={...current,status:'synced',survey_id:result.survey_id};await put(current);dirty=false;
  message(`Synced successfully as survey #${result.survey_id}. Measurements and photos are available in the desktop POS.`);
 }catch(e){
  if(e.status===422&&current.status==='pending'){current.status='draft';delete current.body;await put(current);}
  throw e;
 }finally{busy=false;lockEditor();await list();}
}
async function list(){
 $('drafts').replaceChildren();const records=(await all()).filter(r=>r.owner===account?.id).sort((a,b)=>b.updated-a.updated);
 for(const r of records){const box=document.createElement('div');box.className='draft';const title=document.createElement('strong');title.textContent=`${r.data.name||'Customer #'+r.data.customer_id} · ${r.data.survey_date}`;const detail=document.createElement('p');detail.textContent=r.status==='synced'?`Synced · survey #${r.survey_id}`:r.status==='pending'?'Awaiting upload confirmation — retry safely':'Saved on this phone only';box.append(title,detail,button(r.status==='synced'?'View phone copy':'Open / sync',()=>{if(busy)return;return openRecord(r);}));if(r.status==='synced'){const link=document.createElement('a');link.textContent='Open in POS';link.href=`../view.php?id=${Number(r.survey_id)}`;link.style.marginLeft='12px';box.append(link);} $('drafts').append(box);}
 if(!records.length)$('drafts').textContent='No drafts for this account on this device.';
}
async function resizePhoto(file){
 if(file.size>25000000)throw new Error('Choose a photo under 25 MB.');
 const url=URL.createObjectURL(file);try{
  const img=new Image();img.src=url;await img.decode();const scale=Math.min(1,1600/img.naturalWidth,1600/img.naturalHeight);const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.round(img.naturalWidth*scale));canvas.height=Math.max(1,Math.round(img.naturalHeight*scale));const ctx=canvas.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.drawImage(img,0,0,canvas.width,canvas.height);let quality=.8,src;do{src=canvas.toDataURL('image/jpeg',quality);quality-=.1;}while(src.length>800000&&quality>.2);if(src.length>800000)throw new Error('Photo is too detailed. Try a smaller image.');return src;
 }finally{URL.revokeObjectURL(url);}
}
$('connect').onclick=()=>run(connect);
$('new').onclick=()=>run(async()=>{if(busy||photoBusy||!(await allowLeave()))return;dirty=false;const id=uid();await openRecord({id,owner:account.id,status:'draft',updated:Date.now(),data:{id,owner:account.id,customer_id:0,name:'',phone:'',address:'',notes:'',survey_date:localDate(),items:[{}],photos:[]}});await save();});
$('editor').oninput=()=>{if(current?.status==='draft')dirty=true;};
$('editor').onsubmit=e=>{e.preventDefault();run(save);};
$('sync').onclick=()=>run(sync);
$('close').onclick=async()=>{if(busy||photoBusy)return;if(await allowLeave()){$('editor').hidden=true;current=null;dirty=false;}};
$('addItem').onclick=()=>{if($('items').children.length>=100)return message('Maximum 100 measurements.');addMeasurement();dirty=true;};
$('customer').onchange=()=>{const c=account.customers.find(c=>String(c.id)===$('customer').value);$('name').value=c?.name||'';$('phone').value=c?.phone||'';if(c)$('address').value=c.address||'';dirty=true;};
$('photoInput').onchange=()=>run(async()=>{if(photoBusy)return;photoBusy=true;try{for(const file of $('photoInput').files){if(photos.length>=6)throw new Error('Maximum six photos.');photos.push(await resizePhoto(file));dirty=true;renderPhotos();}}finally{photoBusy=false;$('photoInput').value='';}});
$('export').onclick=()=>run(async()=>{if(busy||photoBusy)return;if(dirty)await save();const records=(await all()).filter(r=>r.owner===account?.id);const blob=new Blob([JSON.stringify({format:'win-pos-survey-backup-v1',records})],{type:'application/json'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=`survey-backup-${localDate()}.json`;a.click();setTimeout(()=>URL.revokeObjectURL(url),10000);message('Backup exported. Keep it private; it contains customer information and photos.');});
$('import').onchange=()=>run(async()=>{if(busy)return;if(!account)throw new Error('Connect with your account before importing.');const file=$('import').files[0];if(!file)return;if(file.size>50000000)throw new Error('Backup too large (maximum 50 MB).');const backup=JSON.parse(await file.text());if(backup.format!=='win-pos-survey-backup-v1'||!Array.isArray(backup.records))throw new Error('Not a survey backup.');const existing=new Set((await all()).map(r=>r.id));let added=0;for(const r of backup.records){if(r.owner!==account.id||existing.has(r.id))continue;if(!/^[a-zA-Z0-9-]{16,64}$/.test(r.id)||!r.data||r.data.id!==r.id||r.data.owner!==r.owner||!Array.isArray(r.data.items)||r.data.items.length>100||!Array.isArray(r.data.photos)||r.data.photos.length>6||r.data.photos.some(p=>typeof p!=='string'||!/^data:image\/jpeg;base64,[A-Za-z0-9+/=]+$/.test(p)||p.length>800000)||!['draft','pending','synced'].includes(r.status))throw new Error('Invalid backup record.');if(r.status!=='draft'&&(typeof r.body!=='string'||r.body.length>6000000||JSON.stringify(JSON.parse(r.body))!==JSON.stringify(r.data)))throw new Error('Invalid frozen submission.');await put(r);existing.add(r.id);added++;}await list();message(`Imported ${added} drafts. Existing drafts and other accounts were not overwritten.`);$('import').value='';});
window.addEventListener('beforeunload',e=>{if(dirty||busy||photoBusy){e.preventDefault();e.returnValue='';}});
window.addEventListener('online',()=>message('Network available. Connect to the factory POS and choose Sync when ready.'));
window.addEventListener('offline',()=>message('Offline. Save drafts on this phone; sync when back at the factory.'));
(async()=>{
 try{db=await openDB();account=await dbOp('meta','readonly',s=>s.get('account'));customerOptions();await list();}
 catch(e){message('Local storage is unavailable. Do not enter surveys until browser storage is enabled.');return;}
 if(!isSecureContext||!('serviceWorker'in navigator)){$('offlineState').textContent='Offline reopening unavailable on this address. Use trusted HTTPS for phone installation. Keep this tab open and export backups for temporary testing.';return;}
 try{await navigator.serviceWorker.register('sw.js',{scope:'./'});await navigator.serviceWorker.ready;$('offlineState').textContent='Offline app ready. Install / Add to Home Screen from your browser menu. Connect once in the installed app before going offline.';}
 catch(e){$('offlineState').textContent='Offline preparation failed. Reconnect and reload before leaving the factory.';}
})();
