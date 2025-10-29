import React, { useEffect, useState } from "react";
import WeeklyReportPresenter from "./WeeklyReportPresenter";
import useForm from "./hooks/useForm";
import { addWar, deleteWarByID, getAllWar, updateWar } from "./Api";
import { getRequest } from "../../api/apiHelpers";
import { useSearchParams } from "react-router-dom";
import axiosClient from "../../api/axiosClient";

const WeeklyReportContainer = ({ authorizeRole }) => {
  /**
   *
   *
   * LOADING STATE
   *
   *
   *
   */
  const [loading, setLoading] = useState(false);

  /**
   *
   *
   * MODAL STATE
   *
   *
   */
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isTaskViewOpen, setIsTaskViewOpen] = useState(false);
  const [isLearningViewOpen, setIsLearningViewOpen] = useState(false);

  /**
   *
   *
   * SELECT STATE
   *
   *
   */
  const [selectedTask, setSelectedTask] = useState("");
  const [selectedLearning, setSelectedLearning] = useState("");

  /**
   *
   *
   * USE FORM HOOK
   *
   *
   */
  const { formData, handleInputChange, resetForm, setFormValues } = useForm({
    week_number: "",
    start_date: "",
    end_date: "",
    tasks: "",
    learnings: "",
    no_of_hours: "",
  });

  // Request week (from coordinator request)
  const [searchParams] = useSearchParams();
  const [lockedWeek, setLockedWeek] = useState(null);

  /**
   *
   *
   * ERROR STATE
   *
   */
  const [errors, setErrors] = useState({});

  /**
   *
   *
   * ROW STATE
   *
   *
   */
  const [rows, setRows] = useState([]);

  // Header info for PDF (student, company, coordinator, chairperson)
  const [headerInfo, setHeaderInfo] = useState({
    studentName: "",
    companyName: "",
    coordinatorName: "",
    chairpersonName: "",
    unitOfficeDept: "",
    traineeName: "",
    supervisorName: "",
    periodStart: "",
    periodEnd: "",
  });

  /**
   *
   *
   * USE EFFECTS
   *
   *
   */
  useEffect(() => {
    getWeeklyRecords();
    fetchIdentity();
    const rq = searchParams.get("request_week");
    if (rq) {
      setLockedWeek(Number(rq));
      setFormValues((prev) => ({ ...prev, week_number: String(rq) }));
      setIsAddOpen(true);
    }
  }, []);

  /**
   *
   *
   * API FUNCTIONS
   *
   *
   */

  const getWeeklyRecords = async () => {
    await getAllWar({
      setLoading: setLoading,
      setRows: setRows,
      authorizeRole: authorizeRole,
    });
  };

  const fetchIdentity = async () => {
    try {
      const profile = await getRequest({ url: "/api/v1/profiles/student" });
      const home = await getRequest({ url: "/api/v1/homes/student" });
      const user = profile?.user || {};
      const student = profile?.student || {};
      const studentUser = student?.user || user;
      const fullName = (u = {}) => [u?.first_name, u?.middle_name, u?.last_name].filter(Boolean).join(" ");
      const studentName = fullName(studentUser) || profile?.name || "";

      const company = student?.company || profile?.company || student?.company || {};
      const companyName = company?.name || "";
      // New: pull normalized address from profile.latest_application.office.address if present.
      // If not present, use company.address (populated from first office on backend) as fallback.
      let unitOfficeDept = (student?.latest_application?.office?.address)
        || company?.address || company?.location || profile?.company_address || profile?.company_location || "";

      const coordinator = student?.coordinator || profile?.coordinator || {};
      const coordUser = coordinator?.user || {};
      const coordinatorName = [coordUser?.first_name, coordUser?.last_name].filter(Boolean).join(" ") || "";

      const program = student?.program || profile?.program || {};
      const chair = program?.chairperson || {};
      const chairUser = chair?.user || {};
      const chairpersonName = [chairUser?.first_name, chairUser?.last_name].filter(Boolean).join(" ") || "";
      // Try to get supervisor from latest application
      let supervisorName = "";
      // Prefer company user as supervisor (company contact acts as supervisor)
      let companyUser = company?.user || profile?.company_user || student?.company?.user || null;
      if (companyUser && !supervisorName) {
        supervisorName = fullName(companyUser);
      }

      // Try to use latest application from /homes/student (most reliable for address and office supervisor)
      try {
        const app = home?.latest_application || home?.latestApplication || home?.student?.latestApplication || null;
        if (app) {
          const office = app?.work_post?.office || {};
          const supUser = office?.supervisor?.user || office?.supervisor || {};
          // Use office supervisor only if company user is not available
          supervisorName = supervisorName || fullName(supUser);
          const parts = [office?.street, office?.barangay, office?.city_municipality, office?.province, office?.postal_code].filter(Boolean);
          unitOfficeDept = unitOfficeDept || (parts.length ? parts.join(', ') : '') || office?.address || office?.location || "";
        }
      } catch (_) {}

      // Additional fallback: query student applications endpoint
      try {
        const apps = await getRequest({ url: "/api/v1/student/applications" });
        const list = Array.isArray(apps) ? apps : (Array.isArray(apps?.data) ? apps.data : []);
        if (list && list.length > 0) {
          const latest = list[0];
          const office = latest?.work_post?.office || {};
          const supUser = office?.supervisor?.user || office?.supervisor || {};
          supervisorName = supervisorName || fullName(supUser);
          const parts = [office?.street, office?.barangay, office?.city_municipality, office?.province, office?.postal_code].filter(Boolean);
          unitOfficeDept = unitOfficeDept || (parts.length ? parts.join(', ') : '') || office?.address || office?.location || "";
        }
      } catch (_) {}

      setHeaderInfo((prev) => ({
        ...prev,
        studentName,
        companyName,
        coordinatorName,
        chairpersonName,
        unitOfficeDept,
        traineeName: studentName,
        supervisorName,
      }));
    } catch (_) {
      setHeaderInfo({ studentName: "", companyName: "", coordinatorName: "", chairpersonName: "", unitOfficeDept: "", traineeName: "", supervisorName: "", periodStart: "", periodEnd: "" });
    }
  };

  // Derive period start/end whenever rows change
  useEffect(() => {
    if (!rows || rows.length === 0) {
      setHeaderInfo((prev) => ({ ...prev, periodStart: "", periodEnd: "" }));
      return;
    }
    const parse = (s) => (s ? String(s).slice(0, 10) : "");
    const starts = rows.map((r) => parse(r.start_date)).filter(Boolean).sort();
    const ends = rows.map((r) => parse(r.end_date)).filter(Boolean).sort();
    const periodStart = starts[0] || "";
    const periodEnd = ends[ends.length - 1] || periodStart;
    setHeaderInfo((prev) => ({ ...prev, periodStart, periodEnd }));
  }, [rows]);

  const addWeeklyTimeRecord = async (e) => {
    e.preventDefault();

    // console.log(formData);
    await addWar({
      authorizeRole: authorizeRole,
      setLoading: setLoading,
      payload: formData,
      setErrors: setErrors,
      setIsOpen: setIsAddOpen,
      setRows: setRows,
    });
    // If this entry fulfills a coordinator request, mark it complete
    try {
      if (lockedWeek) {
        await axiosClient.get('/sanctum/csrf-cookie', { withCredentials: true });
        try {
          await axiosClient.put('/api/v1/student/weekly-entry-requests/complete', { week_number: Number(lockedWeek) });
        } catch (err) {
          await axiosClient.put('/api/v1/weekly-entry-requests/complete', { week_number: Number(lockedWeek) });
        }
        setLockedWeek(null);
      }
    } catch (_) {}
  };

  const updateWeeklyTimeRecord = async (e) => {
    e.preventDefault();

    await updateWar({
      authorizeRole: authorizeRole,
      setLoading: setLoading,
      setRows: setRows,
      setErrors: setErrors,
      setOpen: setIsEditOpen,
      payload: formData,
    });
  };

  const deleteWeeklyTimeRecord = async (id) => {
    await deleteWarByID({
      setLoading: setLoading,
      id: id,
      setRows: setRows,
    });
  };

  /**
   *
   *
   * Other Functions
   *
   *
   */
  const openTaskViewModal = (tasks) => {
    // console.log(tasks);

    setSelectedTask(tasks);
    setIsTaskViewOpen(true);
  };

  const openLearningViewModal = (learning) => {
    setSelectedLearning(learning);
    setIsLearningViewOpen(true);
  };

  const openEditModal = (row) => {
    // console.log(row);

    setFormValues({
      ...row,
    });

    setIsEditOpen(true);
  };

  return (
    <WeeklyReportPresenter
      loading={loading}
      rows={rows}
      header={headerInfo}
      /** Form Props */
      formData={formData}
      handleInputChange={handleInputChange}
      /** Add Props */
      isAddOpen={isAddOpen}
      setIsAddOpen={setIsAddOpen}
      addWeeklyTimeRecord={addWeeklyTimeRecord}
      /** Task View Props */
      selectedTask={selectedTask}
      openTaskViewModal={openTaskViewModal}
      isTaskViewOpen={isTaskViewOpen}
      setIsTaskViewOpen={setIsTaskViewOpen}
      /** Learning View Props */
      selectedLearning={selectedLearning}
      openLearningViewModal={openLearningViewModal}
      isLearningViewOpen={isLearningViewOpen}
      setIsLearningViewOpen={setIsLearningViewOpen}
      /** Update props */
      openEditModal={openEditModal}
      isEditOpen={isEditOpen}
      setIsEditOpen={setIsEditOpen}
      updateWeeklyTimeRecord={updateWeeklyTimeRecord}
      /** Delete props */
      deleteWeeklyTimeRecord={deleteWeeklyTimeRecord}
      /** Validation Error Props */
      validationErrors={errors}
    />
  );
};

export default WeeklyReportContainer;
