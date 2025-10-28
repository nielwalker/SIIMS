import React, { useMemo, useState } from "react";
import axiosClient from "../api/axiosClient";
import { useLocation, useNavigate } from "react-router-dom";
import { getReportsActionColumns, getReportsStaticColumns } from "../utils/columns/reportsColumns";
import Page from "../components/common/Page";
import Loader from "../components/common/Loader";
import Section from "../components/common/Section";
import Heading from "../components/common/Heading";
import Text from "../components/common/Text";
import { Select } from "@headlessui/react";
import DynamicDataGrid from "../components/tables/DynamicDataGrid";

const ViewReportsPage = ({ authorizeRole }) => {
  // Student options for coordinator
  const [students, setStudents] = useState([]);
  const [selectedStudentId, setSelectedStudentId] = useState("");
  const [selectedStudentCompany, setSelectedStudentCompany] = useState("");
  const ALL_WEEKS = useMemo(() => Array.from({ length: 13 }, (_, i) => i + 1), []);
  const [availableWeeks, setAvailableWeeks] = useState([]);
  const [selectedWeek, setSelectedWeek] = useState(1);
  const [summaryLoading, setSummaryLoading] = useState(false);
  const [studentSummary, setStudentSummary] = useState("");
  const [recommendations, setRecommendations] = useState([]);
  const [totalHours, setTotalHours] = useState(0);

  // Open location and navigation
  const location = useLocation();
  const navigate = useNavigate();

  // Loading state
  const [loading, setLoading] = useState(false);
  // Row State
  const [rows, setRows] = useState([]);
  // Data grid URL derived from selected student
  // Grid will fetch weekly entries from backend when applicationId is present
  // grid fetch disabled; we fetch and filter locally from weekly-entries/student/{id}
  const gridUrl = undefined; // prevent DynamicDataGrid from issuing a fetch

  // Function that navigates to DTR
  const navigateToDtr = ({ id }) => {
    const to = `${location.pathname}/${id}/daily-time-records`;

    navigate(to);
  };

  // Function that navigates to WAR
  const navigateToWar = (params) => {
    const to = `${location.pathname}/${params.row.id}/weekly-accomplishment-reports`;

    navigate(to, {
      state: {
        name: params.row.name,
      },
    });
  };

  // Function that navigates to Performance Evaluation
  const navigateToEvaluation = (params) => {
    const to = `${location.pathname}/${params.row.id}/performance-evaluation`;

    navigate(to);
  };

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
  React.useEffect(() => {
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
  React.useEffect(() => {
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

  // Fetch available weeks for selected student
  React.useEffect(() => {
    let cancel = false;
    if (!selectedStudentId) { 
      setAvailableWeeks([]);
      setRows([]); 
      return; 
    }
    (async () => {
      try {
        setLoading(true);
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
        if (cancel) return;
        const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
        
        // Extract unique week numbers that have data
        const weeksWithData = [...new Set(list.map((e) => {
          const wn = Number(e.week_number ?? e.weekNumber ?? e.week);
          return Number.isNaN(wn) ? null : wn;
        }).filter(Boolean))].sort((a, b) => a - b);
        
        setAvailableWeeks(weeksWithData);
        
        // If current selected week is not in available weeks, reset to first available week
        if (weeksWithData.length > 0 && !weeksWithData.includes(selectedWeek)) {
          setSelectedWeek(weeksWithData[0]);
        }
        
        // Filter entries by selected week
        const filtered = list.filter((e) => {
          const wn = Number(e.week_number ?? e.weekNumber ?? e.week);
          return Number.isNaN(wn) ? true : wn === Number(selectedWeek);
        });
        
        const truncate = (s, n = 300) => (s && s.length > n ? s.slice(0, n) + "…" : s);
        const fmtDate = (s) => {
          if (!s) return "";
          const str = String(s).replace('T', ' ');
          return str.slice(0, 16); // YYYY-MM-DD HH:mm
        };
        const normalized = filtered.map((e) => ({
          id: e.id,
          student_id: String(e.student_id ?? e.studentId ?? (selectedStudentId ?? "")),
          week_number: e.week_number ?? e.weekNumber ?? e.week,
          start_date: fmtDate(e.start_date ?? e.startDate),
          end_date: fmtDate(e.end_date ?? e.endDate),
          tasks: truncate(String(e.tasks ?? e.task ?? e.activities ?? "").replace(/<\s*\/?.*?>/g, " ").replace(/\s+/g, " ").trim()),
          learnings: truncate(String(e.learnings ?? e.learning ?? "").replace(/<\s*\/?.*?>/g, " ").replace(/\s+/g, " ").trim()),
          no_of_hours: e.no_of_hours ?? e.hours ?? e.noOfHours ?? 0,
          created_at: fmtDate(e.created_at ?? e.createdAt),
        }));
        setRows(normalized);
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

  // Fetch summary + total hours for coordinator
  React.useEffect(() => {
    const fetchSummary = async () => {
      if (!selectedStudentId) return;
      try {
        setSummaryLoading(true);
        const resp = await axiosClient.post("/api/v1/summary", {
          studentId: selectedStudentId,
          week: selectedWeek,
          useGPT: true,
          analysisType: "coordinator",
          isOverall: false,
        });
        const data = resp?.data || {};
        const cleanHtml = (txt) => String(txt || "")
          .replace(/<\s*\/? .*?>/g, " ")
          .replace(/&nbsp;/gi, " ")
          .replace(/&amp;/gi, "&")
          .replace(/&lt;/gi, "<")
          .replace(/&gt;/gi, ">")
          .replace(/\s+/g, " ")
          .trim();
        let s = cleanHtml(data?.summary || "");
        // Heuristic: if backend returns raw concatenation (no sentences), build a client-side summary
        const periodCount = (s.match(/[.!?]/g) || []).length;
        if (!s || periodCount < 2) {
          // Fallback: fetch entries and compose concise bullet-like sentences
          try {
            const r = await fetch(
              `${import.meta.env.VITE_API_BASE_URL}/api/v1/weekly-entries/student/${selectedStudentId}`,
              {
                headers: {
                  Accept: "application/json",
                  Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
                },
                credentials: "include",
              }
            );
            const p = await r.json().catch(() => []);
            const list = Array.isArray(p?.data) ? p.data : Array.isArray(p) ? p : [];
            const filterByWeek = list.filter((e) => {
              const wn = Number(e.week_number ?? e.weekNumber ?? e.week);
              return Number.isNaN(wn) ? true : wn === Number(selectedWeek);
            });
            const clean = (t) => cleanHtml(t);
            // Try OpenAI polishing using structured activities/learnings
            try {
              const activities = filterByWeek.map((e) => clean(e.tasks ?? e.task ?? e.activities)).filter(Boolean);
              const learnings = filterByWeek.map((e) => clean(e.learnings ?? e.learning)).filter(Boolean);
              // Build GET fallback payload as query param as well to avoid CSRF anomalies
              const payload = { data: { corrected_activities: activities, corrected_learnings: learnings, 'summary for this section on a week': '' }, type: 'coordinator_week' };
              const query = encodeURIComponent(JSON.stringify(payload.data));
              // Try POST first
              let aiResp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/summary/openai-summarize-coordinator`, {
                method: 'POST',
                headers: {
                  Accept: 'application/json',
                  'Content-Type': 'application/json',
                  Authorization: `Bearer ${JSON.parse(localStorage.getItem('ACCESS_TOKEN'))}`,
                  'X-Requested-With': 'XMLHttpRequest'
                },
                // send cookies if backend uses session-based auth
                credentials: 'include',
                body: JSON.stringify({ data: payload.data, type: payload.type })
              });
              if (!aiResp.ok) {
                aiResp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/summary/openai-summarize-coordinator?data=${query}&type=coordinator_week`, {
                  method: 'GET',
                  headers: { Accept: 'application/json', Authorization: `Bearer ${JSON.parse(localStorage.getItem('ACCESS_TOKEN'))}` },
                  credentials: 'include'
                });
              }
              if (aiResp.ok) {
                const aiJson = await aiResp.json();
                if (aiJson?.summary) {
                  s = cleanHtml(aiJson.summary);
                }
              }
            } catch (_) { /* ignore */ }

            if (!s || (s.match(/[.!?]/g) || []).length < 2) {
              const bullets = filterByWeek.flatMap((e) => [clean(e.tasks ?? e.task ?? e.activities), clean(e.learnings ?? e.learning)]).filter(Boolean);
              const top = bullets.slice(0, 5).map((b) => (b.endsWith('.') ? b : b + '.'));
              s = top.join(' ');
            }
          } catch (_) {
            s = s || "No data available.";
          }
        }
        setStudentSummary(s || "No data available.");
        try {
          // Lightweight PO keyword analysis to guide recommendations
          const lower = String(s || "").toLowerCase();
          const poHints = [
            { po: 'PO1', kws: ['math','algorithm','compute','analysis'], tip: 'Practice algorithmic thinking and structured analysis of tasks.' },
            { po: 'PO2', kws: ['standard','guideline','best practice','policy'], tip: 'Adopt team/project standards and document conventions used.' },
            { po: 'PO3', kws: ['troubleshoot','diagnose','root cause','problem'], tip: 'Apply root-cause analysis; log problems and resolutions.' },
            { po: 'PO4', kws: ['user','requirement','stakeholder','ux'], tip: 'Clarify user needs; validate with quick feedback sessions.' },
            { po: 'PO5', kws: ['design','implement','evaluate','test','build'], tip: 'Include evaluation criteria and testing in deliverables.' },
            { po: 'PO6', kws: ['security','safety','privacy','environment'], tip: 'Call out security/privacy considerations in your work notes.' },
            { po: 'PO7', kws: ['tool','framework','library','platform'], tip: 'Leverage appropriate tools and record why they were chosen.' },
            { po: 'PO8', kws: ['team','collaborat','leader','group'], tip: 'Plan pair work; rotate roles to build leadership and teamwork.' },
            { po: 'PO9', kws: ['plan','schedule','timeline','milestone'], tip: 'Maintain a simple weekly plan with milestones and risks.' },
            { po: 'PO10', kws: ['communicat','present','document','report'], tip: 'Improve clarity in weekly reports; add concise summaries.' },
            { po: 'PO11', kws: ['impact','society','organization','community'], tip: 'Reflect on how your work benefits users/organization.' },
            { po: 'PO12', kws: ['ethic','compliance','legal'], tip: 'Note ethical/compliance checks (e.g., data handling).' },
            { po: 'PO13', kws: ['learn','self-study','course','tutorial'], tip: 'Set a weekly learning goal and document outcomes.' },
            { po: 'PO14', kws: ['research','experiment','study'], tip: 'Run small experiments; compare approaches with brief notes.' },
            { po: 'PO15', kws: ['filipino','heritage','culture'], tip: 'Consider local context and accessibility in solutions.' },
          ];
          const missing = poHints.filter(h => !h.kws.some(k => lower.includes(k))).slice(0, 6);
          setRecommendations(missing.map(m => ({ po: m.po, tip: m.tip })));
        } catch {
          setRecommendations([]);
        }
      } catch (e) {
        setStudentSummary("No data available.");
      } finally {
        setSummaryLoading(false);
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

    fetchSummary();
    fetchTotalHours();
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

        {/* Recommendations for Improvement - below the summary; narrative format */}
        {selectedStudentId && recommendations.length > 0 && (
          <div className="bg-emerald-50 border border-emerald-200 rounded p-4 mb-4">
            <h4 className="text-md font-semibold text-emerald-900 mb-2">Recommendations for Improvement</h4>
            {(() => {
              const codes = recommendations.map(r => r.po).join(', ');
              const plan = recommendations.map(r => `For ${r.po}, ${r.tip}`).join(' ');
              return (
                <p className="text-sm text-emerald-800 leading-relaxed">
                  Given this week’s outcomes, please prioritize the following program outcomes: {codes}. {plan} Coordinate with the student to embed these goals in next week’s plan and reflections.
                </p>
              );
            })()}
          </div>
        )}

        {selectedStudentId ? (
          (() => {
            const gridProps = {
              rows: rows,
              setRows: setRows,
              columns: columns,
              checkboxSelection: false,
              requestedBy: authorizeRole,
            };
            if (gridUrl) {
              // Only provide url when defined to avoid `/api/v1undefined` requests
              gridProps.url = gridUrl;
            }
            return <DynamicDataGrid {...gridProps} />;
          })()
        ) : (
          <div className="text-gray-500 border rounded p-4 bg-white">Select a student to load weekly reports.</div>
        )}

        {/* Submission Progress Table */}
        {selectedStudentId && (
          <div className="mt-6 bg-white border rounded p-4">
            <h4 className="text-md font-semibold text-gray-800 mb-3">Weekly Submission Progress</h4>
            {(() => {
              // Build a 5-row view (Week, Date, Status) like the sample
              const submitted = rows && rows.length > 0;
              const firstDate = submitted ? (rows[0]?.created_at || rows[0]?.start_date || '') : '';
              const rowsView = Array.from({ length: 5 }, (_, i) => ({
                weekLabel: `Week ${selectedWeek}`,
                date: i === 0 && submitted ? firstDate : '',
                status: i === 0 && submitted ? 'Submitted' : 'Missing',
              }));
              return (
                <table className="w-full border border-gray-300 text-sm">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="border px-4 py-2 text-left">Week</th>
                      <th className="border px-4 py-2 text-center">Date</th>
                      <th className="border px-4 py-2 text-center">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rowsView.map((r, idx) => (
                      <tr key={idx}>
                        <td className="border px-4 py-3">{r.weekLabel}</td>
                        <td className="border px-4 py-3 text-center">{r.date}</td>
                        <td className="border px-4 py-3 text-center">
                          <span className={`px-2 py-1 rounded text-xs ${r.status === 'Submitted' ? 'text-green-700' : 'text-red-600'}`}>
                            {r.status}
                          </span>
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
