import{E as e}from"./index-Bi8CIbZL.js";
/**
* @license lucide-vue-next v0.536.0 - ISC
*
* This source code is licensed under the ISC license.
* See the LICENSE file in the root directory of this source tree.
*/
const t=e=>e.replace(/([a-z0-9])([A-Z])/g,`$1-$2`).toLowerCase(),n=e=>e.replace(/^([A-Z])|[\s-_]+(\w)/g,(e,t,n)=>n?n.toUpperCase():t.toLowerCase()),r=e=>{let t=n(e);return t.charAt(0).toUpperCase()+t.slice(1)},i=(...e)=>e.filter((e,t,n)=>!!e&&e.trim()!==``&&n.indexOf(e)===t).join(` `).trim(),a=e=>e===``;
/**
* @license lucide-vue-next v0.536.0 - ISC
*
* This source code is licensed under the ISC license.
* See the LICENSE file in the root directory of this source tree.
*/
var o={xmlns:`http://www.w3.org/2000/svg`,width:24,height:24,viewBox:`0 0 24 24`,fill:`none`,stroke:`currentColor`,"stroke-width":2,"stroke-linecap":`round`,"stroke-linejoin":`round`};const s=({name:n,iconNode:s,absoluteStrokeWidth:c,"absolute-stroke-width":l,strokeWidth:u,"stroke-width":d,size:f=o.width,color:p=o.stroke,...m},{slots:h})=>e(`svg`,{...o,...m,width:f,height:f,stroke:p,"stroke-width":a(c)||a(l)||c===!0||l===!0?Number(u||d||o[`stroke-width`])*24/Number(f):u||d||o[`stroke-width`],class:i(`lucide`,m.class,...n?[`lucide-${t(r(n))}-icon`,`lucide-${t(n)}`]:[`lucide-icon`])},[...s.map(t=>e(...t)),...h.default?[h.default()]:[]]),c=(t,n)=>(r,{slots:i,attrs:a})=>e(s,{...a,...r,iconNode:n,name:t},i);export{c as b};