import React, { useEffect, useMemo, useState } from "react";
import axiosClient from "../../api/axiosClient";

export default function ChairpersonSummary({ coordinatorId, week, refreshTrigger }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [summary, setSummary] = useState("");
  const [scores, setScores] = useState(Array.from({ length: 15 }, () => 0));
  const [posHitText, setPosHitText] = useState("");
  const [posNotHitText, setPosNotHitText] = useState("");
  const [hitList, setHitList] = useState([]);
  const [notHitList, setNotHitList] = useState([]);
  const [otherActivities, setOtherActivities] = useState([]);

  const PO_DESCRIPTIONS = useMemo(() => ([
    "Apply knowledge of computing, science, and mathematics.",
    "Use current best practices and standards.",
    "Analyze complex computing/IT-related problems.",
    "Identify and analyze user needs.",
    "Design, implement, and evaluate systems.",
    "Integrate solutions considering public health/safety, etc.",
    "Apply appropriate techniques and tools.",
    "Work effectively in teams and lead when needed.",
    "Assist in creation of effective project plans.",
    "Communicate effectively.",
    "Assess local/global impact of IT.",
    "Act ethically and responsibly.",
    "Pursue independent learning.",
    "Participate in research and development.",
    "Preserve Filipino historical and cultural heritage.",
  ]), []);

  async function loadSummary() {
    try {
      setLoading(true);
      setError(null);

      // helper: fetch entries for students under coordinator and compute PO scores
      const authHeaders = {
        Accept: "application/json",
        Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
      };

      const apiBase = import.meta.env.VITE_API_BASE_URL;

      const computeScores = async () => {
        // Load students under this coordinator directly
        let filteredStudents = [];
        try {
          // Chairperson endpoint: returns all students under chairperson's program
          const r = await fetch(`${apiBase}/api/v1/chairperson/students`, {
            headers: authHeaders,
            credentials: "include",
          });
          const p1 = await r.json().catch(() => []);
          let students = Array.isArray(p1?.data) ? p1.data : Array.isArray(p1) ? p1 : [];
          // Filter by selected coordinator
          filteredStudents = students.filter((s) => {
            const sid = s.coordinator_id ?? s.coordinatorId ?? s.coordinatorID;
            return String(sid ?? '') === String(coordinatorId ?? '');
          });
        } catch {}
        // If the chairperson endpoint payload doesn't include coordinator_id, fallback to full-students list
        if (!filteredStudents || filteredStudents.length === 0) {
          try {
            const studentsResp = await fetch(`${apiBase}/api/v1/users/students/get-all-students`, {
              headers: authHeaders,
              credentials: "include",
            });
            const studentsPayload = await studentsResp.json().catch(() => ({}));
            let students = Array.isArray(studentsPayload?.data)
              ? studentsPayload.data
              : Array.isArray(studentsPayload?.initial_students)
              ? studentsPayload.initial_students
              : Array.isArray(studentsPayload)
              ? studentsPayload
              : [];
            const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"];
            filteredStudents = students.filter((s) => {
              for (const key of coordinatorKeyNames) {
                if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                  return String(s[key] ?? "") === String(coordinatorId ?? "");
                }
              }
              const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
              const cid = c ? (c.id ?? c.coordinator_id) : undefined;
              return String(cid ?? "") === String(coordinatorId ?? "");
            });
          } catch {}
        }
        const requests = filteredStudents.map((s) => {
          const sid = s.id ?? s.student_id ?? s.user_id ?? s.application_id;
          return fetch(`${apiBase}/api/v1/weekly-entries/student/${sid}`, {
            headers: authHeaders,
            credentials: "include",
          }).then((r) => r.json()).catch(() => [])
        });
        const results = await Promise.all(requests);
        const normalizeWeekly = (payload) => {
          if (!payload) return [];
          if (Array.isArray(payload?.data)) return payload.data;
          if (Array.isArray(payload?.weekly_entries)) return payload.weekly_entries;
          if (Array.isArray(payload)) return payload;
          return [];
        };
        const stripHtml = (t) => String(t || "")
          .replace(/<\s*\/?.*?>/g, ' ')
          .replace(/&nbsp;/gi, ' ')
          .replace(/&amp;/gi, '&')
          .replace(/&lt;/gi, '<')
          .replace(/&gt;/gi, '>')
          .replace(/\s+/g, ' ')
          .trim();
        const weekNum = Number(week || 1);
        const allEntries = results.flatMap((p) => normalizeWeekly(p));
        let weekEntries;
        
        // Handle "overall" case - use all entries from all weeks
        if (week === "overall") {
          weekEntries = allEntries;
        } else {
          weekEntries = allEntries.filter((r) => {
            const wn = Number(r.week_number ?? r.weekNumber ?? r.week);
            return !Number.isNaN(wn) ? wn === weekNum : true;
          });
        }
        if (filteredStudents.length === 0 || weekEntries.length === 0) {
          // No data for this coordinator/week; don't overwrite any existing summary from backend
          setScores(Array.from({ length: 15 }, () => 0));
          return;
        }
        const text = weekEntries
          .map((r) => `${stripHtml(r.tasks || r.task || r.activities || "")} ${stripHtml(r.learnings || r.learning || "")}`)
          .join(" ");
        
        console.log('Raw text for PO scoring:', text.substring(0, 200) + '...');
        console.log('Week entries found:', weekEntries.length);
        if (weekEntries.length > 0) {
          console.log('Sample entry:', weekEntries[0]);
        }
        
        const keywordSets = [
          ["math", "mathematics", "science", "algorithm", "compute", "analysis"],
          ["best practice", "standard", "policy", "method", "procedure", "protocol"],
          ["analyze", "analysis", "problem", "root cause", "diagnose", "troubleshoot"],
          ["user need", "requirement", "stakeholder", "ux", "usability"],
          ["design", "implement", "evaluate", "build", "develop", "test", "setup", "configure", "configuration", "install"],
          ["safety", "health", "environment", "security", "ethical"],
          ["tool", "framework", "library", "technology", "platform"],
          ["team", "collaborat", "leader", "group"],
          ["plan", "schedule", "timeline", "project plan"],
          ["communicat", "present", "documentation", "write", "report"],
          ["impact", "society", "organization", "community"],
          ["ethical", "privacy", "legal", "compliance"],
          ["learn", "self-study", "latest", "new skill"],
          ["research", "experiment", "study", "investigation"],
          ["filipino", "heritage", "culture", "tradition"],
        ];
        const lower = String(text || "").toLowerCase();
        console.log('Text to analyze:', lower);
        
        const counts = keywordSets.map((set, index) => {
          let c = 0; 
          for (const kw of set) { 
            if (lower.includes(kw)) {
              console.log(`PO${index + 1}: Found keyword "${kw}" in text`);
              c++; 
            }
          } 
          return c;
        });
        
        console.log('Keyword counts:', counts);
        const total = counts.reduce((a, b) => a + b, 0) || 1;
        const perc = counts.map((c) => Math.round((c / total) * 100));
        console.log('Total matches:', total, 'Percentages:', perc);
        setScores(perc);
        console.log('PO Scores computed:', perc, 'Total entries:', weekEntries.length, 'Students:', filteredStudents.length);

        // Build hit/not-hit lists and other activities overview
        const PO_TITLES = [
          'PO1','PO2','PO3','PO4','PO5','PO6','PO7','PO8','PO9','PO10','PO11','PO12','PO13','PO14','PO15'
        ];
        const KEY_HINT = keywordSets.map((arr) => arr.slice(0,3).join(', '));
        const hits = [];
        const notHits = [];
        perc.forEach((v, idx) => {
          if (v > 0) {
            hits.push({ po: PO_TITLES[idx], reason: `Evidence of ${KEY_HINT[idx]}` });
          } else {
            notHits.push({ po: PO_TITLES[idx], reason: `No clear evidence of ${KEY_HINT[idx]}` });
          }
        });
        setHitList(hits);
        setNotHitList(notHits);

        const sentences = String(text).split(/[.!?]+/).map((s)=>s.trim()).filter(Boolean);
        const unmatched = sentences.filter((s)=>{
          const l = s.toLowerCase();
          return !keywordSets.some((set)=> set.some((kw)=> l.includes(kw)));
        });
        setOtherActivities(unmatched.slice(0,5));

        // This fallback logic is now handled in the main loadSummary function
      };

      // Helper method to aggregate weekly data
      const aggregateWeeklyData = (weeklyData) => {
        const allActivities = [];
        const allLearnings = [];
        const allAssessments = [];
        
        if (Array.isArray(weeklyData)) {
          weeklyData.forEach(entry => {
            if (entry.activities && Array.isArray(entry.activities)) {
              allActivities.push(...entry.activities);
            }
            if (entry.learnings && Array.isArray(entry.learnings)) {
              allLearnings.push(...entry.learnings);
            }
            if (entry.assessment) {
              allAssessments.push(entry.assessment);
            }
          });
        }
        
        // Remove duplicates and clean up
        const uniqueActivities = [...new Set(allActivities)].filter(Boolean);
        const uniqueLearnings = [...new Set(allLearnings)].filter(Boolean);
        const combinedAssessment = allAssessments.join(' ');
        
        return {
          corrected_activities: uniqueActivities,
          corrected_learnings: uniqueLearnings,
          "summary for this section on a week": combinedAssessment
        };
      };
      
      // Helper method to send data to OpenAI
      const sendToOpenAI = async (data) => {
        try {
          console.log('Sending aggregated data to OpenAI:', data);
          
          const openAIResponse = await fetch(`${apiBase}/api/v1/summary/openai-summarize`, {
            method: 'POST',
            headers: {
              ...authHeaders,
              'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
              data: data,
              type: 'overall_summary'
            })
          });
          
          console.log('OpenAI response status:', openAIResponse.status);
          
          if (openAIResponse.ok) {
            const openAIData = await openAIResponse.json();
            console.log('OpenAI response data:', openAIData);
            
            if (openAIData.summary && openAIData.summary.trim()) {
              // Clean up the summary by removing quotes and extra formatting
              const cleanSummary = openAIData.summary
                .replace(/^["']|["']$/g, '') // Remove surrounding quotes
                .replace(/\\"/g, '"') // Unescape quotes
                .replace(/\\n/g, ' ') // Replace newlines with spaces
                .replace(/\s+/g, ' ') // Normalize whitespace
                .trim();
              
              console.log('Using OpenAI summary:', cleanSummary);
              setSummary(cleanSummary);
              
              // Compute scores for the aggregated data
              await computeScores();
              return;
            } else {
              console.log('OpenAI returned empty summary');
            }
          } else {
            const errorText = await openAIResponse.text();
            console.log('OpenAI API error:', openAIResponse.status, errorText);
          }
        } catch (error) {
          console.error('OpenAI summarization failed:', error);
        }
        
        // If OpenAI fails, show error message
        setSummary('Unable to generate summary at this time. Please try again later.');
      };

      // Try backend summary first (Chair-specific route). If it fails, fall back to client-side.
      try {
        // Use GET with query params to avoid 419 in some local setups
        const qp = new URLSearchParams();
        if (coordinatorId) qp.set('coordinatorId', coordinatorId);
        if (week) qp.set('week', String(week));
        qp.set('useGPT', '1');
        const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`, {
          method: 'GET',
          headers: authHeaders,
          credentials: 'include',
        });
        if (resp.ok) {
          const data = await resp.json();
          console.log('Backend response:', data);
          
          // Handle "overall" case - summarize returned learnings locally (no POST)
          if (week === "overall" && data) {
            console.log('Backend data for overall:', data);
            
            // Build a polished paragraph from learnings/activities already returned by backend
            const toArray = (val) => {
              if (Array.isArray(val)) return val.filter(Boolean);
              if (typeof val === 'string') {
                return val
                  .replace(/^\[|\]$/g, '')
                  .split(/"\s*,\s*"|\s*,\s*/)
                  .map((s) => s.replace(/^"|"$/g, '').trim())
                  .filter(Boolean);
              }
              return [];
            };
            let activities = toArray(data.corrected_activities || (data.summary && data.summary.corrected_activities));
            let learnings = toArray(data.corrected_learnings || (data.summary && data.summary.corrected_learnings));

            // If backend did not provide structured arrays, we'll use the data from the backend response
            // The backend should have already provided the structured data
            const assessment = String((data["summary for this section on a week"] || '')).trim();
            
            const buildParagraph = (acts, lerns, assess) => {
              // Clean HTML tags and normalize text
              const cleanText = (text) => {
                if (!text) return '';
                return String(text)
                  .replace(/<[^>]*>/g, '') // Remove HTML tags
                  .replace(/&nbsp;/g, ' ') // Replace &nbsp; with space
                  .replace(/&amp;/g, '&') // Replace &amp; with &
                  .replace(/&lt;/g, '<') // Replace &lt; with <
                  .replace(/&gt;/g, '>') // Replace &gt; with >
                  .replace(/\s+/g, ' ') // Normalize whitespace
                  .trim();
              };

              // Clean and filter the arrays
              const cleanActs = acts.map(cleanText).filter(Boolean);
              const cleanLerns = lerns.map(cleanText).filter(Boolean);
              const cleanAssess = cleanText(assess);

              let p = 'For overall, ';
              if (cleanActs.length) {
                p += `students engaged in activities including ${cleanActs.join(', ')}. `;
              }
              if (cleanLerns.length) {
                p += `Through these experiences, they demonstrated learning and skill development in ${cleanLerns.join(', ')}. `;
              }
              if (cleanAssess) {
                p += cleanAssess;
              }
              p = p.replace(/\s+/g, ' ').trim();
              if (!/[.!?]$/.test(p)) p += '.';
              return p;
            };
            // Send to OpenAI for polished summary
            try {
              const openAIData = {
                corrected_activities: activities,
                corrected_learnings: learnings,
                "summary for this section on a week": assessment
              };
              
              console.log('Sending to OpenAI:', openAIData);
              
              const openAIResponse = await fetch(`${apiBase}/api/v1/summary/openai-summarize`, {
                method: 'POST',
                headers: {
                  ...authHeaders,
                  'Content-Type': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify({
                  data: openAIData,
                  type: 'overall_summary'
                })
              });
              
              if (openAIResponse.ok) {
                const openAIResult = await openAIResponse.json();
                console.log('OpenAI response:', openAIResult);
                
                if (openAIResult.summary) {
                  setSummary(openAIResult.summary);
                  await computeScores();
                  return;
                }
              } else {
                console.log('OpenAI failed:', openAIResponse.status);
              }
            } catch (error) {
              console.error('OpenAI error:', error);
            }
            
            // Fallback to local paragraph if OpenAI fails
            const paragraph = buildParagraph(activities, learnings, assessment);
            console.log('Overall paragraph (local):', paragraph);
            setSummary(paragraph);
            await computeScores();
            return;
            
            // Fallback: Check if data is a string that needs parsing
            let parsedData = data;
            if (typeof data === 'string') {
              try {
                parsedData = JSON.parse(data);
                console.log('Parsed string data:', parsedData);
              } catch (e) {
                console.log('Data is not JSON, treating as text');
                parsedData = data;
              }
            }
            
            // If we have structured data, send to OpenAI for proper summarization
            if (typeof parsedData === 'object' && (parsedData.corrected_activities || parsedData.corrected_learnings || parsedData["summary for this section on a week"])) {
              console.log('Processing structured data for OpenAI summarization');
              
              // Test OpenAI endpoint first
              try {
                const testResponse = await fetch(`${apiBase}/api/v1/summary/openai-test`, {
                  method: 'GET',
                  headers: authHeaders,
                  credentials: 'include'
                });
                
                if (testResponse.ok) {
                  const testData = await testResponse.json();
                  console.log('OpenAI endpoint test:', testData);
                } else {
                  console.log('OpenAI endpoint test failed:', testResponse.status);
                }
              } catch (testError) {
                console.log('OpenAI endpoint test error:', testError);
              }
              
              // Send to OpenAI for summarization
              try {
                console.log('Sending data to OpenAI:', parsedData);
                
                const openAIResponse = await fetch(`${apiBase}/api/v1/summary/openai-summarize`, {
                  method: 'POST',
                  headers: {
                    ...authHeaders,
                    'Content-Type': 'application/json'
                  },
                  credentials: 'include',
                  body: JSON.stringify({
                    data: parsedData,
                    type: 'overall_summary'
                  })
                });
                
                console.log('OpenAI response status:', openAIResponse.status);
                
                if (openAIResponse.ok) {
                  const openAIData = await openAIResponse.json();
                  console.log('OpenAI response data:', openAIData);
                  
                  if (openAIData.summary && openAIData.summary.trim()) {
                    // Clean up the summary by removing quotes and extra formatting
                    const cleanSummary = openAIData.summary
                      .replace(/^["']|["']$/g, '') // Remove surrounding quotes
                      .replace(/\\"/g, '"') // Unescape quotes
                      .replace(/\\n/g, ' ') // Replace newlines with spaces
                      .replace(/\s+/g, ' ') // Normalize whitespace
                      .trim();
                    
                    console.log('Using OpenAI summary:', cleanSummary);
                    setSummary(cleanSummary);
                    
                    if (parsedData?.keywordScores && Array.isArray(parsedData.keywordScores)) {
                      setScores(parsedData.keywordScores);
                      console.log('Using backend PO scores:', parsedData.keywordScores);
                    } else {
                      await computeScores();
                    }
                    return;
                  } else {
                    console.log('OpenAI returned empty summary, trying fallback');
                  }
                } else {
                  const errorText = await openAIResponse.text();
                  console.log('OpenAI API error:', openAIResponse.status, errorText);
                }
              } catch (error) {
                console.error('OpenAI summarization failed:', error);
                console.error('Error details:', {
                  message: error.message,
                  stack: error.stack,
                  name: error.name
                });
                setSummary('Unable to generate summary at this time. Please try again later.');
                return;
              }
            } else if (typeof parsedData?.summary === 'string') {
              // Check if summary contains JSON-like structure
              if (parsedData.summary.includes('corrected_activities') && parsedData.summary.includes('corrected_learnings')) {
                console.log('Summary contains structured data, attempting to extract');
                try {
                  // Try to extract structured data from the summary string
                  const activitiesMatch = parsedData.summary.match(/"corrected_activities":\s*\[(.*?)\]/s);
                  const learningsMatch = parsedData.summary.match(/"corrected_learnings":\s*\[(.*?)\]/s);
                  const summaryMatch = parsedData.summary.match(/"summary for this section on a week":\s*"([^"]*)"/);
                  
                  if (activitiesMatch || learningsMatch || summaryMatch) {
                    const extractList = (match) => {
                      if (!match) return [];
                      const content = match[1];
                      const items = content.split(',').map(item => 
                        item.replace(/^"|"$/g, '').trim()
                      ).filter(Boolean);
                      return items;
                    };
                    
                    const activities = extractList(activitiesMatch);
                    const learnings = extractList(learningsMatch);
                    const assessment = summaryMatch ? summaryMatch[1] : '';
                    
                    console.log('Extracted from summary string - activities:', activities);
                    console.log('Extracted from summary string - learnings:', learnings);
                    console.log('Extracted from summary string - assessment:', assessment);
                    
                    // Send extracted data to OpenAI for proper summarization
                    try {
                      const openAIResponse = await fetch(`${apiBase}/api/v1/summary/openai-summarize`, {
                        method: 'POST',
                        headers: {
                          ...authHeaders,
                          'Content-Type': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                          data: {
                            corrected_activities: activities,
                            corrected_learnings: learnings,
                            "summary for this section on a week": assessment
                          },
                          type: 'overall_summary'
                        })
                      });
                      
                      if (openAIResponse.ok) {
                        const openAIData = await openAIResponse.json();
                        if (openAIData.summary && openAIData.summary.trim()) {
                          const cleanSummary = openAIData.summary
                            .replace(/^["']|["']$/g, '')
                            .replace(/\\"/g, '"')
                            .replace(/\\n/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                          
                          console.log('Using OpenAI summary from string extraction:', cleanSummary);
                          setSummary(cleanSummary);
                          
                          if (parsedData?.keywordScores && Array.isArray(parsedData.keywordScores)) {
                            setScores(parsedData.keywordScores);
                          } else {
                            await computeScores();
                          }
                          return;
                        }
                      }
                    } catch (error) {
                      console.log('OpenAI summarization failed for string extraction:', error);
                    }
                    
                    setSummary('Unable to generate summary at this time. Please try again later.');
                    return;
                  }
                } catch (e) {
                  console.log('Failed to extract structured data from summary string:', e);
                }
              }
              
              // Fallback to regular summary cleaning
              const cleanSum = String(parsedData.summary)
                .replace(/<\s*\/? .*?>/g, ' ')
                .replace(/&nbsp;/gi, ' ')
                .replace(/&amp;/gi, '&')
                .replace(/&lt;/gi, '<')
                .replace(/&gt;/gi, '>')
                .replace(/\s+/g, ' ')
                .trim();
              setSummary(cleanSum);
              
              if (parsedData?.keywordScores && Array.isArray(parsedData.keywordScores)) {
                setScores(parsedData.keywordScores);
                console.log('Using backend PO scores:', parsedData.keywordScores);
              } else {
                await computeScores();
              }
              return;
            }
          }
          
          // Handle regular week summaries
          if (typeof data?.posHitExplanation === 'string') setPosHitText(data.posHitExplanation);
          if (typeof data?.posNotHitExplanation === 'string') setPosNotHitText(data.posNotHitExplanation);
          if (data?.summary) {
            const clean = String(data.summary)
              .replace(/<\s*\/? .*?>/g, ' ')
              .replace(/&nbsp;/gi, ' ')
              .replace(/&amp;/gi, '&')
              .replace(/&lt;/gi, '<')
              .replace(/&gt;/gi, '>')
              .replace(/\s+/g, ' ')
              .trim();

            try {
              const respAI = await fetch(`${apiBase}/api/v1/summary/openai-summarize`, {
                method: 'POST',
                headers: { ...authHeaders, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'include',
                body: JSON.stringify({
                  data: {
                    corrected_activities: Array.isArray(data.corrected_activities) ? data.corrected_activities : [],
                    corrected_learnings: Array.isArray(data.corrected_learnings) ? data.corrected_learnings : [],
                    'summary for this section on a week': clean,
                  },
                  type: 'week_summary',
                }),
              });
              if (respAI.ok) {
                const ai = await respAI.json();
                if (ai?.summary) {
                  setSummary(String(ai.summary).replace(/\s+/g, ' ').trim());
                } else {
                  setSummary(clean);
                }
              } else {
                setSummary(clean);
              }
            } catch (e) {
              console.log('OpenAI week polish failed:', e);
              setSummary(clean);
            }

            if (data?.keywordScores && Array.isArray(data.keywordScores)) {
              setScores(data.keywordScores);
              console.log('Using backend PO scores:', data.keywordScores);
            } else {
              await computeScores();
            }
            return;
          }
        }
      } catch (err) {
        console.log('Backend API failed, falling back to client-side:', err);
      }

      // Compute summary client-side from students' weekly entries under this coordinator
      try {
        setError(null);

        // For "overall" case, create a comprehensive summary from all data
        if (week === "overall") {
          let overallSummary = "";
          
          // 1) Load students under this coordinator directly (chairperson-safe endpoint)
          let filteredStudents = [];
          try {
            const r = await fetch(`${apiBase}/api/v1/chairperson/students`, {
              headers: authHeaders,
              credentials: "include",
            });
            const p1 = await r.json().catch(() => []);
            const arr = Array.isArray(p1?.data) ? p1.data : Array.isArray(p1) ? p1 : [];
            const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"];
            filteredStudents = arr.filter((s) => {
              for (const key of coordinatorKeyNames) {
                if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                  return String(s[key] ?? "") === String(coordinatorId ?? "");
                }
              }
              const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
              const cid = c ? (c.id ?? c.coordinator_id) : undefined;
              return String(cid ?? "") === String(coordinatorId ?? "");
            });
          } catch {}
          
          if (filteredStudents.length === 0) {
            // Fallback: use generic endpoints and filter client-side
            try {
              const studentsResp = await axiosClient.get("/api/v1/users/students/get-all-students");
              const studentsPayload = studentsResp?.data;
              let students = Array.isArray(studentsPayload?.data)
                ? studentsPayload.data
                : Array.isArray(studentsPayload?.initial_students)
                ? studentsPayload.initial_students
                : Array.isArray(studentsPayload)
                ? studentsPayload
                : [];

              const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator" ];
              const idToUse = coordinatorId;
              filteredStudents = students.filter((s) => {
                for (const key of coordinatorKeyNames) {
                  if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                    return String(s[key] ?? "") === String(idToUse ?? "");
                  }
                }
                return false;
              });
              if (filteredStudents.length === 0) {
                filteredStudents = students.filter((s) => {
                  const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
                  const cid = c ? (c.id ?? c.coordinator_id) : undefined;
                  return String(cid ?? "") === String(idToUse ?? "");
                });
              }
            } catch {}
          }
          
          // If we have students, create a comprehensive summary
          if (filteredStudents.length > 0) {
            console.log('Creating client-side overall summary for', filteredStudents.length, 'students');
          }
        }

        // 2) Fetch weekly entries per student (parallel)
        const requests = filteredStudents.map((s) => {
          const sid = s.id ?? s.student_id ?? s.user_id ?? s.application_id;
          return axiosClient
            .get(`/api/v1/weekly-entries/student/${sid}`)
            .then((r) => r?.data || [])
            .catch(() => [])
        });
        const results = await Promise.all(requests);

        // Normalize weekly entries payloads
        const normalizeWeekly = (payload) => {
          if (!payload) return [];
          if (Array.isArray(payload?.data)) return payload.data;
          if (Array.isArray(payload?.weekly_entries)) return payload.weekly_entries;
          if (Array.isArray(payload)) return payload;
          return [];
        };

        const stripHtml = (t) => String(t || "")
          .replace(/<\s*\/?.*?>/g, ' ') // remove any HTML tags like <p>, <span style="...">
          .replace(/&nbsp;/gi, ' ')
          .replace(/&amp;/gi, '&')
          .replace(/&lt;/gi, '<')
          .replace(/&gt;/gi, '>')
          .replace(/\s+/g, ' ')
          .trim();

        const weekNum = Number(week || 1);
        const allEntries = results.flatMap((p) => normalizeWeekly(p));
        let weekEntries;
        
        // Handle "overall" case - use all entries from all weeks
        if (week === "overall") {
          weekEntries = allEntries;
          
          // Create comprehensive overall summary
          if (weekEntries.length > 0) {
            const activities = weekEntries
              .map((r) => stripHtml(r.tasks || r.task || r.activities || ""))
              .filter(text => text.trim().length > 0)
              .join(". ");
              
            const learnings = weekEntries
              .map((r) => stripHtml(r.learnings || r.learning || ""))
              .filter(text => text.trim().length > 0)
              .join(". ");
            
            overallSummary = `
              <h5 class="text-lg font-semibold text-blue-800 mb-3">📋 Overall Activities Summary</h5>
              <p class="text-gray-700 leading-relaxed mb-4">${activities || 'No specific activities recorded.'}</p>
              
              <h5 class="text-lg font-semibold text-green-800 mb-3">🎓 Key Learnings Across All Weeks</h5>
              <p class="text-gray-700 leading-relaxed mb-4">${learnings || 'No specific learnings recorded.'}</p>
              
              <h5 class="text-lg font-semibold text-purple-800 mb-3">📊 Comprehensive Assessment</h5>
              <p class="text-gray-700 leading-relaxed mb-4">
                This comprehensive overview covers all ${weekEntries.length} weekly entries from students under this coordinator. 
                The analysis provides insights into the overall progress, skill development, and learning outcomes achieved 
                throughout the entire internship period.
              </p>
            `;
            
            setSummary(overallSummary);
            await computeScores();
            return;
          }
        } else {
          weekEntries = allEntries.filter((r) => {
            const wn = Number(r.week_number ?? r.weekNumber ?? r.week);
            return !Number.isNaN(wn) ? wn === weekNum : true;
          });
          // If nothing matched for the chosen week, fall back to all entries
          if (weekEntries.length === 0) {
            weekEntries = allEntries;
          }
        }
        
        const text = weekEntries
          .map((r) => `${stripHtml(r.tasks || r.task || r.activities || "")} ${stripHtml(r.learnings || r.learning || "")}`)
          .join(" ");

        // 4) Local keyword scoring similar to Coordinator logic
        try {
          const keywordSets = [
            ["math", "mathematics", "science", "algorithm", "compute", "analysis"],
            ["best practice", "standard", "policy", "method", "procedure", "protocol"],
            ["analyze", "analysis", "problem", "root cause", "diagnose", "troubleshoot"],
            ["user need", "requirement", "stakeholder", "ux", "usability"],
            ["design", "implement", "evaluate", "build", "develop", "test", "setup", "configure", "configuration", "install"],
            ["safety", "health", "environment", "security", "ethical"],
            ["tool", "framework", "library", "technology", "platform"],
            ["team", "collaborat", "leader", "group"],
            ["plan", "schedule", "timeline", "project plan"],
            ["communicat", "present", "documentation", "write", "report"],
            ["impact", "society", "organization", "community"],
            ["ethical", "privacy", "legal", "compliance"],
            ["learn", "self-study", "latest", "new skill"],
            ["research", "experiment", "study", "investigation"],
            ["filipino", "heritage", "culture", "tradition"],
          ];
          const lower = String(text || "").toLowerCase();
          const counts = keywordSets.map((set) => {
            let c = 0;
            for (const kw of set) {
              if (lower.includes(kw)) c++;
            }
            return c;
          });
          const total = counts.reduce((a, b) => a + b, 0) || 1;
          const perc = counts.map((c) => Math.round((c / total) * 100));

          setScores(perc);
          setSummary(text ? stripHtml(text).slice(0, 240) : "No data available");
        } catch (fallbackErr) {
          setError(
            fallbackErr?.response?.data?.message || fallbackErr?.message || "Failed to load summary"
          );
          setSummary("");
          setScores(Array.from({ length: 15 }, () => 0));
        }
      } catch (fallbackErr) {
        setError(
          fallbackErr?.response?.data?.message || fallbackErr?.message || "Failed to load summary"
        );
        setSummary("");
        setScores(Array.from({ length: 15 }, () => 0));
      }
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadSummary();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [coordinatorId, week, refreshTrigger]);

  // Initialize Bootstrap tooltips
  useEffect(() => {
    const els = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    // Dispose existing instances and reset cached titles so Bootstrap reads fresh values
    els.forEach((el) => {
      const existing = window.bootstrap.Tooltip.getInstance(el);
      if (existing) existing.dispose();
      // Critical: remove Bootstrap's cached title so re-init uses the current attribute
      el.removeAttribute('data-bs-original-title');
    });
    const instances = els.map((el) => new window.bootstrap.Tooltip(el));
    return () => { instances.forEach((i) => i.dispose()); };
  }, [scores]);

  const hasAnyScore = useMemo(() => scores.some((v) => Number(v) > 0), [scores]);

  return (
    <div className="mt-6 bg-white border rounded-lg shadow-sm">
      <div className="px-4 py-3 border-b bg-gray-50 rounded-t-lg">
        <h4 className="text-lg font-semibold text-gray-800">Summary</h4>
      </div>
      <div className="p-4">
        {error && (
          <div className="mb-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
            {error}
          </div>
        )}
        {loading && !summary && (
          <div className="mb-3 text-sm text-sky-800 bg-sky-50 border border-sky-200 rounded px-3 py-2">
            Loading summary…
          </div>
        )}
        {!!summary && (
          <div className="text-gray-800 leading-relaxed">
            {week === "overall" ? (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-blue-800 mb-3">Overall Summary</h5>
                <p className="text-gray-800 leading-7">
                  {summary || 'No comprehensive summary available for the selected coordinator.'}
                </p>
              </div>
            ) : (
              <div dangerouslySetInnerHTML={{ __html: summary }} />
            )}
          </div>
        )}
        {!loading && !summary && (
          <div className="text-gray-500">No summary available.</div>
        )}

        {summary && (
          <div className="mt-6 space-y-6">
            {/* PO Explanations */}
            <div className="grid md:grid-cols-2 gap-6">
              {/* POs Hit */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-blue-800 mb-3">Program Outcomes Achieved</h5>
                {hitList.length > 0 ? (
                  <div className="text-sm text-blue-700 leading-relaxed">
                    <p className="mb-2">Based on the analysis of student activities and reports, the following program outcomes have been successfully achieved:</p>
                    <ul className="list-disc list-inside space-y-1 text-blue-800">
                      {hitList.map((h, i) => (
                        <li key={`hit-${i}`}>
                          <strong>{h.po}</strong> — {h.reason.toLowerCase()}
                        </li>
                      ))}
                    </ul>
                    <p className="mt-3 text-blue-600">
                      These achievements indicate strong progress in the students' learning journey and demonstrate practical application of theoretical knowledge in real-world scenarios.
                    </p>
                  </div>
                ) : (
                  <p className="text-sm text-blue-600">No specific POs were clearly achieved this week.</p>
                )}
              </div>

              {/* POs Not Hit */}
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-red-800 mb-3">Program Outcomes Not Met</h5>
                {notHitList.length > 0 ? (
                  <div className="text-sm text-red-700 leading-relaxed">
                    <p className="mb-2">After reviewing the student activities and reports, the following program outcomes require additional attention and development:</p>
                    <ul className="list-disc list-inside space-y-1 text-red-800">
                      {notHitList.map((h, i) => (
                        <li key={`not-hit-${i}`}>
                          <strong>{h.po}</strong> — {h.reason.toLowerCase()}
                        </li>
                      ))}
                    </ul>
                    <p className="mt-3 text-red-600">
                      These areas present opportunities for improvement and should be addressed in future activities to ensure comprehensive learning outcomes are met.
                    </p>
                  </div>
                ) : (
                  <p className="text-sm text-red-600">All POs were achieved this week.</p>
                )}
              </div>
            </div>

            {/* PO Analysis Graph - Interactive Bootstrap Version */}
            <div className="card">
              <div className="card-header bg-primary text-white">
                <h5 className="card-title mb-0">
                  <i className="bi bi-graph-up me-2"></i>
                  Program Outcome Analysis
                </h5>
              </div>
              <div className="card-body">
                {(() => {
                  const chartMax = 100; // percentage scale
                  const chartHeight = 300; // px
                  const yStep = 10;
                  const steps = chartMax / yStep; // 10
                  const stepPx = chartHeight / steps; // align ticks with grid
                  const yTicks = Array.from({ length: steps + 1 }, (_, k) => k * yStep);
                  // Alignment constants for bars and x-axis labels
                  const itemWidth = 40; // px per PO column
                  const itemGap = 8; // px gap between columns
                  
                  return (
                    <div className="w-100 overflow-x-auto">
                      <div className="d-flex position-relative" style={{ minHeight: chartHeight + 60 }}>
                        {/* Y Axis labels */}
                        <div className="position-relative pe-2 text-muted small" style={{ height: chartHeight, width: 40 }}>
                          {yTicks.map((t, idx) => {
                            const y = chartHeight - (t / chartMax) * chartHeight;
                            return (
                              <div
                                key={idx}
                                className="position-absolute end-0"
                                style={{ top: `${y}px`, transform: 'translateY(-50%)' }}
                              >
                                {t}%
                              </div>
                            );
                          })}
                        </div>
                        
                        {/* Interactive Chart Area */}
                        <div className="flex-fill position-relative">
                          {/* Grid Background */}
                          <div 
                            className="position-absolute w-100 h-100"
                            style={{
                              backgroundImage: 'linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px), linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px)',
                              backgroundSize: `${stepPx}px ${stepPx}px, ${stepPx}px ${stepPx}px`,
                              backgroundColor: 'rgba(13, 110, 253, 0.05)',
                              height: chartHeight
                            }}
                          ></div>
                          
                          {/* Interactive Bars */}
                          <div className="d-flex align-items-end h-100 px-3" style={{ height: chartHeight }}>
                            {scores.map((v, i) => {
                              const height = Math.max(4, Math.round((v / chartMax) * chartHeight));
                              const isAchieved = v > 0;
                              const barColor = isAchieved ? 'bg-primary' : 'bg-danger';
                              const hoverColor = isAchieved ? 'bg-primary' : 'bg-danger';
                              
                              return (
                                <div 
                                  key={`${i}-${v}`} 
                                  className="d-flex flex-column align-items-center position-relative" 
                                  style={{ width: itemWidth, marginRight: i < scores.length - 1 ? itemGap : 0 }}
                                  data-bs-toggle="tooltip" 
                                  data-bs-placement="top" 
                                  title={`PO${i + 1}: ${v}% - ${isAchieved ? 'Achieved' : 'Not Met'}`}
                                  data-bs-title={`PO${i + 1}: ${v}% - ${isAchieved ? 'Achieved' : 'Not Met'}`}
                                >
                                  <div 
                                    className={`w-100 ${barColor} border border-dark rounded-top position-relative`}
                                    style={{ 
                                      height: height,
                                      boxSizing: 'border-box',
                                      transition: 'all 0.3s ease',
                                      cursor: 'pointer'
                                    }}
                                    onMouseEnter={(e) => {
                                      e.target.style.transform = 'scaleY(1.1)';
                                      e.target.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                                    }}
                                    onMouseLeave={(e) => {
                                      e.target.style.transform = 'scaleY(1)';
                                      e.target.style.boxShadow = 'none';
                                    }}
                                  >
                                    {/* Percentage label on bar */}
                                    {v > 0 && (
                                      <div className="position-absolute top-0 start-50 translate-middle-x text-white small fw-bold">
                                        {v}%
                                      </div>
                                    )}
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      </div>
                      
                      {/* X-axis labels with Bootstrap styling */}
                      <div className="mt-3 d-flex">
                        <div style={{ width: 40 }}></div>
                        <div className="flex-fill px-3">
                          <div className="d-flex">
                            {scores.map((v, i) => (
                              <div key={`lbl-${i}`} className="text-center" style={{ width: itemWidth, marginRight: i < scores.length - 1 ? itemGap : 0 }}>
                                <div className="small fw-bold text-dark">PO{i + 1}</div>
                                <div className={`badge ${v > 0 ? 'bg-primary' : 'bg-danger'} small`}>
                                  {v}%
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })()}
              </div>
            </div>

            {/* PO Details Table - Bootstrap Version */}
            <div className="card">
              <div className="card-header bg-info text-white">
                <h5 className="card-title mb-0">
                  <i className="bi bi-table me-2"></i>
                  Program Outcome Details
                </h5>
              </div>
              <div className="card-body p-0">
                <div className="table-responsive">
                  <table className="table table-hover mb-0">
                    <thead className="table-light">
                      <tr>
                        <th className="px-3 py-3">
                          <i className="bi bi-hash me-1"></i>
                          PO Code
                        </th>
                        <th className="px-3 py-3">
                          <i className="bi bi-list-ul me-1"></i>
                          Program Outcome
                        </th>
                        <th className="px-3 py-3">
                          <i className="bi bi-check-circle me-1"></i>
                          Status
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {scores.map((v, i) => (
                        <tr key={i} className="align-middle">
                          <td className="px-3 py-3">
                            <span className="badge bg-primary fs-6">PO{i + 1}</span>
                          </td>
                          <td className="px-3 py-3">
                            <small className="text-muted">{PO_DESCRIPTIONS[i] || ''}</small>
                          </td>
                          <td className="px-3 py-3">
                            <span className={`badge ${v > 0 ? 'bg-primary' : 'bg-danger'} fs-6`}>
                              <i className={`bi ${v > 0 ? 'bi-check-circle' : 'bi-x-circle'} me-1`}></i>
                              {v > 0 ? 'Achieved' : 'Not Met'}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            {/* Other Activities */}
            {otherActivities.length > 0 && (
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-yellow-800 mb-3">Other Activities Observed</h5>
                <p className="text-sm text-yellow-700 mb-2">Activities that don't directly map to specific Program Outcomes:</p>
                <ul className="list-disc list-inside space-y-1 text-sm text-yellow-700">
                  {otherActivities.map((s, i) => (
                    <li key={`oth-${i}`}>{s}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}


