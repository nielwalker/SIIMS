import React, { useMemo, useState, useEffect, useCallback } from "react";
import axiosClient from "../api/axiosClient";
import Page from "../components/common/Page";
import Loader from "../components/common/Loader";
import Section from "../components/common/Section";
import Heading from "../components/common/Heading";
import Text from "../components/common/Text";
import DynamicDataGrid from "../components/tables/DynamicDataGrid";

const ViewReportsPage = ({ authorizeRole }) => {
  // Student options for coordinator
  const [students, setStudents] = useState([]);
  const [selectedStudentId, setSelectedStudentId] = useState("");
  const [selectedStudentCompany, setSelectedStudentCompany] = useState("");
  const [availableWeeks, setAvailableWeeks] = useState([]);
  const [selectedWeek, setSelectedWeek] = useState(1);
  const [summaryLoading, setSummaryLoading] = useState(false);
  const [studentSummary, setStudentSummary] = useState("");
  // Coordinator view: recommendations hidden (chairperson only)
  const [totalHours, setTotalHours] = useState(0);
  
  // PO Analysis state
  const [poAnalysisLoading, setPoAnalysisLoading] = useState(false);
  const [posHit, setPosHit] = useState([]);
  const [posNotHit, setPosNotHit] = useState([]);
  const [recommendations, setRecommendations] = useState([]);
  const [poError, setPoError] = useState("");
  const [poScores, setPoScores] = useState(Array.from({ length: 15 }, () => 0));
  const [wordBasedContributions, setWordBasedContributions] = useState(Array.from({ length: 15 }, () => 0));
  const [contextBasedContributions, setContextBasedContributions] = useState(Array.from({ length: 15 }, () => 0));

  // Loading state
  const [loading, setLoading] = useState(false);
  // Row State
  const [rows, setRows] = useState([]);
  // Keep all entries for status computation across weeks
  const [allEntries, setAllEntries] = useState([]);
  // Data grid URL derived from selected student
  // Grid will fetch weekly entries from backend when applicationId is present
  // grid fetch disabled; we fetch and filter locally from weekly-entries/student/{id}
  const gridUrl = undefined; // prevent DynamicDataGrid from issuing a fetch

  // Static Columns
  // Weekly report columns (for weekly-entries rows)
  const weeklyColumns = useMemo(
    () => [
      { field: "student_id", headerName: "Student ID", width: 120, headerClassName: "super-app-theme--header" },
      { field: "week_number", headerName: "Week", width: 90, headerClassName: "super-app-theme--header" },
      { field: "start_date", headerName: "Start Date", width: 130, headerClassName: "super-app-theme--header" },
      { field: "end_date", headerName: "End Date", width: 130, headerClassName: "super-app-theme--header" },
      {
        field: "tasks",
        headerName: "Tasks",
        minWidth: 280,
        flex: 1,
        headerClassName: "super-app-theme--header",
        renderCell: (params) => (
          <div className="whitespace-pre-wrap break-words text-gray-800">{params.value}</div>
        ),
      },
      {
        field: "learnings",
        headerName: "Learnings",
        minWidth: 320,
        flex: 1,
        headerClassName: "super-app-theme--header",
        renderCell: (params) => (
          <div className="whitespace-pre-wrap break-words text-gray-800">{params.value}</div>
        ),
      },
      { field: "no_of_hours", headerName: "No. of hours", width: 130, headerClassName: "super-app-theme--header" },
      { field: "created_at", headerName: "Created At", width: 130, headerClassName: "super-app-theme--header" },
    ],
    []
  );

  // Action Column
  // Columns for weekly entries — no action column here
  const columns = weeklyColumns;

  // Fetch coordinator's students for dropdown
  useEffect(() => {
    let cancel = false;
    (async () => {
      try {
        setLoading(true);
        const resp = await fetch(
          `${import.meta.env.VITE_API_BASE_URL}/api/v1/coordinator/students`,
          {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
            },
            credentials: "include",
          }
        );
        const data = await resp.json().catch(() => []);
        if (cancel) return;
        const list = Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data)
          ? data
          : [];
        const opts = list.map((s) => {
          const id = String(s.id ?? s.student_id ?? s.user_id ?? "");
          const first = s.first_name || s.firstName || "";
          const last = s.last_name || s.lastName || "";
          const name = [first, last].filter(Boolean).join(" ") || s.name || s.fullName || id;
          const company = s.company?.name || s.company_name || s.companyName || s.latest_application_company_name || "—";
          return { id, name, company };
        });
        setStudents(opts);
      } catch (e) {
        setStudents([]);
      } finally {
        if (!cancel) setLoading(false);
      }
    })();
    return () => {
      cancel = true;
    };
  }, []);

  // Track selected student's company name
  useEffect(() => {
    let cancelled = false;
    const setFromOption = () => {
      const s = students.find((x) => String(x.id) === String(selectedStudentId));
      if (s && s.company) {
        setSelectedStudentCompany(s.company);
        return true;
      }
      return false;
    };
    const extractCompanyName = (r = {}) => {
      const direct = r.company?.name || r.company_name || r.companyName;
      const fromApp = r.latestApplication?.workPost?.office?.company?.name || r.latest_application_company_name;
      const fromWE = Array.isArray(r.workExperiences) && r.workExperiences.length > 0
        ? (r.workExperiences[0].company_name || r.workExperiences[0].companyName)
        : undefined;
      return direct || fromApp || fromWE || "";
    };
    const fetchFallback = async () => {
      const endpoints = [
        '/api/v1/users/students/get-all-students',
        '/api/v1/chairperson/students',
        '/api/v1/users/students',
      ];
      for (const ep of endpoints) {
        try {
          const resp = await fetch(`${import.meta.env.VITE_API_BASE_URL}${ep}`, {
            headers: {
              Accept: 'application/json',
              Authorization: `Bearer ${JSON.parse(localStorage.getItem('ACCESS_TOKEN'))}`,
            },
            credentials: 'include',
          });
          if (!resp.ok) continue;
          const payload = await resp.json().catch(() => ({}));
          const list = Array.isArray(payload?.data)
            ? payload.data
            : Array.isArray(payload?.initial_students)
            ? payload.initial_students
            : Array.isArray(payload)
            ? payload
            : [];
          const found = list.find((r) => String(r.id ?? r.student_id ?? r.user_id) === String(selectedStudentId));
          const name = extractCompanyName(found);
          if (name) { if (!cancelled) setSelectedStudentCompany(name); return; }
        } catch (_) { /* try next */ }
      }
      if (!cancelled) setSelectedStudentCompany('—');
    };

    if (selectedStudentId) {
      const ok = setFromOption();
      if (!ok) fetchFallback();
    } else {
      setSelectedStudentCompany("");
    }

    return () => { cancelled = true; };
  }, [students, selectedStudentId]);

  // Fetch available weeks for selected student (OPTIMIZED)
  useEffect(() => {
    let cancel = false;
    if (!selectedStudentId) { 
      setAvailableWeeks([]);
      setRows([]); 
      return; 
    }
    (async () => {
      try {
        setLoading(true);
        const apiBase = import.meta.env.VITE_API_BASE_URL;
        const headers = {
          Accept: "application/json",
          Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
        };

        // OPTIMIZED: Try new endpoint first (single query for weeks only)
        try {
          const qp = new URLSearchParams({ studentId: selectedStudentId });
          const weeksResp = await fetch(
            `${apiBase}/api/v1/coordinator/available-weeks?${qp.toString()}`,
            { headers, credentials: "include" }
          );
          
          if (weeksResp.ok) {
            const weeksData = await weeksResp.json();
            const weeks = Array.isArray(weeksData?.weeks) ? weeksData.weeks : [];
            
            if (!cancel) {
              setAvailableWeeks(weeks);
            }
            
            // Still fetch full entries for table display (but only if we have weeks)
            if (weeks.length > 0) {
              const resp = await fetch(
                `${apiBase}/api/v1/weekly-entries/student/${selectedStudentId}`,
                { headers, credentials: "include" }
              );
              const payload = await resp.json().catch(() => []);
              if (cancel) return;
              const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
              
              // Helper formatters
              const truncate = (s, n = 300) => (s && s.length > n ? s.slice(0, n) + "…" : s);
              const fmtDate = (s) => {
                if (!s) return "";
                const str = String(s).replace('T', ' ');
                return str.slice(0, 16); // YYYY-MM-DD HH:mm
              };

              // Normalize ALL entries for status computation
              const normalizedAll = list.map((e) => ({
                id: e.id,
                student_id: String(e.student_id ?? e.studentId ?? (selectedStudentId ?? "")),
                week_number: Number(e.week_number ?? e.weekNumber ?? e.week),
                start_date: fmtDate(e.start_date ?? e.startDate),
                end_date: fmtDate(e.end_date ?? e.endDate),
                tasks: truncate(String(e.tasks ?? e.task ?? e.activities ?? "").replace(/<\s*\/?[^>]*>/g, " ").replace(/\s+/g, " ").trim()),
                learnings: truncate(String(e.learnings ?? e.learning ?? "").replace(/<\s*\/?[^>]*>/g, " ").replace(/\s+/g, " ").trim()),
                no_of_hours: e.no_of_hours ?? e.hours ?? e.noOfHours ?? 0,
                created_at: fmtDate(e.created_at ?? e.createdAt),
              }));
              setAllEntries(normalizedAll);
              
              // Filter entries by selected week for the grid
              const filtered = normalizedAll.filter((e) => e.week_number === Number(selectedWeek));
              if (!cancel) {
                setRows(filtered);
              }
            } else {
              if (!cancel) {
                setRows([]);
                setAllEntries([]);
              }
            }
            return; // Success with optimized endpoint
          }
        } catch (err) {
          console.warn('Optimized weeks endpoint failed, falling back', err);
        }

        // FALLBACK: Original method (fetch all entries)
        const resp = await fetch(
          `${apiBase}/api/v1/weekly-entries/student/${selectedStudentId}`,
          { headers, credentials: "include" }
        );
        const payload = await resp.json().catch(() => []);
        if (cancel) return;
        const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
        
        // Extract unique week numbers that have data
        const weeksWithData = [...new Set(list.map((e) => {
          const wn = Number(e.week_number ?? e.weekNumber ?? e.week);
          return Number.isNaN(wn) ? null : wn;
        }).filter(Boolean))].sort((a, b) => a - b);
        
        if (!cancel) {
          setAvailableWeeks(weeksWithData);
        }
        
        // Helper formatters
        const truncate = (s, n = 300) => (s && s.length > n ? s.slice(0, n) + "…" : s);
        const fmtDate = (s) => {
          if (!s) return "";
          const str = String(s).replace('T', ' ');
          return str.slice(0, 16); // YYYY-MM-DD HH:mm
        };

        // Normalize ALL entries for status computation
        const normalizedAll = list.map((e) => ({
          id: e.id,
          student_id: String(e.student_id ?? e.studentId ?? (selectedStudentId ?? "")),
          week_number: Number(e.week_number ?? e.weekNumber ?? e.week),
          start_date: fmtDate(e.start_date ?? e.startDate),
          end_date: fmtDate(e.end_date ?? e.endDate),
          tasks: truncate(String(e.tasks ?? e.task ?? e.activities ?? "").replace(/<\s*\/?[^>]*>/g, " ").replace(/\s+/g, " ").trim()),
          learnings: truncate(String(e.learnings ?? e.learning ?? "").replace(/<\s*\/?[^>]*>/g, " ").replace(/\s+/g, " ").trim()),
          no_of_hours: e.no_of_hours ?? e.hours ?? e.noOfHours ?? 0,
          created_at: fmtDate(e.created_at ?? e.createdAt),
        }));
        setAllEntries(normalizedAll);
        
        // Filter entries by selected week for the grid
        const filtered = normalizedAll.filter((e) => e.week_number === Number(selectedWeek));
        setRows(filtered);
      } catch (e) {
        setAvailableWeeks([]);
        setRows([]);
      } finally {
        if (!cancel) setLoading(false);
      }
    })();
    return () => {
      cancel = true;
    };
  }, [selectedStudentId, selectedWeek]);

  // Separate effect to update selectedWeek when available weeks change
  // This prevents infinite loops by not updating state during the fetch effect
  useEffect(() => {
    if (availableWeeks.length > 0 && !availableWeeks.includes(selectedWeek)) {
      setSelectedWeek(availableWeeks[0]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [availableWeeks]); // Only depend on availableWeeks, not selectedWeek

  // Memoize setRows callback to prevent infinite loops when passed to DynamicDataGrid
  const handleSetRows = useCallback((newRows) => {
    setRows(prevRows => {
      // Only update if rows actually changed (shallow comparison)
      if (prevRows.length !== newRows.length) {
        return newRows;
      }
      // Deep comparison only if lengths match
      const changed = prevRows.some((row, idx) => {
        const newRow = newRows[idx];
        if (!newRow) return true;
        return row.id !== newRow.id || 
               row.week_number !== newRow.week_number ||
               row.tasks !== newRow.tasks ||
               row.learnings !== newRow.learnings;
      });
      return changed ? newRows : prevRows;
    });
  }, []);

  // Calculate PO scores for graph display
  const calculatePOScores = useCallback((poData, weeklyEntries) => {
    // Extract text from weekly entries for keyword matching
    const combinedText = weeklyEntries.map(e => 
      `${e.tasks || ''} ${e.learnings || ''}`
    ).join(' ').toLowerCase();
    
    // Keyword sets for each PO
    const keywordSets = [
      ['math', 'mathematics', 'science', 'algorithm', 'compute', 'analysis', 'calculate', 'solve'],
      ['best practice', 'standard', 'policy', 'method', 'procedure', 'protocol', 'quality'],
      ['analyze', 'analysis', 'problem', 'root cause', 'diagnose', 'troubleshoot', 'debug', 'test'],
      ['user need', 'requirement', 'stakeholder', 'ux', 'usability', 'feedback'],
      ['design', 'implement', 'evaluate', 'build', 'develop', 'test', 'setup', 'configure', 'create'],
      ['safety', 'health', 'environment', 'security', 'ethical', 'sustainability'],
      ['tool', 'framework', 'library', 'technology', 'platform', 'software'],
      ['team', 'collaborat', 'leader', 'group', 'meeting'],
      ['plan', 'schedule', 'timeline', 'project plan', 'documentation'],
      ['communicat', 'present', 'documentation', 'write', 'report', 'explain'],
      ['impact', 'society', 'organization', 'community', 'global'],
      ['ethical', 'privacy', 'legal', 'compliance', 'professional'],
      ['learn', 'self-study', 'latest', 'new skill', 'research', 'study'],
      ['research', 'experiment', 'study', 'investigation', 'development'],
      ['filipino', 'heritage', 'culture', 'tradition'],
    ];
    
    // Calculate keyword scores
    const keywordCounts = keywordSets.map(set => {
      let count = 0;
      for (const kw of set) {
        if (combinedText.includes(kw)) count++;
      }
      return count;
    });
    const totalKeywordCount = keywordCounts.reduce((a, b) => a + b, 0) || 1;
    const keywordScores = keywordCounts.map(c => (c / totalKeywordCount) * 100);
    
    // Get confirmed POs from pos_hit
    const confirmedPOs = new Set();
    if (Array.isArray(poData?.pos_hit)) {
      poData.pos_hit.forEach(item => {
        const po = typeof item === 'string' ? item : (item?.po || '');
        if (po && po.match(/^PO\d+$/)) {
          confirmedPOs.add(po);
        }
      });
    }
    
    // Build AI scores (1 if PO is in any AI analysis, 0 otherwise)
    const aiScores = Array.from({ length: 15 }, (_, i) => {
      const code = `PO${i + 1}`;
      return confirmedPOs.has(code) ? 1 : 0;
    });
    
    // Hybrid approach: 40% keyword + 60% AI
    const alpha = 0.4;
    const beta = 0.6;
    
    // Calculate contributions
    const wordContribs = keywordScores.map(k => Math.round(alpha * k));
    const contextContribs = aiScores.map(a => Math.round(beta * a * 100));
    
    // Calculate final scores
    const finalScores = Array.from({ length: 15 }, (_, i) => {
      const poCode = `PO${i + 1}`;
      if (confirmedPOs.has(poCode)) {
        const k = keywordScores[i] || 0;
        const a = aiScores[i] || 0;
        const hybridScore = (alpha * k) + (beta * a * 100);
        return Math.max(50, Math.round(hybridScore));
      }
      return 0;
    });
    
    setPoScores(finalScores);
    setWordBasedContributions(wordContribs);
    setContextBasedContributions(contextContribs);
  }, []);

  // Fetch summary + PO analysis + total hours for coordinator
  useEffect(() => {
    const fetchSummaryAndPOAnalysis = async () => {
      if (!selectedStudentId) {
        setStudentSummary("");
        setPosHit([]);
        setPosNotHit([]);
        setPoError("");
        return;
      }
      try {
        setSummaryLoading(true);
        setPoAnalysisLoading(true);
        setPoError("");
        const resp = await axiosClient.post("/api/v1/summary", {
          studentId: selectedStudentId,
          week: selectedWeek,
          useGPT: true, // Use OpenAI for summarization and PO analysis
          analysisType: "coordinator",
          isOverall: false,
        }, {
          timeout: 90000, // 90 seconds timeout for OpenAI (can be slow)
        });
        const data = resp?.data || {};
        
        // Extract summary
        const cleanHtml = (txt) => String(txt || "")
          .replace(/<\s*\/? .*?>/g, " ")
          .replace(/&nbsp;/gi, " ")
          .replace(/&amp;/gi, "&")
          .replace(/&lt;/gi, "<")
          .replace(/&gt;/gi, ">")
          .replace(/\s+/g, " ")
          .trim();
        const s = cleanHtml(data?.summary || "");
        setStudentSummary(s || "No data available.");
        
        // Extract PO analysis data
        if (Array.isArray(data?.pos_hit)) {
          setPosHit(data.pos_hit);
        } else {
          setPosHit([]);
        }
        
        if (Array.isArray(data?.pos_not_hit)) {
          setPosNotHit(data.pos_not_hit);
        } else {
          setPosNotHit([]);
        }
        
        // Extract recommendations from backend
        if (data?.recommendations && Array.isArray(data.recommendations) && data.recommendations.length > 0) {
          setRecommendations(data.recommendations);
        } else {
          setRecommendations([]);
        }
        
        // Calculate PO scores for graph (use allEntries filtered by week)
        // Use a ref to prevent infinite loops - only calculate when PO data actually changes
        const weekEntries = allEntries.filter(e => e.week_number === Number(selectedWeek));
        if (data?.pos_hit || data?.pos_not_hit) {
          calculatePOScores(data, weekEntries);
        }

        if (data?.openai_unavailable) {
          setPoError("PO Analysis is currently unavailable. Please try again later.");
        }
      } catch (e) {
        // Check if it's a timeout error
        if (e.code === 'ECONNABORTED' || e.message?.includes('timeout')) {
          setStudentSummary("Summary generation is taking longer than expected. Please try again.");
          setPoError("PO Analysis is taking longer than expected. Please try again.");
        } else {
          setStudentSummary("No data available.");
          setPoError("Failed to load PO analysis. Please try again.");
        }
        setPosHit([]);
        setPosNotHit([]);
        setRecommendations([]);
      } finally {
        setSummaryLoading(false);
        setPoAnalysisLoading(false);
      }
    };

    const fetchTotalHours = async () => {
      if (!selectedStudentId) { setTotalHours(0); return; }
      try {
        const resp = await fetch(
          `${import.meta.env.VITE_API_BASE_URL}/api/v1/weekly-entries/student/${selectedStudentId}`,
          {
            headers: {
              Accept: "application/json",
              Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
            },
            credentials: "include",
          }
        );
        const payload = await resp.json().catch(() => []);
        const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
        const sum = list.reduce((acc, e) => acc + Number(e.no_of_hours ?? e.hours ?? 0), 0);
        setTotalHours(sum);
      } catch (e) {
        setTotalHours(0);
      }
    };

    fetchSummaryAndPOAnalysis();
    fetchTotalHours();
    // Remove allEntries and calculatePOScores from dependencies to prevent infinite loops
    // allEntries is used inside but doesn't need to trigger re-fetch
    // calculatePOScores is stable (useCallback with empty deps)
  }, [selectedStudentId, selectedWeek]);

  return (
    <Page>
      <Loader loading={loading} />

      <Section>
        <Heading level={3} text={"View Reports"} />
        <Text className="text-sm text-blue-950">
          This is where you view the student's reports.
        </Text>
        <hr className="my-3" />
      </Section>

      {/* Top-right total hours tracker + company */}
      {selectedStudentId && (
        <div className="flex justify-between gap-3 mb-3 flex-wrap">
          <div className="bg-white border rounded px-4 py-3 shadow-sm">
            <div className="text-xs font-semibold text-gray-600">Company</div>
            <div className="text-lg font-semibold text-gray-900">
              {selectedStudentCompany || "—"}
            </div>
          </div>
          <div className="bg-white border rounded px-4 py-3 shadow-sm min-w-[280px] ml-auto">
            <div className="text-xs font-semibold text-gray-600">Total Hours Accumulated</div>
            <div className="text-2xl font-bold text-gray-900">{totalHours} / 486</div>
            <div className="mt-2 h-2 w-full bg-gray-200 rounded">
              <div
                className="h-2 bg-blue-600 rounded"
                style={{ width: `${Math.min(100, Math.max(0, (Number(totalHours) / 486) * 100)).toFixed(1)}%` }}
              />
            </div>
          </div>
        </div>
      )}

      <div className="mt-3">
        <div className="flex flex-wrap items-center gap-4 bg-gray-50 border rounded px-4 py-3 mb-4">
          <label className="text-sm font-semibold text-gray-700">Student:</label>
          <select
            className="px-3 py-2 border rounded text-gray-900 bg-white"
            value={selectedStudentId}
            onChange={(e) => setSelectedStudentId(e.target.value)}
          >
            <option value="">Select Student</option>
            {students.map((s) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
            {students.length === 0 && <option value="" disabled>-- No students found --</option>}
          </select>

          <label className="text-sm font-semibold text-gray-700">Week:</label>
          <select
            className="px-3 py-2 border rounded text-gray-900 bg-white"
            value={selectedWeek}
            onChange={(e) => setSelectedWeek(Number(e.target.value))}
          >
            {availableWeeks.length > 0 ? (
              availableWeeks.map((w) => (
                <option key={w} value={w}>Week {w}</option>
              ))
            ) : (
              <option value="" disabled>No weeks with data</option>
            )}
          </select>
        </div>

        {/* Summary */}
        {selectedStudentId && (
          <div className="bg-white border rounded p-4 min-h-[80px] text-gray-800 whitespace-pre-wrap break-words mb-4">
            {summaryLoading ? "Analyzing…" : (studentSummary || "No data available.")}
          </div>
        )}

        {/* PO Analysis Section */}
        {selectedStudentId && (
          <div className="bg-white border rounded-lg shadow-sm mb-4">
            <div className="px-4 py-3 border-b bg-gray-50 rounded-t-lg">
              <h4 className="text-lg font-semibold text-gray-800">Program Outcome (PO) Analysis</h4>
            </div>
            <div className="p-4">
              {poError && (
                <div className="mb-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
                  {poError}
                </div>
              )}
              {poAnalysisLoading && (
                <div className="mb-3 text-sm text-sky-800 bg-sky-50 border border-sky-200 rounded px-3 py-2">
                  Loading PO analysis…
                </div>
              )}
              {!poAnalysisLoading && !poError && (
                <div className="grid md:grid-cols-2 gap-6">
                  {/* POs Achieved */}
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h5 className="text-lg font-semibold text-blue-800 mb-3">Program Outcomes Achieved</h5>
                    {posHit && posHit.length > 0 ? (
                      <div className="text-sm text-blue-700 leading-relaxed">
                        <p className="mb-2">Based on the analysis of student activities and reports, the following program outcomes have been successfully achieved:</p>
                        <ul className="list-disc list-inside space-y-1 text-blue-800">
                          {posHit.map((h, i) => (
                            <li key={`hit-${i}`}>
                              <strong>{h.po || h}</strong> — {h.reason ? h.reason.toLowerCase() : 'Evidence found in activities and learnings'}
                            </li>
                          ))}
                        </ul>
                        <p className="mt-3 text-blue-600">
                          These achievements indicate strong progress in the student's learning journey and demonstrate practical application of theoretical knowledge in real-world scenarios.
                        </p>
                      </div>
                    ) : (
                      <p className="text-sm text-blue-600">No specific POs were clearly achieved this week.</p>
                    )}
                  </div>

                  {/* POs Not Met */}
                  <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h5 className="text-lg font-semibold text-red-800 mb-3">Program Outcomes Not Met</h5>
                    {posNotHit && posNotHit.length > 0 ? (
                      <div className="text-sm text-red-700 leading-relaxed">
                        <p className="mb-2">After reviewing the student activities and reports, the following program outcomes require additional attention and development:</p>
                        <ul className="list-disc list-inside space-y-1 text-red-800">
                          {posNotHit.map((h, i) => (
                            <li key={`not-hit-${i}`}>
                              <strong>{h.po || h}</strong> — {h.reason ? h.reason.toLowerCase() : 'No evidence found in activities and learnings'}
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
              )}
              
              {/* PO Analysis Graph */}
              {!poAnalysisLoading && !poError && (
                <div className="mt-6 bg-white border rounded-lg shadow-sm">
                  <div className="px-4 py-3 border-b bg-gray-50 rounded-t-lg">
                    <h5 className="text-lg font-semibold text-gray-800">Program Outcome Analysis Graph</h5>
                  </div>
                  <div className="p-4">
                    {(() => {
                      const chartMax = 100;
                      const chartHeight = 300;
                      const yStep = 10;
                      const steps = chartMax / yStep;
                      const stepPx = chartHeight / steps;
                      const yTicks = Array.from({ length: steps + 1 }, (_, k) => k * yStep);
                      const itemWidth = 40;
                      const itemGap = 8;
                      
                      return (
                        <div className="w-full overflow-x-auto">
                          <div className="flex relative" style={{ minHeight: chartHeight + 60 }}>
                            {/* Y Axis labels */}
                            <div className="relative pr-2 text-gray-600 text-sm" style={{ height: chartHeight, width: 40 }}>
                              {yTicks.map((t, idx) => {
                                const y = chartHeight - (t / chartMax) * chartHeight;
                                return (
                                  <div
                                    key={idx}
                                    className="absolute right-0"
                                    style={{ top: `${y}px`, transform: 'translateY(-50%)' }}
                                  >
                                    {t}%
                                  </div>
                                );
                              })}
                            </div>
                            
                            {/* Chart Area */}
                            <div className="flex-1 relative">
                              {/* Grid Background */}
                              <div 
                                className="absolute w-full h-full"
                                style={{
                                  backgroundImage: 'linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px), linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px)',
                                  backgroundSize: `${itemWidth + itemGap}px ${stepPx}px, ${itemWidth + itemGap}px ${stepPx}px`,
                                  backgroundColor: 'rgba(59, 130, 246, 0.05)',
                                  height: chartHeight
                                }}
                              ></div>
                              
                              {/* Bars */}
                              <div className="flex items-end h-full px-3" style={{ height: chartHeight }}>
                                {poScores.map((v, i) => {
                                  const height = Math.max(4, Math.round((v / chartMax) * chartHeight));
                                  const isAchieved = v > 0;
                                  const barColor = isAchieved ? 'bg-blue-600' : 'bg-red-500';
                                  
                                  const wordContrib = wordBasedContributions[i] || 0;
                                  const contextContrib = contextBasedContributions[i] || 0;
                                  
                                  // Calculate nested bar height: word contribution as percentage of total score
                                  // The red bar should show the word-based contribution (40% weight) inside the blue bar
                                  const nestedHeight = v > 0 && wordContrib > 0 
                                    ? Math.max(2, Math.round((wordContrib / chartMax) * chartHeight))
                                    : 0;
                                  
                                  return (
                                    <div 
                                      key={`${i}-${v}`} 
                                      className="flex flex-col items-center relative" 
                                      style={{ width: itemWidth, marginRight: i < poScores.length - 1 ? itemGap : 0 }}
                                      title={`PO${i + 1}: ${v}% (Word: ${wordContrib}%, Context: ${contextContrib}%) - ${isAchieved ? 'Achieved' : 'Not Met'}`}
                                    >
                                      <div 
                                        className={`w-full ${barColor} border border-gray-800 rounded-t relative`}
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
                                        {/* Nested contribution bar - Red outline showing word-based contribution (40% weight) */}
                                        {v > 0 && wordContrib > 0 && nestedHeight > 0 && (
                                          <div
                                            className="absolute bottom-0 left-0 w-full"
                                            style={{
                                              height: `${Math.min(100, Math.max(5, (nestedHeight / height) * 100))}%`,
                                              border: '2px solid #dc2626',
                                              borderBottom: 'none',
                                              backgroundColor: 'rgba(220, 38, 38, 0.25)',
                                              boxSizing: 'border-box',
                                              borderRadius: '2px 2px 0 0',
                                              zIndex: 5,
                                              minHeight: '4px'
                                            }}
                                            title={`Word-based contribution: ${wordContrib}% (40% weight) of total ${v}%`}
                                          />
                                        )}
                                        
                                        {/* Percentage label */}
                                        {v > 0 && (
                                          <div className="absolute top-0 left-1/2 transform -translate-x-1/2 text-white text-xs font-bold" style={{ zIndex: 10 }}>
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
                          
                          {/* X-axis labels */}
                          <div className="mt-3 flex">
                            <div style={{ width: 40 }}></div>
                            <div className="flex-1 px-3">
                              <div className="flex">
                                {poScores.map((v, i) => (
                                  <div key={`lbl-${i}`} className="text-center" style={{ width: itemWidth, marginRight: i < poScores.length - 1 ? itemGap : 0 }}>
                                    <div className="text-sm font-bold text-gray-800">PO{i + 1}</div>
                                    <div className={`inline-block px-2 py-1 rounded text-xs ${v > 0 ? 'bg-blue-600 text-white' : 'bg-red-500 text-white'}`}>
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
              )}
            </div>
          </div>
        )}

        {/* Recommendations for Improvement */}
        {selectedStudentId && recommendations.length > 0 && (
          <div className="mt-6 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
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

        {selectedStudentId ? (
          <DynamicDataGrid
            rows={rows}
            setRows={handleSetRows}
            columns={columns}
            checkboxSelection={false}
            requestedBy={authorizeRole}
            url={gridUrl}
          />
        ) : (
          <div className="text-gray-500 border rounded p-4 bg-white">Select a student to load weekly reports.</div>
        )}

        {/* Submission Progress Table */}
        {selectedStudentId && (
          <div className="mt-6 bg-white border rounded p-4">
            <h4 className="text-md font-semibold text-gray-800 mb-3">Weekly Submission Progress</h4>
            {(() => {
              // Build a 5-row view for the currently selected week only
              const weekNum = Number(selectedWeek) || 1;
              const entriesForWeek = allEntries.filter((e) => Number(e.week_number) === weekNum);
              const sorted = entriesForWeek.sort((a, b) => String(a.created_at || a.start_date).localeCompare(String(b.created_at || b.start_date)));
              const getDate = (idx) => (sorted[idx]?.created_at || sorted[idx]?.start_date || '');

              const rowsView = Array.from({ length: 5 }, (_, i) => ({
                label: `Week ${weekNum} — Day ${i + 1}`,
                date: sorted[i] ? getDate(i) : '',
                status: sorted[i] ? 'Submitted' : 'Missing',
              }));

              return (
                <table className="w-full border border-gray-300 text-sm">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="border px-4 py-2 text-left">Week / Day</th>
                      <th className="border px-4 py-2 text-center">Date</th>
                      <th className="border px-4 py-2 text-center">Status</th>
                      <th className="border px-4 py-2 text-center">Request</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rowsView.map((r, idx) => (
                      <tr key={idx}>
                        <td className="border px-4 py-3">{r.label}</td>
                        <td className="border px-4 py-3 text-center">{r.date}</td>
                        <td className="border px-4 py-3 text-center">
                          <span className={`px-2 py-1 rounded text-xs ${r.status === 'Submitted' ? 'text-green-700' : 'text-red-600'}`}>
                            {r.status}
                          </span>
                        </td>
                        <td className="border px-4 py-3 text-center">
                          {r.status !== 'Submitted' ? (
                            <button
                              onClick={async () => {
                                try {
                                  await axiosClient.get('/sanctum/csrf-cookie', { withCredentials: true });
                                  await axiosClient.post('/api/v1/weekly-entry-requests', {
                                    student_id: selectedStudentId,
                                    week_number: weekNum,
                                  });
                                  alert('Request sent to the student successfully.');
                                } catch (e) {
                                  alert('Failed to send request.');
                                }
                              }}
                              className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                              Request
                            </button>
                          ) : (
                            <span className="text-gray-400">—</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              );
            })()}
          </div>
        )}
      </div>
    </Page>
  );
};

export default ViewReportsPage;
