const n=r=>r?r.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").trim():"",i=(r,e)=>e?r?n(r).includes(n(e)):!1:!0;export{i,n};
