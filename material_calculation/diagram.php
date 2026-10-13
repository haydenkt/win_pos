<style>
 .window-diagram svg { display:block; width:100%; max-height:460px; background:var(--surface); border:1px solid var(--ink-200); border-radius:14px; }
 .window-diagram svg text { fill:var(--ink-900); font:14px system-ui,sans-serif; }
 .window-diagram-legend { display:flex; flex-wrap:wrap; gap:8px 16px; margin-top:14px; font-size:.85rem; }
 .window-diagram-legend span { display:inline-flex; gap:6px; align-items:center; }
 .window-diagram-legend i { display:inline-block; width:18px; height:6px; border:1px solid #999; border-radius:3px; }
 [data-theme="dark"] .window-diagram [data-code="72803"] { stroke:#e2e8f0; }
 [data-theme="dark"] .window-diagram [data-code="798MD29"] { stroke:#e2e8f0; }
 [data-theme="dark"] .window-diagram .diagram-note { color:#fff !important; }
</style>
<div class="card mt-4 window-diagram" id="windowDiagram" hidden>
 <div class="card-header"><h2 class="h5 mb-1">Adjust your window</h2><small id="diagramDesignLabel">728 Slide · two top fixed panels and two sliding leaves</small></div>
 <div class="card-body">
  <div class="row g-2 mb-3 no-print">
   <div class="col-12"><label for="diagramSize" class="form-label">Drawing for size</label><select id="diagramSize" class="form-select"></select></div>
   <div class="col-6"><label for="diagramWidth" class="form-label">Width (ft)</label><input id="diagramWidth" type="number" min="0.5" step="0.5" class="form-control"></div>
   <div class="col-6"><label for="diagramHeight" class="form-label">Overall height (ft)</label><input id="diagramHeight" type="number" min="0.5" step="0.5" class="form-control"></div>
   <div class="col-6"><label for="diagramHeading" class="form-label">Top height (ft)</label><input id="diagramHeading" type="number" min="0.5" step="0.5" class="form-control"></div>
   <div class="col-6"><label for="diagramQuantity" class="form-label">Quantity</label><input id="diagramQuantity" type="number" min="1" step="1" class="form-control"></div>
  </div>
  <div id="diagramWarning" class="alert alert-warning" role="status" hidden></div>
  <div id="diagramCanvas"></div>
  <div class="window-diagram-legend" id="diagramLegend"></div>
  <p class="small text-body-secondary mt-3 mb-0 diagram-note">Colors match your drawing. Black is shown as light gray in dark mode. Line offsets are for clarity, not cutting deductions. Totals combine all sizes; pieces are estimated from total length, not a cutting layout.</p>
 </div>
</div>
<script>
window.winPos728Diagram = (() => {
 const panel = document.getElementById('windowDiagram');
 const selector = document.getElementById('diagramSize');
 const canvas = document.getElementById('diagramCanvas');
 const warning = document.getElementById('diagramWarning');
 const colors = {'72803':'#222222','72802D':'#ef4444','72809':'#eab308','72801A':'#22c55e','72808':'#3b82f6','72804':'#f97316','72807':'#8b5cf6','72806':'#06b6d4','72816':'#d946ef','72812':'#a3b820'};
 const colorNames = {'72803':'Black','72802D':'Red','72809':'Yellow','72801A':'Green','72808':'Blue','72804':'Orange','72807':'Purple','72806':'Cyan','72816':'Magenta','72812':'Lime'};
 const fields = {diagramWidth:'.size-width',diagramHeight:'.size-height',diagramHeading:'.size-heading-height',diagramQuantity:'.size-quantity'};
 let rows = [], currentTemplate = null;
 const matches = template => ['728 Slide with Top Fixed Panels', '38 with Head'].includes(template?.name);
 const valid = row => {
  const n = selector => Number(row.querySelector(selector).value);
  return n('.size-width') > 0 && n('.size-height') > 0 && n('.size-quantity') >= 1 && Number.isInteger(n('.size-quantity')) && row.querySelector('.size-has-heading').checked && n('.size-heading-height') > 0 && n('.size-heading-height') < n('.size-height');
 };
 const svgNode = (tag, attrs, text) => {
  const node = document.createElementNS('http://www.w3.org/2000/svg',tag);
  Object.entries(attrs).forEach(([key,value]) => node.setAttribute(key,value));
  if (text !== undefined) node.textContent = text;
  return node;
 };
 function render(template, sizeRows) {
  currentTemplate = template; rows = [...sizeRows]; panel.hidden = !matches(template);
  if (panel.hidden) return;
  const is38 = template.name === '38 with Head';
  const drawingColors = is38 ? {'798MD29':'#222222','798MD30':'#ef4444','79823':'#22c55e','868 B16C':'#eab308','798MD14':'#3b82f6'} : colors;
  const drawingNames = is38 ? {'798MD29':'Black','798MD30':'Red','79823':'Green','868 B16C':'Yellow','798MD14':'Blue'} : colorNames;
  document.getElementById('diagramDesignLabel').textContent = is38 ? '38 with Head · two top fixed panels and two lower leaves' : '728 Slide · two top fixed panels and two sliding leaves';
  const index = Math.min(Math.max(0, Number(selector.value) || 0), rows.length - 1);
  selector.replaceChildren(...rows.map((row,i) => new Option(`Size ${i+1}`,String(i))));
  selector.value = String(index);
  const row = rows[index]; if (!row) return;
  Object.entries(fields).forEach(([id,css]) => {
   const input = document.getElementById(id);
   if (document.activeElement !== input) input.value = row.querySelector(css).value;
  });
  const invalid = rows.map((r,i) => valid(r) ? null : i+1).filter(Boolean);
  warning.hidden = !invalid.length;
  warning.textContent = `Check size ${invalid.join(', ')}: use positive dimensions, a whole quantity, and enable a top heading shorter than the overall height. Invalid sizes are excluded from totals.`;
  canvas.replaceChildren();
  const legend = document.getElementById('diagramLegend'); legend.replaceChildren();
  Object.entries(drawingColors).forEach(([code,color]) => {
   const label = document.createElement('span'), swatch = document.createElement('i');
   swatch.style.background = color; label.append(swatch,`${code} · ${drawingNames[code]}`); legend.append(label);
  });
  if (!valid(row)) return;
  const w = Number(row.querySelector('.size-width').value), h = Number(row.querySelector('.size-height').value), top = Number(row.querySelector('.size-heading-height').value);
  const scale = Math.min(300/w,340/h), left = 75+(300-w*scale)/2, right = left+w*scale, y = 52, bottom = y+h*scale, split = y+top*scale, mid=(left+right)/2;
  const svg = svgNode('svg',{viewBox:'0 0 470 455',role:'img','aria-label':`${template.name}, size ${index+1}: ${w} feet wide, ${h} feet high, ${top} feet top fixed panels, ${h-top} feet lower section`});
  const line = (code,x1,y1,x2,y2) => {
   const node = svgNode('line',{x1,y1,x2,y2,stroke:drawingColors[code],'stroke-width':3,'data-code':code});
   node.append(svgNode('title',{},code)); svg.append(node);
  };
  const inset = Math.min(9,(right-left)/14,(split-y)/6,(bottom-split)/6);
  if (is38) {
   const rectangle = (code,a,t,b,z) => {
    line(code,a,t,b,t); line(code,b,t,b,z); line(code,b,z,a,z); line(code,a,z,a,t);
   };
   rectangle('798MD29',left,y,right,bottom);
   line('798MD30',left,split,right,split); line('798MD30',mid,y,mid,bottom);
   [[left,mid],[mid,right]].forEach(([a,b]) => {
    rectangle('798MD14',a+inset,y+inset,b-inset,split-inset);
    rectangle('79823',a+inset,split+inset,b-inset,bottom-inset);
    rectangle('868 B16C',a+2*inset,split+2*inset,b-2*inset,bottom-2*inset);
   });
  } else {
  line('72803',left,y,left,bottom); line('72803',right,y,right,bottom);
  line('72802D',left,bottom,right,bottom); line('72809',left,y,right,y);
  line('72801A',left,split,right,split); line('72808',mid,y,mid,split);
  [left+inset,right-inset].forEach(x=>line('72812',x,y+inset,x,split-inset));
  [[left+2*inset,mid-inset],[mid+inset,right-2*inset]].forEach(([a,b])=>{
   const t=y+inset, z=split-inset;
   line('72816',a,t,b,t); line('72816',b,t,b,z); line('72816',b,z,a,z); line('72816',a,z,a,t);
  });
  [[left+inset,mid-inset],[mid+inset,right-inset]].forEach(([a,b])=>{
   line('72804',a,split+inset,b,split+inset); line('72804',a,bottom-inset,b,bottom-inset);
  });
  line('72807',left+inset,split+inset,left+inset,bottom-inset); line('72807',right-inset,split+inset,right-inset,bottom-inset);
  line('72806',mid-inset,split+inset,mid-inset,bottom-inset); line('72806',mid+inset,split+inset,mid+inset,bottom-inset);
  }
  const text = (x,y,value,anchor='middle') => svg.append(svgNode('text',{x,y,'text-anchor':anchor},value));
  text(mid,28,`${w}′ wide`); text(left-12,(y+bottom)/2,`${h}′`,'end');
  text(right+12,(y+split)/2,`${top}′`,'start'); text(right+12,(split+bottom)/2,`${Math.round((h-top)*1000)/1000}′`,'start');
  text(235,435,`Size ${index+1} · Qty ${row.querySelector('.size-quantity').value}`);
  canvas.append(svg);
 }
 selector.addEventListener('change',()=>render(currentTemplate,rows));
 Object.entries(fields).forEach(([id,css])=>document.getElementById(id).addEventListener('input',event=>{
  const row = rows[Number(selector.value)]; if (!row) return;
  row.querySelector(css).value = event.target.value;
  if (id === 'diagramHeading') { row.querySelector('.size-has-heading').checked=true; row.querySelector('.size-heading-height').disabled=false; }
  row.querySelector(css).dispatchEvent(new Event('input',{bubbles:true}));
 }));
 return {render,matches,valid};
})();
</script>
