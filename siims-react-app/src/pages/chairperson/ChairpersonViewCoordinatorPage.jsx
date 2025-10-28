import React, { useState } from "react";
import { useLoaderData } from "react-router-dom";
import Page from "../../components/common/Page";
import Section from "../../components/common/Section";
import Heading from "../../components/common/Heading";
import Table from "../../components/tables/Table";
import Text from "../../components/common/Text";
import EmptyState from "../../components/common/EmptyState";

const ChairpersonViewCoordinatorPage = () => {
  // Fetch coordinators data
  const { coordinators } = useLoaderData();
  const [analyticsLoading, setAnalyticsLoading] = useState(false);
  const [coordinatorAnalytics, setCoordinatorAnalytics] = useState([]);
  const [aiInsight, setAiInsight] = useState("");
  const [aiLoading, setAiLoading] = useState(false);

  const loadAnalytics = async () => {
    try {
      setAnalyticsLoading(true);
      setAiInsight("");
      const apiBase = import.meta.env.VITE_API_BASE_URL;
      const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
      };

      // Pull students once via chairperson endpoint
      let students = [];
      try {
        const r = await fetch(`${apiBase}/api/v1/chairperson/students`, { headers, credentials: "include" });
        const p = await r.json().catch(() => ([]));
        students = Array.isArray(p?.data) ? p.data : (Array.isArray(p) ? p : []);
      } catch (_) {}

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
      const stripHtml = (t) => String(t || "")
        .replace(/<\s*\/? .*?>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/\s+/g, ' ')
        .trim();

      const coordItems = Array.isArray(coordinators) ? coordinators.slice(0, 20) : [];
      const out = [];
      for (const c of coordItems) {
        const id = String(c.id ?? c.user_id ?? c.coordinator_id ?? "");
        const label = `${id} - ${[c.first_name || c.firstName || '', c.last_name || c.lastName || ''].filter(Boolean).join(' ') || c.name || 'Coordinator'}`;
        const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"]; 
        let filtered = students.filter((s) => {
          for (const key of coordinatorKeyNames) {
            if (s && Object.prototype.hasOwnProperty.call(s, key)) {
              if (String(s[key] ?? "") === String(id)) return true;
            }
          }
          const cc = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
          const cid2 = cc ? (cc.id ?? cc.coordinator_id) : undefined;
          return String(cid2 ?? "") === String(id);
        }).slice(0, 6);

        const reqs = filtered.map((s) => {
          const sid = s.id ?? s.student_id ?? s.user_id ?? s.application_id;
          return fetch(`${apiBase}/api/v1/weekly-entries/student/${sid}`, { headers, credentials: "include" })
            .then((r) => r.json()).catch(() => []);
        });
        const weeklyPayloads = await Promise.all(reqs);
        const normalizeWeekly = (p) => Array.isArray(p?.data) ? p.data : (Array.isArray(p?.weekly_entries) ? p.weekly_entries : (Array.isArray(p) ? p : []));
        const all = weeklyPayloads.flatMap((p) => normalizeWeekly(p));
        const text = all.map(r => `${stripHtml(r.tasks || r.task || r.activities || "")} ${stripHtml(r.learnings || r.learning || "")}`).join(' ');
        const lower = text.toLowerCase();
        const counts = keywordSets.map((set) => set.some((kw) => lower.includes(kw)) ? 1 : 0);
        const coverage = counts.reduce((a,b)=>a+b,0);
        const poCoveragePercent = Math.round((coverage / keywordSets.length) * 100);
        out.push({ id, label, poCoveragePercent, entriesCount: all.length, studentsCount: filtered.length });
      }
      out.sort((a,b)=> b.poCoveragePercent - a.poCoveragePercent || b.entriesCount - a.entriesCount);
      setCoordinatorAnalytics(out);
    } catch (_) {
      setCoordinatorAnalytics([]);
    } finally {
      setAnalyticsLoading(false);
    }
  };

  const generateAIInsights = async () => {
    try {
      setAiLoading(true);
      const data = coordinatorAnalytics.map((r) => ({ id: r.id, label: r.label, poCoveragePercent: r.poCoveragePercent, entriesCount: r.entriesCount, studentsCount: r.studentsCount }));
      try {
        const resp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/summary/openai-summarize`, {
          method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${JSON.parse(localStorage.getItem('ACCESS_TOKEN'))}` }, credentials: 'include',
          body: JSON.stringify({ data: { coordinators: data }, type: 'chair_overview' })
        });
        if (resp.ok) { const json = await resp.json(); if (json?.summary) { setAiInsight(String(json.summary)); return; } }
      } catch (_) {}
      if (data.length) {
        const top = [...data].sort((a,b)=> b.poCoveragePercent - a.poCoveragePercent)[0];
        const low = [...data].sort((a,b)=> a.poCoveragePercent - b.poCoveragePercent)[0];
        setAiInsight(`Top contributor: ${top.label} (${top.poCoveragePercent}% coverage, ${top.entriesCount} entries). ` +
          `Priority support: ${low.label} (${low.poCoveragePercent}%). Encourage activities to cover missing POs.`);
      } else { setAiInsight('No enough data to generate insights.'); }
    } finally { setAiLoading(false); }
  };

  // console.log(coordinators);

  return (
    <Page>
      <Section>
        <Heading level={3} text={"View Coordinators"} />
        <Text className="text-sm text-blue-950">
          This is where you view the coordinators.
        </Text>
        <hr className="my-3" />

        {/* Table */}
        {coordinators.length > 0 ? (
          <>
            <Table data={coordinators} />
            {/* Analytics */}
            <div className="mt-8 bg-white border rounded-lg p-4">
              <div className="flex items-center justify-between mb-3">
                <h4 className="text-lg font-semibold text-gray-800">Program Outcomes Coverage by Coordinator</h4>
                <div className="flex gap-2">
                  <button onClick={loadAnalytics} className="px-3 py-2 bg-slate-700 text-white rounded hover:bg-slate-800">{analyticsLoading ? 'Loading…' : 'Load Analytics'}</button>
                  <button onClick={generateAIInsights} disabled={coordinatorAnalytics.length === 0} className="px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50">{aiLoading ? 'Analyzing…' : 'Generate AI Insight'}</button>
                </div>
              </div>
              {coordinatorAnalytics.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="bg-gray-50 text-gray-700">
                        <th className="text-left px-3 py-2">Coordinator</th>
                        <th className="text-left px-3 py-2">PO Coverage</th>
                        <th className="text-left px-3 py-2">Entries</th>
                        <th className="text-left px-3 py-2">Students (sample)</th>
                      </tr>
                    </thead>
                    <tbody>
                      {coordinatorAnalytics.map((r) => (
                        <tr key={r.id} className="border-t">
                          <td className="px-3 py-2 whitespace-nowrap">{r.label}</td>
                          <td className="px-3 py-2">
                            <div className="h-3 bg-gray-200 rounded relative" style={{ minWidth: 200 }}>
                              <div className="h-3 bg-green-600 rounded" style={{ width: `${r.poCoveragePercent}%` }}></div>
                              <div className="absolute inset-0 flex items-center justify-center text-[11px] text-white">{r.poCoveragePercent}%</div>
                            </div>
                          </td>
                          <td className="px-3 py-2">{r.entriesCount}</td>
                          <td className="px-3 py-2">{r.studentsCount}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-gray-500 text-sm">Click "Load Analytics" to compute coverage scores for coordinators.</div>
              )}
              {aiInsight && (
                <div className="mt-4 p-3 bg-purple-50 border border-purple-200 rounded text-purple-900 text-sm">
                  <strong>AI Insight: </strong>{aiInsight}
                </div>
              )}
            </div>
          </>
        ) : (
          <EmptyState
            title="No coordinators available at the moment"
            message="Once activities are recorded, coordinators will appear here."
          />
        )}
      </Section>
    </Page>
  );
};

export default ChairpersonViewCoordinatorPage;
