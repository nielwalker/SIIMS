import React, { useEffect, useMemo, useState, useRef } from "react";
import { useLoaderData } from "react-router-dom"; // Hook to load route data
import axiosClient from "../../api/axiosClient";
import { FaUserGraduate, FaBuilding, FaUsers, FaBook } from "react-icons/fa"; // Import icons

// Import Components
import Heading from "../../components/common/Heading";
import Page from "../../components/common/Page";
import ChairpersonSummary from "../../components/chairperson/ChairpersonSummary";

export default function ChairpersonDashboardPage() {
  // Fetch Data (from loader as fallback)
  const loaderData = useLoaderData() || {}; // { userRoles, dashboard }

  // Local state so the page can fetch totals directly (avoids loader issues)
  const [dashboard, setDashboard] = useState(loaderData.dashboard || null);
  const [coordinators, setCoordinators] = useState([]);
  const [selectedCoordinatorId, setSelectedCoordinatorId] = useState("");
  const [selectedWeek, setSelectedWeek] = useState(1);
  const [availableWeeks, setAvailableWeeks] = useState([]);
  const [refreshTrigger, setRefreshTrigger] = useState(0);
  const [exportSnapshot, setExportSnapshot] = useState(null);
  const summaryRef = useRef(null);

  const handleDownloadPDF = () => {
    try {
      if (!summaryRef.current) return;
      const content = summaryRef.current.innerHTML;
      const coord = exportSnapshot?.coordinatorId || selectedCoordinatorId || '';
      const wk = exportSnapshot?.week ?? selectedWeek;
      const when = new Date().toLocaleString();
      const title = `Chair_Summary_${coord}_week_${wk || 'overall'}`;
      const html = `<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>${title}</title>
    <style>
      @page { size: A4; margin: 16mm; }
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #111827; }
      h1,h2,h3,h4,h5 { margin: 0 0 8px; }
      .header { margin-bottom: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
      .meta { font-size: 12px; color: #374151; }
      .section { margin-top: 12px; }
      .card, .bg-white { background: #fff !important; }
      .border { border: 1px solid #e5e7eb !important; }
      .rounded, .rounded-lg { border-radius: 8px !important; }
      .p-4, .px-4, .py-4 { padding: 16px !important; }
      .mb-3 { margin-bottom: 12px !important; }
      table { width: 100%; border-collapse: collapse; font-size: 12px; }
      th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
      .badge { border: 1px solid #e5e7eb; border-radius: 4px; padding: 2px 6px; }
      .text-muted { color: #6b7280; }
      /* Only show .print-only on print, hide .screen-only */
      .print-only { display: none !important; }
      .screen-only { display: block; }
      @media print {
        .print-only { display: block !important; }
        .screen-only { display: none !important; }
      }
    </style>
    <script>
      window.addEventListener('load', function() {
        setTimeout(function(){ try { window.print(); } catch(e){} }, 200);
      });
      window.onafterprint = function(){ try { window.close(); } catch(e){} };
    </script>
  </head>
  <body>
    <div class="header">
      <h2>Chairperson Summary</h2>
      <div class="meta">Coordinator ID: ${coord} • Week: ${wk || 'overall'} • Exported: ${when}</div>
    </div>
    <div class="section">
      ${content}
    </div>
  </body>
</html>`;

      const blob = new Blob([html], { type: 'text/html' });
      const url = URL.createObjectURL(blob);
      const w = window.open(url, '_blank', 'noopener,noreferrer');
      if (!w) {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.srcdoc = html;
        iframe.onload = () => {
          try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
          } catch (_) {}
          setTimeout(() => { try { document.body.removeChild(iframe); } catch (_) {} }, 1000);
        };
        document.body.appendChild(iframe);
      } else {
        // Revoke URL after some time
        setTimeout(() => { try { URL.revokeObjectURL(url); } catch(_){} }, 10000);
      }
    } catch (_) { /* no-op */ }
  };

  useEffect(() => {
    let didCancel = false;
    (async () => {
      try {
        // Use native fetch to avoid global axios toasts when backend throws unrelated errors
        const resp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/dashboards?requestedBy=chairperson`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
          },
          credentials: "include",
        });
        if (!resp.ok) throw new Error("dashboards error");
        const data = await resp.json();
        if (!didCancel) setDashboard(data);
      } catch (e) {
        if (!didCancel) setDashboard(null); // UI will show zeros
      }
    })();
    return () => {
      didCancel = true;
    };
  }, []);

  // Load coordinators for dropdown
  useEffect(() => {
    let didCancel = false;
    (async () => {
      try {
        const normalize = (p) => {
          if (!p) return [];
          if (Array.isArray(p?.data?.data)) return p.data.data;
          if (Array.isArray(p?.data)) return p.data;
          if (Array.isArray(p)) return p;
          return [];
        };

        // Try multiple known endpoints until one returns data
        const attempts = [
          () => axiosClient.get("/api/v1/users/v2/coordinators", { params: { requestedBy: "chairperson", page: 1, perPage: 500 } }),
          () => axiosClient.get("/api/v1/users/coordinators"),
          () => axiosClient.get("/api/v1/users/coordinators/all"),
          () => axiosClient.get("/api/v1/coordinators"),
        ];

        let rows = [];
        for (const attempt of attempts) {
          try {
            const resp = await attempt();
            rows = normalize(resp);
            if (rows.length) break;
          } catch {
            // try next
          }
        }

        // Normalize/dedupe and build labels
        const normalized = [];
        const seen = new Set();
        for (const c of rows) {
          const rawId = c?.id ?? c?.user_id ?? c?.coordinator_id;
          const id = rawId != null ? String(rawId) : undefined;
          if (!id || seen.has(id)) continue;
          seen.add(id);
          const first = c.first_name || c.firstName || c.user_first_name || '';
          const last = c.last_name || c.lastName || c.user_last_name || '';
          const fallback = c.user_name || c.userName || c.name || c.fullName || 'Coordinator';
          const label = `${id} - ${[first, last].filter(Boolean).join(' ') || fallback}`;
          normalized.push({ id, label });
        }

          if (!didCancel) {
          setCoordinators(normalized);
          // Do not auto-select; require explicit choice
          setSelectedCoordinatorId("");
        }
      } catch (err) {
        if (!didCancel) {
          setCoordinators([]);
          setSelectedCoordinatorId("");
        }
      }
    })();
    return () => {
      didCancel = true;
    };
  }, []);

  // Dynamically compute weeks that have data for the selected coordinator
  useEffect(() => {
    let cancelled = false;
    async function loadWeeks() {
      try {
        if (!selectedCoordinatorId) {
          if (!cancelled) {
            setAvailableWeeks([]);
            setSelectedWeek("");
          }
          return;
        }

        const apiBase = import.meta.env.VITE_API_BASE_URL;
        const headers = {
          Accept: "application/json",
          Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
        };

        // 1) Get all students in chairperson program and filter by coordinatorId
        const r = await fetch(`${apiBase}/api/v1/chairperson/students`, { headers, credentials: "include" });
        const payload = await r.json().catch(() => ([]));
        const students = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"];
        let filtered = students.filter((s) => {
          for (const key of coordinatorKeyNames) {
            if (s && Object.prototype.hasOwnProperty.call(s, key)) {
              return String(s[key] ?? "") === String(selectedCoordinatorId ?? "");
            }
          }
          const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
          const cid = c ? (c.id ?? c.coordinator_id) : undefined;
          return String(cid ?? "") === String(selectedCoordinatorId ?? "");
        });

        // Fallback: some chairperson student payloads only include coordinator name string
        if (!filtered || filtered.length === 0) {
          const r2 = await fetch(`${apiBase}/api/v1/users/students/get-all-students`, { headers, credentials: "include" });
          const p2 = await r2.json().catch(() => ({}));
          const list = Array.isArray(p2?.data) ? p2.data : (Array.isArray(p2?.initial_students) ? p2.initial_students : (Array.isArray(p2) ? p2 : []));
          filtered = list.filter((s) => {
            for (const key of coordinatorKeyNames) {
              if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                return String(s[key] ?? "") === String(selectedCoordinatorId ?? "");
              }
            }
            const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
            const cid = c ? (c.id ?? c.coordinator_id) : undefined;
            return String(cid ?? "") === String(selectedCoordinatorId ?? "");
          });
        }

        // 2) Fetch weekly entries per filtered student in parallel
        const ids = filtered.map((s) => s.id ?? s.student_id ?? s.user_id ?? s.application_id).filter(Boolean);
        const reqs = ids.map((id) => fetch(`${apiBase}/api/v1/weekly-entries/student/${id}`, { headers, credentials: "include" })
          .then((res) => res.json()).catch(() => ([])));
        const results = await Promise.all(reqs);

        const normalizeWeekly = (p) => {
          if (!p) return [];
          if (Array.isArray(p?.data)) return p.data;
          if (Array.isArray(p?.weekly_entries)) return p.weekly_entries;
          if (Array.isArray(p)) return p;
          return [];
        };
        const all = results.flatMap((p) => normalizeWeekly(p));
        const weekNums = new Set();
        for (const row of all) {
          const wn = Number(row?.week_number ?? row?.weekNumber ?? row?.week);
          if (!Number.isNaN(wn) && wn > 0) weekNums.add(wn);
        }
        const sorted = Array.from(weekNums).sort((a, b) => a - b);
        if (!cancelled) {
          setAvailableWeeks(sorted);
          if (!sorted.includes(Number(selectedWeek))) {
            setSelectedWeek(sorted.length ? sorted[0] : "");
          }
        }
      } catch {
        if (!cancelled) {
          setAvailableWeeks([]);
          setSelectedWeek("");
        }
      }
    }
    loadWeeks();
    return () => { cancelled = true; };
  }, [selectedCoordinatorId]);

  // Coordinators list removed from dashboard

  // Destructure data safely (using default values)
  const dash = dashboard || loaderData?.dashboard || {};
  const totalStudents = (dash.totalStudents ?? dash.total_students) ?? 0;
  const totalCompanies = (dash.totalCompanies ?? dash.total_companies) ?? 0;
  const totalCoordinators = (dash.totalCoordinators ?? dash.total_coordinators) ?? 0;
  const totalPrograms = (dash.totalPrograms ?? dash.total_programs) ?? 0;

  const stats = [
    {
      label: "Total Interns",
      value: totalStudents,
      color: "blue",
      icon: <FaUserGraduate className="text-blue-500" size={32} />,
    },
    {
      label: "Total Companies",
      value: totalCompanies,
      color: "violet",
      icon: <FaBuilding className="text-orange-500" size={32} />,
    },
    {
      label: "Total Coordinators",
      value: totalCoordinators,
      color: "red",
      icon: <FaUsers className="text-red-500" size={32} />,
    },
    {
      label: "Total Programs",
      value: totalPrograms,
      color: "teal",
      icon: <FaBook className="" size={32} />,
    },
  ];


  return (
    <>
      <Page>
        <div className="bg-blue-600 w-100 rounded-md px-2 py-7">
          <Heading
            level={3}
            text={"Welcome, Chairperson! 👋"}
            textColor="text-white"
          />
        </div>

        <section>
          <div className="p-6 bg-gray-100">
            {/* Overview Section */}
            <div className="mb-4">
              <h3 className="text-xl font-semibold text-gray-800">Overview</h3>
            </div>

            {/* Cards Grid */}
            <div className="grid grid-cols-2 grid-rows-2 p-4 h-[400px] gap-4">
              {stats.map((stat, index) => (
                <div
                  key={index}
                  className={`flex flex-col items-center justify-center bg-white shadow-md rounded-lg border-t-4 border-${stat.color}-500 p-4`}
                >
                  {/* Icon */}
                  <div className="mb-2">{stat.icon}</div>
                  {/* Label */}
                  <span className="text-gray-600 text-sm font-medium">
                    {stat.label}
                  </span>
                  {/* Value */}
                  <span className="text-6xl font-bold text-gray-800">
                    {stat.value}
                  </span>
                </div>
              ))}
            </div>

            {/* Controls for Section and Week */}
            <div className="flex flex-wrap items-center gap-3 p-4 bg-gray-50 border rounded">
              <div className="flex items-center gap-2">
                <label className="text-sm font-semibold text-gray-700">Coordinator:</label>
                <select
                  value={selectedCoordinatorId}
                  onChange={(e) => setSelectedCoordinatorId(e.target.value)}
                  className="px-3 py-2 border rounded text-gray-900 bg-white"
                >
                  <option value="">Select Coordinator</option>
                  {coordinators.map((c) => (
                    <option key={c.id} value={c.id}>{c.label}</option>
                  ))}
                  {coordinators.length === 0 && (
                    <option value="">-- No coordinators found --</option>
                  )}
                </select>
              </div>
              <div className="flex items-center gap-2">
                <label className="text-sm font-semibold text-gray-700">Week:</label>
                <select
                  value={selectedWeek}
                  onChange={(e) => {
                    const val = e.target.value === "" ? "" : (e.target.value === "overall" ? "overall" : Number(e.target.value));
                    setSelectedWeek(val);
                    setRefreshTrigger(prev => prev + 1);
                  }}
                  className="px-3 py-2 border rounded text-gray-900 bg-white"
                >
                  <option value="">Select Week</option>
                  <option value="overall">Overall (All Weeks)</option>
                  {availableWeeks.length === 0 && (
                    <option value="" disabled>No weeks available</option>
                  )}
                  {availableWeeks.map((w) => (
                    <option key={w} value={w}>Week {w}</option>
                  ))}
                </select>
                <button
                  onClick={() => setRefreshTrigger(prev => prev + 1)}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Refresh
                </button>
              
              <button
                onClick={handleDownloadPDF}
                disabled={!selectedCoordinatorId || (!selectedWeek && selectedWeek !== "overall")}
                className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                title={!selectedCoordinatorId ? "Select a coordinator first" : (!selectedWeek && selectedWeek !== "overall" ? "Select a week or Overall" : "")}
              >
                Download PDF
              </button>
              </div>
            </div>

            {/* Summary Block - only when both coordinator and week are selected */}
            {!!selectedCoordinatorId && (selectedWeek === "overall" || !!selectedWeek) && (
              <div ref={summaryRef}>
                <ChairpersonSummary 
                  coordinatorId={selectedCoordinatorId} 
                  week={selectedWeek} 
                  refreshTrigger={refreshTrigger}
                  onExportReady={setExportSnapshot}
                />
              </div>
            )}

          

            {/* Coordinators section removed per requirement */}
          </div>
        </section>
      </Page>
    </>
  );
}
