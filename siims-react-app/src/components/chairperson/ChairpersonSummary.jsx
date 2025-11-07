import React, { useEffect, useMemo, useState } from "react";
import axiosClient from "../../api/axiosClient";

export default function ChairpersonSummary({ coordinatorId, sectionId = null, week, refreshTrigger, onExportReady }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [summary, setSummary] = useState("");
  const [scores, setScores] = useState(Array.from({ length: 15 }, () => 0));
  const [hitList, setHitList] = useState([]);
  const [notHitList, setNotHitList] = useState([]);
  const [recommendations, setRecommendations] = useState([]);
  const [noSectionStudents, setNoSectionStudents] = useState(false);
  const [wordBasedPercent, setWordBasedPercent] = useState(0);
  const [contextBasedPercent, setContextBasedPercent] = useState(0);
  const [wordBasedContributions, setWordBasedContributions] = useState(Array.from({ length: 15 }, () => 0));
  const [contextBasedContributions, setContextBasedContributions] = useState(Array.from({ length: 15 }, () => 0));

  const PO_DESCRIPTIONS = useMemo(() => ([
    "Apply knowledge of computing, science, and mathematics in solving computing/IT-related problems through critical and creative thinking.",
    "Use current best practices and standards in solving complex computing/IT-related problems and requirements.",
    "Analyze complex computing/IT-related problems by applying analytical and quantitative reasoning, and define the computing requirements appropriate to its solution.",
    "Identify and analyze user needs and take them into account in the selection, creation, evaluation, and administration of computer-based systems.",
    "Design creatively, implement, and evaluate different computer-based systems, processes, components, or programs to meet desired needs and requirements under various constraints.",
    "Integrate effectively the IT-based solutions into the user environment with appropriate consideration for public health and safety, cultural, societal, and environmental concerns.",
    "Select, adapt, and apply appropriate techniques, resources, skills, and modern computing tools to complex computing activities, with an understanding of the limitations.",
    "Function effectively as an individual, or work collaboratively and respectfully as a member or leader in diverse development teams and in multidisciplinary and/or multicultural settings.",
    "Assist in the creation of an effective IT project plan.",
    "Communicate effectively in both oral and written form by being able to deliver and comprehend instructions clearly; and present persuasively to diverse audiences the complex computing/IT-related ideas and perspectives.",
    "Assess local and global impact of computing and information technology on individuals, organizations, and society.",
    "Act in recognition of professional, ethical, legal, security, and social responsibilities in the utilization of information technology.",
    "Recognize the need to engage in independent learning and stay updated with the latest developments in specialized IT fields such as Database Management and Information Systems, Network Design and Administration, and Computer Vision and Image Processing for continual professional development.",
    "Participate in the generation of new knowledge or in research and development projects aligned with local and national development agendas or goals, contributing to the local and national economy.",
    "Preserve and promote Filipino historical and cultural heritage.",
  ]), []);

  async function loadSummary() {
    try {
      setError(null);
      setNoSectionStudents(false);

      // helper: fetch entries for students under coordinator and compute PO scores
      const authHeaders = {
        Accept: "application/json",
        Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
      };

      const apiBase = import.meta.env.VITE_API_BASE_URL;

      let poContextHitFromBackend = null; // holds AI contextual PO hits like ['PO1','PO3'] when available
      let poWordHitFromBackend = null; // holds AI keyword-based PO hits when available
      let posHitFromBackend = null; // holds OpenAI's pos_hit array (complete PO analysis)

      const computeScores = async (skipHitLists = false) => {
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
            const matchesCoordinator = String(sid ?? '') === String(coordinatorId ?? '');
            // Also filter by section if sectionId is provided
            if (matchesCoordinator && sectionId) {
              // Check multiple possible field names for section ID
              const section_id = s.section_id ?? s.sectionId ?? s.sectionID ?? s.section?.id ?? (typeof s.section === 'object' && s.section ? s.section.id : null);
              return String(section_id ?? '') === String(sectionId ?? '');
            }
            return matchesCoordinator;
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
              let matchesCoordinator = false;
              for (const key of coordinatorKeyNames) {
                if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                  matchesCoordinator = String(s[key] ?? "") === String(coordinatorId ?? "");
                  break;
                }
              }
              if (!matchesCoordinator) {
                const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
                const cid = c ? (c.id ?? c.coordinator_id) : undefined;
                matchesCoordinator = String(cid ?? "") === String(coordinatorId ?? "");
              }
              // Also filter by section if sectionId is provided
              if (matchesCoordinator && sectionId) {
                const section_id = s.section_id ?? s.sectionId ?? s.sectionID ?? s.section?.id;
                return String(section_id ?? '') === String(sectionId ?? '');
              }
              return matchesCoordinator;
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
        // Don't check for empty students here - wait for backend API response
        // The backend will handle filtering and return empty results if no students/entries exist
        // This prevents false "no students" errors when section filtering is working correctly
        if (weekEntries.length === 0) {
          // No week entries but students exist
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
        
        // Reference Keywords/Verbs for PO Matching (Guidance Only)
        const keywordSets = [
          // PO1: Apply knowledge of computing, science, and mathematics
          ["apply", "compute", "calculate", "solve", "use knowledge", "mathematics", "math", "science", "algorithm", "critical thinking", "creative thinking"],
          // PO2: Use current best practices and standards
          ["standard", "best practice", "quality", "performance", "requirement", "best practices", "standards", "policy", "method", "procedure"],
          // PO3: Analyze complex computing problems
          ["analyze", "troubleshoot", "test", "debug", "identify", "evaluate", "analysis", "problem", "root cause", "diagnose", "quantitative reasoning"],
          // PO4: Identify and analyze user needs
          ["user need", "requirement analysis", "evaluation", "feedback", "user needs", "stakeholder", "ux", "usability", "user feedback"],
          // PO5: Design, implement, and evaluate systems
          ["design", "develop", "implement", "create", "build", "deploy", "evaluate", "system", "component", "program", "constraints"],
          // PO6: Integrate IT solutions
          ["integrate", "adapt", "maintain", "environment", "safety", "sustainability", "cultural", "societal", "public health", "environmental"],
          // PO7: Select and apply appropriate techniques and tools
          ["tool", "modern technology", "programming", "configure", "software", "technique", "resource", "skill", "computing tool", "technology", "framework", "library", "platform"],
          // PO8: Function effectively in teams
          ["team", "collaborate", "assist", "coordinate", "leader", "individual", "member", "diverse", "multidisciplinary", "multicultural", "group"],
          // PO9: Assist in creation of effective IT project plan
          ["plan", "project plan", "timeline", "documentation", "scheduling", "project", "schedule", "it project"],
          // PO10: Communicate effectively
          ["communicate", "present", "report", "explain", "document", "oral", "written", "deliver", "comprehend", "instructions", "persuasive", "audience"],
          // PO11: Assess local and global impact
          ["impact", "society", "organization", "community", "global", "local", "individual", "assess"],
          // PO12: Act with professional, ethical responsibilities
          ["ethics", "privacy", "law", "responsibility", "security", "professionalism", "ethical", "legal", "compliance", "professional", "social responsibility"],
          // PO13: Engage in independent learning
          ["learn independently", "explore", "research", "self-study", "improve skills", "learn", "independent learning", "latest", "new skill", "continual professional development", "stay updated"],
          // PO14: Participate in research and development
          ["research", "innovation", "development", "contribution", "national goal", "experiment", "study", "investigation", "new knowledge", "local", "national", "economy"],
          // PO15: Preserve Filipino historical and cultural heritage
          ["filipino", "culture", "heritage", "values", "historical", "cultural heritage", "tradition", "filipino culture"],
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
        const totalKeywordCount = counts.reduce((a, b) => a + b, 0) || 1; // avoid div/0

        // Compute keyword-only percentages (unrounded used for weighted blend; round only final)
        const keywordScore = counts.map((c) => (c / totalKeywordCount) * 100);

        // Build AI contextual scores from ALL OpenAI PO data (hybrid approach)
        // Combine pos_hit, poContextHit, and poWordHit to get complete AI analysis
        const allAIPOs = new Set();
        
        // Add POs from pos_hit (primary source - complete OpenAI analysis)
        if (Array.isArray(posHitFromBackend) && posHitFromBackend.length > 0) {
          posHitFromBackend.forEach(item => {
            const po = typeof item === 'string' ? item : (item?.po || '');
            if (po && po.match(/^PO\d+$/)) {
              allAIPOs.add(po);
            }
          });
        }
        
        // Add POs from poContextHit (contextual analysis from OpenAI)
        if (Array.isArray(poContextHitFromBackend)) {
          poContextHitFromBackend.forEach(po => {
            if (typeof po === 'string' && po.match(/^PO\d+$/)) {
              allAIPOs.add(po);
            }
          });
        }
        
        // Add POs from poWordHit (keyword-based analysis from OpenAI)
        if (Array.isArray(poWordHitFromBackend)) {
          poWordHitFromBackend.forEach(po => {
            if (typeof po === 'string' && po.match(/^PO\d+$/)) {
              allAIPOs.add(po);
            }
          });
        }
        
        // Build AI scores array (0..1) - 1 if PO is found in ANY OpenAI analysis, 0 otherwise
        const aiScores = Array.from({ length: 15 }, (_, i) => {
          const code = `PO${i + 1}`;
          return allAIPOs.has(code) ? 1 : 0;
        });
        
        console.log('Hybrid AI PO analysis:', {
          pos_hit_count: Array.isArray(posHitFromBackend) ? posHitFromBackend.length : 0,
          poContextHit_count: Array.isArray(poContextHitFromBackend) ? poContextHitFromBackend.length : 0,
          poWordHit_count: Array.isArray(poWordHitFromBackend) ? poWordHitFromBackend.length : 0,
          allAIPOs: Array.from(allAIPOs),
          aiScores: aiScores
        });

        // Check if we have OpenAI data
        const hasAnyAIScore = aiScores.some((v) => v > 0);

        // HYBRID APPROACH: Combine keyword matching + OpenAI analysis
        // Weights: 40% keyword, 60% OpenAI
        const alpha = 0.4; // keyword weight
        const beta = 0.6;  // AI weight

        // Build final scores using HYBRID approach
        // CRITICAL: Graph MUST match "Program Outcomes Achieved" section exactly
        // Only POs in pos_hit should have scores > 0 (pos_hit is the source of truth)
        
        // First, get the confirmed POs from OpenAI's pos_hit (primary source of truth)
        const confirmedPOsFromPosHit = new Set();
        if (Array.isArray(posHitFromBackend) && posHitFromBackend.length > 0) {
          posHitFromBackend.forEach(item => {
            const po = typeof item === 'string' ? item : (item?.po || '');
            if (po && po.match(/^PO(\d+)$/)) {
              confirmedPOsFromPosHit.add(po);
            }
          });
        }
        
        // Calculate weighted contributions for each PO (for nested bar display)
        const wordContributions = Array.from({ length: 15 }, (_, i) => {
          const k = keywordScore[i] || 0;
          // Word-based contribution = 40% of keyword score
          return Math.round(alpha * k);
        });
        
        const contextContributions = Array.from({ length: 15 }, (_, i) => {
          const a = aiScores[i] || 0;
          // Context-based contribution = 60% of AI score (0-1 scale * 100)
          return Math.round(beta * a * 100);
        });
        
        // Build final scores - ONLY for POs confirmed in pos_hit
        let finalScores = Array.from({ length: 15 }, (_, i) => {
          const poCode = `PO${i + 1}`;
          const k = keywordScore[i] || 0;
          const a = aiScores[i] || 0;
          
          // ONLY calculate score if this PO is in OpenAI's pos_hit (confirmed achievements)
          if (confirmedPOsFromPosHit.has(poCode)) {
            // PO is confirmed by OpenAI - use hybrid formula: 40% keyword + 60% AI
            const hybridScore = (alpha * k) + (beta * a * 100);
            // Ensure minimum score of 50% for confirmed POs to ensure visible bars
            return Math.max(50, Math.round(hybridScore));
          }
          
          // PO is NOT in pos_hit - score must be 0 (graph should NOT show it)
          return 0;
        });

        // If OpenAI/AI data missing entirely, fallback to keyword-only rounded percentages
        // But only if we have no pos_hit data at all
        if (!hasAnyAIScore && confirmedPOsFromPosHit.size === 0) {
          finalScores = keywordScore.map((k) => Math.round(k));
          // In fallback mode, contributions are keyword-only
          for (let i = 0; i < 15; i++) {
            wordContributions[i] = Math.round(alpha * keywordScore[i]);
            contextContributions[i] = 0;
          }
        }
        
        // Final validation: Double-check that graph matches pos_hit exactly
        // If a PO is in pos_hit but score is 0, calculate it
        if (Array.isArray(posHitFromBackend) && posHitFromBackend.length > 0) {
          posHitFromBackend.forEach(item => {
            const po = typeof item === 'string' ? item : (item?.po || '');
            if (po && po.match(/^PO(\d+)$/)) {
              const poIndex = parseInt(po.match(/^PO(\d+)$/)[1]) - 1;
              if (poIndex >= 0 && poIndex < 15 && finalScores[poIndex] === 0) {
                // PO is in pos_hit but score is 0 - calculate it using hybrid approach
                const k = keywordScore[poIndex] || 0;
                const a = aiScores[poIndex] || 0;
                const hybridScore = (alpha * k) + (beta * a * 100);
                finalScores[poIndex] = Math.max(50, Math.round(hybridScore));
                // Update contributions for this PO
                wordContributions[poIndex] = Math.round(alpha * k);
                contextContributions[poIndex] = Math.round(beta * a * 100);
              }
            }
          });
        }
        
        // CRITICAL: Zero out any POs that are NOT in pos_hit (source of truth)
        // This ensures graph ONLY shows POs that match "Program Outcomes Achieved"
        for (let i = 0; i < 15; i++) {
          const poCode = `PO${i + 1}`;
          if (!confirmedPOsFromPosHit.has(poCode)) {
            finalScores[i] = 0;
          }
        }
        
        console.log('Graph scores aligned with pos_hit:', {
          confirmedPOs: Array.from(confirmedPOsFromPosHit),
          finalScores: finalScores
        });

        // Calculate word-based and context-based hit percentages for display
        const wordBasedPOs = new Set();
        const contextBasedPOs = new Set();
        
        // Collect POs from word-based hits
        if (Array.isArray(poWordHitFromBackend)) {
          poWordHitFromBackend.forEach(po => {
            if (typeof po === 'string' && po.match(/^PO\d+$/)) {
              wordBasedPOs.add(po);
            }
          });
        }
        
        // Collect POs from context-based hits
        if (Array.isArray(poContextHitFromBackend)) {
          poContextHitFromBackend.forEach(po => {
            if (typeof po === 'string' && po.match(/^PO\d+$/)) {
              contextBasedPOs.add(po);
            }
          });
        }
        
        // Calculate percentages based on confirmed POs (pos_hit)
        const totalConfirmedPOs = confirmedPOsFromPosHit.size;
        const wordBasedCount = Array.from(confirmedPOsFromPosHit).filter(po => wordBasedPOs.has(po)).length;
        const contextBasedCount = Array.from(confirmedPOsFromPosHit).filter(po => contextBasedPOs.has(po)).length;
        
        const wordBasedPercent = totalConfirmedPOs > 0 ? Math.round((wordBasedCount / totalConfirmedPOs) * 100) : 0;
        const contextBasedPercent = totalConfirmedPOs > 0 ? Math.round((contextBasedCount / totalConfirmedPOs) * 100) : 0;
        
        console.log('Total keyword matches:', totalKeywordCount, 'KeywordScore% (unrounded):', keywordScore, 'aiScores(0-1):', aiScores, 'Final%:', finalScores);
        console.log('PO Analysis Contribution:', {
          totalConfirmedPOs,
          wordBasedCount,
          contextBasedCount,
          wordBasedPercent: `${wordBasedPercent}%`,
          contextBasedPercent: `${contextBasedPercent}%`
        });
        setScores(finalScores);
        console.log('PO Scores computed (hybrid):', finalScores, 'Total entries:', weekEntries.length, 'Students:', filteredStudents.length);
        
        // Store contribution percentages for display
        setWordBasedPercent(wordBasedPercent);
        setContextBasedPercent(contextBasedPercent);
        setWordBasedContributions(wordContributions);
        setContextBasedContributions(contextContributions);

        // IMPORTANT: Do NOT create hit/not-hit lists from keyword matching
        // ONLY OpenAI's response (from backend) should determine which POs are achieved
        // Keyword matching is ONLY used for hybrid scoring (graph visualization)
        // hitList and notHitList should ONLY come from OpenAI's pos_hit and pos_not_hit arrays
        if (!skipHitLists) {
          console.log('Skipping hardcoded hit/not-hit list generation - only OpenAI determines PO achievements');
        } else {
          console.log('Skipping hitList/notHitList update - skipHitLists is true (backend provided data from OpenAI)');
        }
      };

      // Try backend summary first (Chair-specific route). If it fails, fall back to client-side.
      try {
        // Use GET with query params to avoid 419 in some local setups
        const qp = new URLSearchParams();
        if (coordinatorId) qp.set('coordinatorId', coordinatorId);
        if (sectionId) qp.set('sectionId', String(sectionId));
        // For "overall", don't set week parameter (or set to null/0) so backend returns all weeks
        if (week && week !== "overall") {
          qp.set('week', String(week));
        }
        qp.set('useGPT', '1');
        const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`, {
          method: 'GET',
          headers: authHeaders,
          credentials: 'include',
        });
        
        // Handle 503 Service Unavailable (OpenAI not available)
        if (resp.status === 503) {
          const data = await resp.json().catch(() => ({}));
          setError(data?.error || 'OpenAI is not available right now');
          setSummary("");
          setScores(Array.from({ length: 15 }, () => 0));
          setHitList([]);
          setNotHitList([]);
          setRecommendations([]);
          setNoSectionStudents(false);
          setLoading(false);
          return;
        }
        
        if (resp.ok) {
          const data = await resp.json();
          console.log('Backend response:', data);
          
          // Check if OpenAI is unavailable
          if (data?.openai_unavailable || data?.error === 'OpenAI is not available right now') {
            setError('OpenAI is not available right now');
            setSummary("");
            setScores(Array.from({ length: 15 }, () => 0));
            setHitList([]);
            setNotHitList([]);
            setRecommendations([]);
            setNoSectionStudents(false);
            setLoading(false);
            return;
          }
          
          // Check if backend returned empty results (no weekly entries) for a selected section
          // This indicates the section has no students or no weekly entries
          const hasWeeklyEntries = data && (
            (Array.isArray(data.pos_hit) && data.pos_hit.length > 0) ||
            data.summary ||
            (data.activities && data.activities.length > 0) ||
            (data.learnings && data.learnings.length > 0) ||
            (data['summary for this section on a week'])
          );
          
          if (!hasWeeklyEntries && sectionId) {
            // Backend confirmed no data for this section
            setNoSectionStudents(true);
            setSummary("");
            setScores(Array.from({ length: 15 }, () => 0));
            setHitList([]);
            setNotHitList([]);
            setRecommendations([]);
            setLoading(false);
            return;
          }
          
          // Clear the no students error if we have data
          setNoSectionStudents(false);
          setError(null);
          
          // Capture ALL OpenAI PO analysis data for hybrid scoring
          // pos_hit: Primary source - complete PO analysis from OpenAI
          if (Array.isArray(data?.pos_hit)) {
            posHitFromBackend = data.pos_hit;
            console.log('Captured pos_hit from backend for hybrid scoring:', posHitFromBackend);
          }
          
          // poContextHit: Contextual PO hits from OpenAI
          if (Array.isArray(data?.poContextHit)) {
            poContextHitFromBackend = data.poContextHit;
            console.log('Captured poContextHit from backend for hybrid scoring:', poContextHitFromBackend);
          }
          
          // poWordHit: Keyword-based PO hits from OpenAI
          if (Array.isArray(data?.poWordHit)) {
            poWordHitFromBackend = data.poWordHit;
            console.log('Captured poWordHit from backend for hybrid scoring:', poWordHitFromBackend);
          }
          
          // Extract AI-generated recommendations from backend
          if (data?.recommendations && Array.isArray(data.recommendations) && data.recommendations.length > 0) {
            setRecommendations(data.recommendations);
            console.log('Loaded AI recommendations from backend:', data.recommendations);
          }
          
          // Extract POS hit/not-hit arrays from backend for consistent display (BEFORE handling overall case)
          // First, try pos_hit array
          let hits = [];
          console.log('Raw backend data.pos_hit:', data?.pos_hit);
          console.log('Raw backend data.poContextHit:', data?.poContextHit);
          console.log('Raw backend data.poWordHit:', data?.poWordHit);
          
          if (Array.isArray(data?.pos_hit) && data.pos_hit.length > 0) {
            hits = data.pos_hit.map(item => ({
              po: typeof item === 'string' ? item : (item?.po || ''),
              reason: typeof item === 'object' && item?.reason ? item.reason : 'Evidence found in activities and learnings'
            })).filter(item => item.po && item.po.trim() !== '');
            console.log('Extracted hits from pos_hit:', hits);
          }
          
          // Fallback: If pos_hit is empty but poContextHit exists (from OpenAI), use that
          // These are still from OpenAI's response, just different fields in the JSON structure
          if (hits.length === 0 && Array.isArray(data?.poContextHit) && data.poContextHit.length > 0) {
            hits = data.poContextHit.map(poCode => {
              const code = typeof poCode === 'string' ? poCode : (poCode?.po || poCode);
              return {
                po: code,
                reason: 'Achieved through contextual activities and practical application of knowledge (OpenAI analysis)'
              };
            }).filter(item => item.po && item.po.trim() !== '');
            console.log('Using poContextHit from OpenAI response as fallback for hits:', hits);
          }
          
          // Fallback: If still empty but poWordHit exists (from OpenAI), use that
          // These are still from OpenAI's response, just different fields in the JSON structure
          if (hits.length === 0 && Array.isArray(data?.poWordHit) && data.poWordHit.length > 0) {
            hits = data.poWordHit.map(poCode => {
              const code = typeof poCode === 'string' ? poCode : (poCode?.po || poCode);
              return {
                po: code,
                reason: 'Achieved through keyword matching and explicit evidence in activities (OpenAI analysis)'
              };
            }).filter(item => item.po && item.po.trim() !== '');
            console.log('Using poWordHit from OpenAI response as fallback for hits:', hits);
          }
          
          // Set hitList immediately - this is the source of truth from backend
          // CRITICAL: Set this BEFORE calling computeScores to ensure it's not overwritten
          // IMPORTANT: Display ALL POs from backend - NO LIMITS
          if (hits.length > 0) {
            setHitList(hits); // Display ALL hits - no filtering or limiting
            console.log('✅ Set hitList from backend data (ALL POs):', hits.length, 'items:', hits);
          } else {
            console.log('⚠️ No hits found in backend data - pos_hit, poContextHit, and poWordHit are all empty or missing');
            // Keep existing hitList if we have one, don't overwrite with empty array
          }
          
          let notHits = [];
          if (Array.isArray(data?.pos_not_hit) && data.pos_not_hit.length > 0) {
            // Use backend's pos_not_hit array directly for consistency
            notHits = data.pos_not_hit.map(item => ({
              po: typeof item === 'string' ? item : (item?.po || ''),
              reason: typeof item === 'object' && item?.reason ? item.reason : ''
            })).filter(item => item.po);
          }
          
          // CRITICAL: Ensure ALL 15 POs are accounted for
          // If a PO is not in hitList, it MUST be in notHitList
          const allPOs = Array.from({ length: 15 }, (_, i) => `PO${i + 1}`);
          const achievedPOs = new Set(hits.map(h => h.po));
          const notAchievedPOs = new Set(notHits.map(h => h.po));
          
          // Find POs that are missing from both lists
          const missingPOs = allPOs.filter(po => !achievedPOs.has(po) && !notAchievedPOs.has(po));
          
          // Add missing POs to notHitList with default reason
          if (missingPOs.length > 0) {
            const defaultReasons = {
              'PO1': 'No evidence of mathematical or computational knowledge application.',
              'PO2': 'No evidence of analyzing complex computing problems.',
              'PO3': 'No evidence of designing or implementing computing-based solutions.',
              'PO4': 'No evidence of using current techniques, skills, and tools.',
              'PO5': 'No evidence of working effectively in teams.',
              'PO6': 'No evidence of effective communication.',
              'PO7': 'No evidence of assessing local and global impact of computing.',
              'PO8': 'No evidence of professional and ethical responsibilities.',
              'PO9': 'No evidence of effective project planning and management.',
              'PO10': 'No evidence of identifying and analyzing user needs.',
              'PO11': 'No evidence of integrating IT solutions.',
              'PO12': 'No evidence of testing or quality assurance activities.',
              'PO13': 'No evidence of continuing professional development.',
              'PO14': 'No evidence of research and development participation.',
              'PO15': 'No evidence of preserving Filipino historical and cultural heritage.'
            };
            
            missingPOs.forEach(po => {
              notHits.push({
                po: po,
                reason: defaultReasons[po] || `No evidence of achieving ${po} based on activities and learnings.`
              });
            });
            
            console.log(`Added ${missingPOs.length} missing POs to notHitList:`, missingPOs);
          }
          
          setNotHitList(notHits);
          console.log('Set notHitList from backend data (all 15 POs accounted for):', notHits.length, 'items');
          
          // Compute scores for the graph ONLY (don't update hit/not-hit lists)
          // CRITICAL: Always pass skipHitLists=true when we have backend data to prevent overwriting
          console.log('Calling computeScores with skipHitLists=true (preserving backend hitList)');
          await computeScores(true); // Force skipHitLists=true to preserve backend data
          
          // Extract AI-generated recommendations from backend
          if (data?.recommendations && Array.isArray(data.recommendations) && data.recommendations.length > 0) {
            setRecommendations(data.recommendations);
            console.log('Loaded AI recommendations from backend:', data.recommendations);
          }
          
          if (data?.summary) {
            const clean = String(data.summary)
              .replace(/<\s*\/? .*?>/g, ' ')
              .replace(/&nbsp;/gi, ' ')
              .replace(/&amp;/gi, '&')
              .replace(/&lt;/gi, '<')
              .replace(/&gt;/gi, '>')
              .replace(/\s+/g, ' ')
              .trim();

            // Backend already processed summary with OpenAI, use it directly for better performance
            setSummary(clean);

            if (data?.keywordScores && Array.isArray(data.keywordScores)) {
              setScores(data.keywordScores);
              console.log('Using backend PO scores:', data.keywordScores);
            } else {
              // CRITICAL: Pass skipHitLists=true to preserve backend hitList data
              await computeScores(true);
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
            // CRITICAL: Pass skipHitLists=true to preserve backend hitList data if it exists
            await computeScores(true);
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
          // Reference Keywords/Verbs for PO Matching (Guidance Only)
          const keywordSets = [
            // PO1: Apply knowledge of computing, science, and mathematics
            ["apply", "compute", "calculate", "solve", "use knowledge", "mathematics", "math", "science", "algorithm", "critical thinking", "creative thinking"],
            // PO2: Use current best practices and standards
            ["standard", "best practice", "quality", "performance", "requirement", "best practices", "standards", "policy", "method", "procedure"],
            // PO3: Analyze complex computing problems
            ["analyze", "troubleshoot", "test", "debug", "identify", "evaluate", "analysis", "problem", "root cause", "diagnose", "quantitative reasoning"],
            // PO4: Identify and analyze user needs
            ["user need", "requirement analysis", "evaluation", "feedback", "user needs", "stakeholder", "ux", "usability", "user feedback"],
            // PO5: Design, implement, and evaluate systems
            ["design", "develop", "implement", "create", "build", "deploy", "evaluate", "system", "component", "program", "constraints"],
            // PO6: Integrate IT solutions
            ["integrate", "adapt", "maintain", "environment", "safety", "sustainability", "cultural", "societal", "public health", "environmental"],
            // PO7: Select and apply appropriate techniques and tools
            ["tool", "modern technology", "programming", "configure", "software", "technique", "resource", "skill", "computing tool", "technology", "framework", "library", "platform"],
            // PO8: Function effectively in teams
            ["team", "collaborate", "assist", "coordinate", "leader", "individual", "member", "diverse", "multidisciplinary", "multicultural", "group"],
            // PO9: Assist in creation of effective IT project plan
            ["plan", "project plan", "timeline", "documentation", "scheduling", "project", "schedule", "it project"],
            // PO10: Communicate effectively
            ["communicate", "present", "report", "explain", "document", "oral", "written", "deliver", "comprehend", "instructions", "persuasive", "audience"],
            // PO11: Assess local and global impact
            ["impact", "society", "organization", "community", "global", "local", "individual", "assess"],
            // PO12: Act with professional, ethical responsibilities
            ["ethics", "privacy", "law", "responsibility", "security", "professionalism", "ethical", "legal", "compliance", "professional", "social responsibility"],
            // PO13: Engage in independent learning
            ["learn independently", "explore", "research", "self-study", "improve skills", "learn", "independent learning", "latest", "new skill", "continual professional development", "stay updated"],
            // PO14: Participate in research and development
            ["research", "innovation", "development", "contribution", "national goal", "experiment", "study", "investigation", "new knowledge", "local", "national", "economy"],
            // PO15: Preserve Filipino historical and cultural heritage
            ["filipino", "culture", "heritage", "values", "historical", "cultural heritage", "tradition", "filipino culture"],
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

  // Clear previous results immediately when dropdown values change
  useEffect(() => {
    // Immediately clear all previous data and show loading
    setLoading(true);
    setWordBasedPercent(0);
    setContextBasedPercent(0);
    setWordBasedContributions(Array.from({ length: 15 }, () => 0));
    setContextBasedContributions(Array.from({ length: 15 }, () => 0));
    setSummary("");
    setScores(Array.from({ length: 15 }, () => 0));
    setHitList([]);
    setNotHitList([]);
    setRecommendations([]);
    setError(null);
    setNoSectionStudents(false);
    
    // Then load new data
    loadSummary();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [coordinatorId, sectionId, week, refreshTrigger]);

  // Notify parent with the latest exportable data snapshot
  useEffect(() => {
    if (typeof onExportReady !== 'function') return;
    const stripHtml = (t) => String(t || "")
      .replace(/<[^>]*>/g, ' ')
      .replace(/&nbsp;/gi, ' ')
      .replace(/&amp;/gi, '&')
      .replace(/&lt;/gi, '<')
      .replace(/&gt;/gi, '>')
      .replace(/\s+/g, ' ')
      .trim();
    let cleanSummary = stripHtml(summary);
    if (!cleanSummary) {
      try {
        const tmp = document.createElement('div');
        tmp.innerHTML = String(summary || '');
        cleanSummary = String(tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
      } catch {}
    }
    onExportReady({
      coordinatorId,
      sectionId,
      week,
      summaryText: cleanSummary || 'No summary available.',
      scores: Array.isArray(scores) ? scores.slice() : [],
      hitList: Array.isArray(hitList) ? hitList.slice() : [],
      notHitList: Array.isArray(notHitList) ? notHitList.slice() : [],
      poDescriptions: PO_DESCRIPTIONS.slice(),
      exportedAt: new Date().toISOString(),
    });
  }, [onExportReady, coordinatorId, sectionId, week, summary, scores, hitList, notHitList, PO_DESCRIPTIONS]);

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
        {loading && (
          <div className="mb-3 text-sm text-sky-800 bg-sky-50 border border-sky-200 rounded px-3 py-2">
            Loading summary…
          </div>
        )}
        {noSectionStudents && !loading && (
          <div className="mb-3 text-sm text-orange-700 bg-orange-50 border border-orange-200 rounded px-3 py-2">
            No week report available for the selected section. This section has no students assigned.
          </div>
        )}
        {!loading && !!summary && (
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
        {!loading && !summary && !noSectionStudents && (
          <div className="text-gray-500">No summary available.</div>
        )}

        {!loading && summary && (
          <div className="mt-6 space-y-6">
            {/* PO Explanations */}
            <div className="grid md:grid-cols-2 gap-6">
              {/* POs Hit */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-blue-800 mb-3">Program Outcomes Achieved</h5>
                {hitList && hitList.length > 0 ? (
                  <div className="text-sm text-blue-700 leading-relaxed">
                    <p className="mb-2">Based on the analysis of student activities and reports, the following program outcomes have been successfully achieved:</p>
                    <ul className="list-disc list-inside space-y-1 text-blue-800">
                      {hitList.map((h, i) => (
                        <li key={`hit-${i}`}>
                          <strong>{h.po}</strong> — {h.reason ? h.reason.toLowerCase() : 'Evidence found in activities and learnings'}
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

            {/* AI-Generated Recommendations - placed directly after Achieved/Not Met sections */}
            {recommendations.length > 0 && (
              <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                <h5 className="text-lg font-semibold text-emerald-800 mb-3">Recommendations for Improvement</h5>
                {Array.isArray(recommendations) && recommendations.every(r => typeof r === 'string') ? (
                  // AI-generated recommendations (array of strings)
                  <ul className="list-disc list-inside space-y-2 text-sm text-emerald-800 leading-relaxed">
                    {recommendations.map((rec, idx) => (
                      <li key={`rec-${idx}`}>{rec}</li>
                    ))}
                  </ul>
                ) : (
                  // Fallback format (array of objects)
                  <ul className="list-disc list-inside space-y-2 text-sm text-emerald-800 leading-relaxed">
                    {recommendations.map((rec, idx) => (
                      <li key={`rec-${idx}`}>
                        {typeof rec === 'string' ? rec : `${rec.po || ''}: ${rec.tip || rec.recommendation || ''}`}
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            )}

            {/* PO Analysis Graph - Interactive (screen only) */}
            <div className="card screen-only">
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
                              
                              // Calculate nested bar contribution (word-based - 40% weight)
                              const wordContrib = wordBasedContributions[i] || 0;
                              const contextContrib = contextBasedContributions[i] || 0;
                              // Show word-based contribution as nested bar (40% weight)
                              const nestedContrib = wordContrib;
                              const nestedHeight = v > 0 && nestedContrib > 0 
                                ? Math.max(2, Math.round((nestedContrib / chartMax) * chartHeight))
                                : 0;
                              
                              return (
                                <div 
                                  key={`${i}-${v}`} 
                                  className="d-flex flex-column align-items-center position-relative" 
                                  style={{ width: itemWidth, marginRight: i < scores.length - 1 ? itemGap : 0 }}
                                  data-bs-toggle="tooltip" 
                                  data-bs-placement="top" 
                                  title={`PO${i + 1}: ${v}% (Word: ${wordContrib}%, Context: ${contextContrib}%) - ${isAchieved ? 'Achieved' : 'Not Met'}`}
                                  data-bs-title={`PO${i + 1}: ${v}% (Word: ${wordContrib}%, Context: ${contextContrib}%) - ${isAchieved ? 'Achieved' : 'Not Met'}`}
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
                                    {/* Nested contribution bar (red outlined rectangle) */}
                                    {v > 0 && nestedContrib > 0 && (
                                      <div
                                        className="position-absolute bottom-0 start-0 w-100"
                                        style={{
                                          height: `${(nestedHeight / height) * 100}%`,
                                          border: '2px solid #dc3545',
                                          backgroundColor: 'rgba(220, 53, 69, 0.2)',
                                          boxSizing: 'border-box',
                                          borderRadius: '2px 2px 0 0'
                                        }}
                                      />
                                    )}
                                    
                                    {/* Percentage label on bar */}
                                    {v > 0 && (
                                      <div className="position-absolute top-0 start-50 translate-middle-x text-white small fw-bold" style={{ zIndex: 10 }}>
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

            {/* PO Analysis - Print-friendly table (hidden on screen; shown in PDF print) */}
            <div className="print-only" style={{ display: 'none' }}>
              <h5 className="text-lg font-semibold mb-2">Program Outcome Analysis</h5>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                <thead>
                  <tr>
                    <th style={{ textAlign: 'left', border: '1px solid #e5e7eb', padding: 8 }}>PO</th>
                    <th style={{ textAlign: 'left', border: '1px solid #e5e7eb', padding: 8 }}>Score</th>
                  </tr>
                </thead>
                <tbody>
                  {scores.map((v, i) => (
                    <tr key={`print-po-${i}`}>
                      <td style={{ border: '1px solid #e5e7eb', padding: 8 }}>PO{i + 1}</td>
                      <td style={{ border: '1px solid #e5e7eb', padding: 8 }}>
                        <div style={{ width: '100%', height: 10, background: '#e5e7eb', position: 'relative' }}>
                          <div style={{ width: `${Math.max(0, Math.min(100, Number(v) || 0))}%`, height: 10, background: v > 0 ? '#0d6efd' : '#dc3545' }}></div>
                        </div>
                        <div style={{ fontSize: 11, marginTop: 4 }}>{Number(v) || 0}%</div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
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


          </div>
        )}
      </div>
    </div>
  );
}


