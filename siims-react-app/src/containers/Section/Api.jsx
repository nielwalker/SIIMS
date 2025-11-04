import { getRequest } from "../../api/apiHelpers";
import axiosClient from "../../api/axiosClient";
import { showSuccessAlert } from "../../utils/toastify";
import {
  ADD_SECTION_URL,
  ASSIGN_STUDENT_BY_SECTION_URL,
  GET_ALL_SECTIONS,
  GET_ALL_STUDENTS_BY_SECTION,
  GET_ALL_WEEKLY_ENTRIES_BY_STUDENT,
} from "./constants/resources";

/**
 *
 * GET
 *
 */
export const getSection = async ({
  authorizeRole,
  searchTerm,
  setLoading,
  setSelectedSection,
  setSections,
}) => {
  // Set Loading
  setLoading(true);

  try {
    const response = await getRequest({
      url: GET_ALL_SECTIONS,
      params: {
        requestedBy: authorizeRole,
        searchTerm: searchTerm,
      },
    });

    // console.log(sectionID);

    if (response) {
      // Handle different response formats
      let sectionsList = [];
      
      // Check if response is directly an array
      if (Array.isArray(response)) {
        sectionsList = response;
      } 
      // Check if response has a data property with array
      else if (response && response.data && Array.isArray(response.data)) {
        sectionsList = response.data;
      }
      
      // Filter out soft-deleted or unavailable sections (sections should already be filtered by backend)
      // Only show available sections (non-deleted, active sections)
      const availableSections = sectionsList.filter(section => section && section.id && section.name);
      
      // For coordinators: Only add "All Sections" if they have multiple sections (more than 1)
      // This allows coordinators to see all their students when they have multiple sections
      let sectionsWithAll = [];
      if (authorizeRole === 'coordinator' && availableSections.length > 1) {
        // Add "All Sections" option only if coordinator has multiple sections
        sectionsWithAll = [{ id: null, name: "All Sections" }, ...availableSections];
      } else {
        // For single section or non-coordinator roles, don't add "All Sections"
        sectionsWithAll = availableSections;
      }
      
      setSections(sectionsWithAll);
      
      // Auto-select logic
      if (setSelectedSection && typeof setSelectedSection === 'function') {
        // If coordinator has multiple sections, default to "All Sections"
        if (authorizeRole === 'coordinator' && availableSections.length > 1) {
          setSelectedSection({ id: null, name: "All Sections" });
        } 
        // If coordinator has only one section, auto-select that section
        else if (authorizeRole === 'coordinator' && availableSections.length === 1) {
          setSelectedSection({ id: availableSections[0].id, name: availableSections[0].name });
        }
        // For other roles, default to "All Sections" if available
        else if (sectionsWithAll.length > 0 && sectionsWithAll[0].id === null) {
          setSelectedSection({ id: null, name: "All Sections" });
        }
      }
    }
  } catch (error) {
    console.log(error);
  } finally {
    setLoading(false);
  }
};

/**
 *
 *
 * GET
 *
 *
 */
export const fetchData = async ({
  requestedBy,
  setLoading,
  paginationModel,
  setRows,
  setTotalCount,
  searchTerm,
  sectionID,
}) => {
  // Set Loading
  setLoading(true);

  /* console.log({
    page: paginationModel.page + 1,
    perPage: paginationModel.pageSize,
    search: searchTerm,
    requestedBy: requestedBy,
    section: sectionID,
  }); */

  try {
    const response = await getRequest({
      url: GET_ALL_STUDENTS_BY_SECTION,
      params: {
        page: paginationModel.page + 1,
        perPage: paginationModel.pageSize,
        search: searchTerm,
        requestedBy: requestedBy,
        sectionID: sectionID,
      },
    });

    console.log(response);

    if (response) {
      setRows(response.data);
      setTotalCount(response.meta.total);
    }
  } catch (error) {
    console.error(error);
  } finally {
    setLoading(false);
  }
};

/**
 *
 *
 *
 * GET (WEEKLY)
 *
 *
 *
 */
export const fetchWeeklyByStudent = async ({
  setLoading,
  setRows,
  studentID,
}) => {
  // Set Loading
  setLoading(true);

  try {
    const response = await axiosClient.get(
      GET_ALL_WEEKLY_ENTRIES_BY_STUDENT(studentID)
    );

    // Check response
    if (response && response.status === 200) {
      setRows(response.data);
    }
  } catch (error) {
    console.error(error);
  } finally {
    setLoading(false);
  }
};

/**
 *
 *
 * POST
 *
 *
 */
export const addSection = async ({
  setLoading,
  setSections,
  authorizeRole,
  payload,
  setIsOpenSection,
}) => {
  // Set Loading
  setLoading(true);

  try {
    const response = await axiosClient.post(ADD_SECTION_URL, payload, {
      params: {
        requestedBy: authorizeRole,
      },
    });

    // Check response
    if (response && response.status === 201) {
      setIsOpenSection(false);
      setSections((prevState) => [...prevState, response.data.data]);
    }
  } catch (error) {
    console.error(error);
  } finally {
    setLoading(false);
  }
};

/**
 *
 *
 *
 * PUT
 *
 *
 */
export const assignStudentToSection = async ({
  sectionID,
  setLoading,
  payload,
  setRows,
  setIsSectionAssignOpen,
}) => {
  // Set Loading
  setLoading(true);

  try {
    const response = await axiosClient.put(
      ASSIGN_STUDENT_BY_SECTION_URL(sectionID),
      payload
    );

    if (response && response.status === 200) {
      showSuccessAlert(response.data.message);
      setIsSectionAssignOpen(false);
    }
  } catch (error) {
    console.error(error);
  } finally {
    setLoading(false);
  }
};
