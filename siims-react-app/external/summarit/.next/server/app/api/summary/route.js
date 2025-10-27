"use strict";(()=>{var e={};e.id=325,e.ids=[325],e.modules={399:e=>{e.exports=require("next/dist/compiled/next-server/app-page.runtime.prod.js")},517:e=>{e.exports=require("next/dist/compiled/next-server/app-route.runtime.prod.js")},2615:e=>{e.exports=require("http")},8791:e=>{e.exports=require("https")},8621:e=>{e.exports=require("punycode")},6162:e=>{e.exports=require("stream")},7360:e=>{e.exports=require("url")},1568:e=>{e.exports=require("zlib")},2581:(e,t,r)=>{r.r(t),r.d(t,{originalPathname:()=>y,patchFetch:()=>f,requestAsyncStorage:()=>p,routeModule:()=>d,serverHooks:()=>g,staticGenerationAsyncStorage:()=>h});var n={};r.r(n),r.d(n,{OPTIONS:()=>u,POST:()=>m});var a=r(9303),o=r(8716),s=r(670),i=r(7070),l=r(1926);let c={"Access-Control-Allow-Origin":"*","Access-Control-Allow-Methods":"GET, POST, OPTIONS","Access-Control-Allow-Headers":"Content-Type, Authorization"};async function u(){return new i.NextResponse(null,{status:204,headers:c})}async function m(e){try{let t=await e.json().catch(()=>null),r=t?.section,n=t?.studentId,a=t?.week,o=t?.useGPT,s=t?.analysisType,u=t?.isOverall;console.log("Summary request:",{section:r,studentId:n,week:a,isOverall:u,analysisType:s});let m=await (0,l._8)(r,n);console.log("All reports from DB:",m.length,"Week numbers:",m.map(e=>e.weekNumber));let d=Array.isArray(m)?u?m:m.filter(e=>!a||Number(e.weekNumber||1)===Number(a)):[],p="coordinator"===s?d:d.filter(e=>!e.excuse);console.log("All reports for week:",d.length,"Reports for summary:",p.length,"Analysis type:",s,"Week numbers:",d.map(e=>e.weekNumber));let h=p.map(e=>`${e.activities||""} ${e.learnings||""}`.trim()).filter(Boolean).map(e=>e.replace(/\s+/g," ").trim()).map(e=>/[.!?]$/.test(e)?e:`${e}.`).join(" ").trim();console.log("Text length:",h.length,"Text preview:",h.substring(0,200)+"..."),console.log("Individual report texts:",p.map(e=>({week:e.weekNumber,activities:e.activities?.substring(0,50),learnings:e.learnings?.substring(0,50)})));let g=h.toLowerCase(),y=[["math","mathematics","science","algorithm","compute","analysis"],["best practice","standard","policy","method","procedure","protocol"],["analyze","analysis","problem","root cause","diagnose","troubleshoot"],["user need","requirement","stakeholder","ux","usability"],["design","implement","evaluate","build","develop","test","setup","configure","configuration","install"],["safety","health","environment","security","ethical"],["tool","framework","library","technology","platform"],["team","collaborat","leader","group"],["plan","schedule","timeline","project plan"],["communicat","present","documentation","write","report"],["impact","society","organization","community"],["ethical","privacy","legal","compliance"],["learn","self-study","latest","new skill"],["research","experiment","study","investigation"],["filipino","heritage","culture","tradition"]].map(e=>{let t=0;for(let r of e){if(g.includes(r)){t++;continue}let e=r.split(" ");if(e.length>1&&e.some(e=>g.includes(e))){t++;continue}let n=r.replace(/(ing|ed|es|s)$/,"");if(n.length>3&&g.includes(n)||[r+"s",r+"ing",r+"ed",r.replace(/s$/,"")].some(e=>g.includes(e))){t++;continue}}return t}),f=y.reduce((e,t)=>e+t,0),w=y.map(e=>f>0?Math.round(e/f*100):0),v=null,O=process.env.OPENAI_API_KEY;if(O&&h&&o&&"coordinator"===s){let e=`You are an evaluator creating brief, concise summaries of BSIT internship journals for coordinators.

Your goal is to create SHORT, EASY-TO-READ summaries that capture the essential activities and learnings.

The summary should:
- Be BRIEF and CONCISE - aim for 2-3 sentences maximum.
- Focus only on the MOST IMPORTANT activities and key learnings.
- Use simple, clear language that's easy to read quickly.
- Avoid unnecessary details and repetitive information.
- Use proper connector words (and, is, are, but, however, therefore, etc.) to create smooth, flowing sentences.
- Write in natural, readable language with proper grammar and sentence structure.
- Prioritize clarity and brevity over comprehensiveness.

Do not list Program Outcomes or graph data. Your output is only for coordinators to quickly review student progress.`,t=`Create a brief, concise summary of the following student journal entry:

**If data is for one week:**
- Write a SHORT summary (2-3 sentences) highlighting the most important activities and key learnings.

**If over all selected in drop down menu weeks:**
- Write a SHORT summary (2-3 sentences) describing the student's main tasks and learnings throughout the OJT.

Requirements:
- Be BRIEF and CONCISE - maximum 2-3 sentences.
- Focus only on the MOST IMPORTANT activities and learnings.
- Use simple, clear language that's easy to read quickly.
- Use proper connector words (and, is, are, but, however, therefore, etc.) to create smooth, flowing sentences.
- Write in natural, readable language with proper grammar and sentence structure.
- Prioritize brevity and clarity over comprehensive coverage.

Entry:
${h}`;try{let r=await fetch("https://api.openai.com/v1/chat/completions",{method:"POST",headers:{"Content-Type":"application/json",Authorization:`Bearer ${O}`},body:JSON.stringify({model:"gpt-4o-mini",messages:[{role:"system",content:e},{role:"user",content:t}],temperature:.2})});if(r.ok){let e=await r.json();v=e?.choices?.[0]?.message?.content||null}}catch{}}else if(O&&h&&o&&"chairman"===s){let e=`You are an expert evaluator creating brief, concise summaries of BSIT internship journals for chairpersons.

Your goal is to create SHORT, EASY-TO-READ summaries that capture the essential activities and learnings.

The summary should:
- Be BRIEF and CONCISE - aim for 2-3 sentences maximum.
- Focus only on the MOST IMPORTANT activities and key learnings.
- Use simple, clear language that's easy to read quickly.
- Avoid unnecessary details and repetitive information.
- Use proper connector words (and, is, are, but, however, therefore, etc.) to create smooth, flowing sentences.
- Write in natural, readable language with proper grammar and sentence structure.
- Prioritize clarity and brevity over comprehensiveness.

Do not list Program Outcomes or detailed analysis. Your output is only for chairpersons to quickly review section progress.`,t=`Create a brief, concise summary of the following student journal entry:

**If data is for one week:**
- Write a SHORT summary (2-3 sentences) highlighting the most important activities and key learnings.

**If overall analysis:**
- Write a SHORT summary (2-3 sentences) describing the student's main tasks and learnings throughout the OJT.

Requirements:
- Be BRIEF and CONCISE - maximum 2-3 sentences.
- Focus only on the MOST IMPORTANT activities and learnings.
- Use simple, clear language that's easy to read quickly.
- Use proper connector words (and, is, are, but, however, therefore, etc.) to create smooth, flowing sentences.
- Write in natural, readable language with proper grammar and sentence structure.
- Prioritize brevity and clarity over comprehensive coverage.

Entry:
${h}`;try{let r=await fetch("https://api.openai.com/v1/chat/completions",{method:"POST",headers:{"Content-Type":"application/json",Authorization:`Bearer ${O}`},body:JSON.stringify({model:"gpt-4o-mini",messages:[{role:"system",content:e},{role:"user",content:t}],temperature:.2})});if(r.ok){let e=await r.json();v=e?.choices?.[0]?.message?.content||null}}catch{}}else if(O&&h){let e=`Analyze the following student journal entry and produce:

1) A concise summary (2–3 sentences) of what the student actually did.
2) A list of BSIT Program Outcomes (PO1–PO15) that are relevant based on context.
3) A short reasoning (1–2 sentences) why each PO applies.
4) If none clearly fit, write "No PO matched."

Entry:
${h}`;try{let t=await fetch("https://api.openai.com/v1/chat/completions",{method:"POST",headers:{"Content-Type":"application/json",Authorization:`Bearer ${O}`},body:JSON.stringify({model:"gpt-4o-mini",messages:[{role:"system",content:"You are an educational evaluator for BSIT internships. Your job is to analyze student OJT journal entries and determine which BSIT Program Outcomes (PO1–PO15) apply based on context and intent — not just keywords. Be objective, logical, and consistent."},{role:"user",content:e}],temperature:.2})});if(t.ok){let e=await t.json();v=e?.choices?.[0]?.message?.content||null}}catch{}}let S=v||h||"No journal entries found.";return i.NextResponse.json({summary:S,keywordScores:w,usedGPT:!!v},{headers:c})}catch(e){return console.error("Summary error:",e),i.NextResponse.json({error:"Failed to generate summary"},{status:500,headers:c})}}let d=new a.AppRouteRouteModule({definition:{kind:o.x.APP_ROUTE,page:"/api/summary/route",pathname:"/api/summary",filename:"route",bundlePath:"app/api/summary/route"},resolvedPagePath:"C:\\Users\\Admin\\Desktop\\summarize application -Online version\\summarit\\src\\app\\api\\summary\\route.ts",nextConfigOutput:"",userland:n}),{requestAsyncStorage:p,staticGenerationAsyncStorage:h,serverHooks:g}=d,y="/api/summary/route";function f(){return(0,s.patchFetch)({serverHooks:g,staticGenerationAsyncStorage:h})}},1926:(e,t,r)=>{r.d(t,{GR:()=>m,LK:()=>l,OQ:()=>s,Zn:()=>u,_8:()=>c,aE:()=>d,sK:()=>i});var n=r(4738);let a=process.env.SUPABASE_URL||process.env.NEXT_PUBLIC_SUPABASE_URL,o=process.env.SUPABASE_KEY||process.env.SUPABASE_SERVICE_ROLE_KEY,s=(0,n.createClient)(a,o);async function i(e){let{data:t,error:r}=await s.from("StudentEnrollment").select("*").eq("section",e).order("studentId",{ascending:!0});if(r)throw r;return t||[]}async function l(e){let{data:t,error:r}=await s.from("StudentEnrollment").insert([{...e,createdAt:new Date().toISOString(),updatedAt:new Date().toISOString()}]).select().single();if(r)throw r;return t}async function c(e,t){let r=s.from("WeeklyReport").select("*");e&&(r=r.eq("section",e)),t&&(r=r.eq("studentId",t));let{data:n,error:a}=await r.order("weekNumber",{ascending:!0});if(a)throw a;return n||[]}async function u(e){let{data:t,error:r}=await s.from("WeeklyReport").insert([{...e,createdAt:new Date().toISOString(),updatedAt:new Date().toISOString()}]).select().single();if(r)throw r;return t}async function m(e){let{error:t}=await s.from("WeeklyReport").delete().eq("id",e);if(t)throw t;return!0}async function d(e){let{data:t,error:r}=await s.from("Coordinator").upsert([{...e,createdAt:new Date().toISOString(),updatedAt:new Date().toISOString()}],{onConflict:"userName"}).select().single();if(r)throw r;return t}}};var t=require("../../../webpack-runtime.js");t.C(e);var r=e=>t(t.s=e),n=t.X(0,[276,175],()=>r(2581));module.exports=n})();