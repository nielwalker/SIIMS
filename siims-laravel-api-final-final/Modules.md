### Role Management (Admin) - Ready for testing

-   Admin can create a new record of role (Ready for testing)
-   Admin can view the list of roles (Ready for testing)
-   Admin can view the list of user roles of each user (Ready for testing)

### College Management (Admin) - Pending

-   Admin can add a new record of college. (Ready for testing)
-   Admin can update a record of college by ID. (Ready for testing)
-   Admin can view the list of colleges (including the total programs, and the dean assigned). (Ready for testing)
-   Admin can assign a dean user to a college. (Ready for testing)
-   Admin can import a file containing the list of colleges and creates a new records. (Pending)

### Program Management (Admin, Dean) - Ready for testing

-   Admin can add a new record of the program and must assign it to a college. (Ready for testing)
-   Admin can update a record of the program by ID. (Ready for testing)
-   Admin can archive a record of the program by ID. (Ready for testing)
-   Admin can assign a chairperson in the program. (Ready for testing)
-   Admin can view the list of programs including their college, chairperson assigned, total of students and coordinators in that program. (Ready for Testing)
-   Dean can add a new record of the program based on his assigned college. (Ready for testing)
-   Dean can update a record of the program by ID based on his/her assigned college.(Ready for testing)
-   Dean can view the list of programs based on his assigned college including the total of students, and total of coordinators in each program. (Ready for testing)

### Document Management (Admin, OSA) - Ready for testing

-   Note: The system has already a pre-defined document types based on the Student Handbook and Interviews as the source references.
-   Admin can create a new record of document type. (Ready for testing)
-   Admin can view the list of document types. (Ready for testing)
-   Admin can update a record of document type by ID. (Ready for testing)
-   Admin can archive a record of document type by ID. (Ready for testing)
-   OSA can create a new record of document type. (Ready for testing)
-   OSA can view the list of document types. (Ready for testing)
-   OSA can update a record of document type by ID. (Ready for testing)
-   OSA can archive a record of document type by ID. (Ready for testing)

### Office Management (Admin, Company) - Ready for testing

-   Admin can create a record of office by visiting the company user. (Ready for testing)
-   Admin can view the list of offices by visiting the company user. (Ready for testing)
-   Admin can update a record of office by ID but visit the company user first. It can also assign an supervisor. (Ready for testing)
-   Admin can delete a record of office by ID but visit the company user first. (Ready for testing)
-   Company can create a record of office. (Ready for testing)
-   Company can view the list of offices. (Ready for testing)
-   Company can update a record of office by ID. (Ready for testing)
-   Company can delete a record of office by ID. (Ready for testing)

# Work Experience Management (Student)

-   Student can create a record of work experience
-   Student can delete a record of work experience
-   Student can update a record of work experience

# Education Management (Student)

-   Student can create a record of education
-   Student can update a record of education
-   Student can delete a record of education

### User Management (Admin, Chairperson, Dean, Company)

### User Management with multiple roles (Admin)

### Job Management (Admin, Supervisor, Company)

-   Supervisor can view the list of job posts based on his assigned office. (Include: Work Type, Applicants who apply in this job) (Ready for testing)
-   Supervisor can add a new job post record based on his assigned office. (Ready for testing)
-   Supervisor can update a job post record by ID but based on his assigned office. (Ready for testing)
-   Supervisor can delete a job post record by ID but based on his assigned office. (Ready for testing)
-   Company can create a job post and assign it to an office. (Ready for testing)
-   Company can view the list of job posts. (Ready for testing)
-   Company can delete a job post by ID. (Ready for testing)
-   Company can update a job post by ID. (Ready for testing)
-   Admin can add a new job post record and assign it to an office by visiting the company user. (Not priority)
-   Admin can view the list of job posts records by visiting the company user. (Not priority)
-   Admin can update a job post record by ID but first visit the company user. (Not priority)
-   Admin can delelte a job post record by ID but first visit the company user. (Not priority)

### Dean Management (Admin)

-   Admin can create new dean user. (Ready for Testing)
-   Admin can update a dean user. (Ready for Testing)
-   Admin can view the list of dean user. (Ready for Testing)
-   Admin can archive the list of dean user. (Ready for Testing)

### Student Management Page (Admin, Dean, Chairperson, Coordinator)

-   Chairperson can add a new student record base on assigned program.
-   Chairperson can import a file that contains a student (required fields: student_id, full_name).
-   Chairperson can view the list of students including their coordinator (Backend Done)
-   Chairperson can assign multiple student to a coordinator in the same program. (Backend Done)
-   Coordinator can view the list of students that is assigned by him. (Backend Done)
-   Coodinator can view a student by ID. (Backend Done)
-   Coordinator can view his/her student application progress. (TBD)
-   Coordinator can view the student's document submissions. (TBD)
-   Admin can view the list of students including their program. (Not Priority)
-   Admin can add a new student and assign it to a program. (Not Priority)
-   Admin can edit a student by ID including the program_id. (Not Priority)
-   Admin can delete a student by ID. (Not Priority)
-   Admin can import a file that contains a student (required fields: student_id, full_name).

### Coordinator Management Page (Admin, Coordinator, Chairperson)

-   Chairperson can view the coordinators in the same program. (Ready for testing)
-   Coordinator can view their own profile. (Ready for testing)
-   Coordinator can edit their own profile. (Ready for testing)
-   Admin can add new coordinator and assign it to a program.
-   Admin can view the list of coordinators.
-   Admin can update a coordinator.
-   Admin can delete a coordinator.

##### TBD

-   Coordinator can view the acceptance letter.
-   Coordinator can deploy (imagine a button) student after submitting all documents
-   Coordinator can view the daily time record
-   Coordinator can view the weekly accomplishment report
-   Coordinator can view the performance evaluation

### Application Management (Admin, Student, Chairperson, Dean, OSA)

-   Student can view the list of work posts. (Ready for testing)
-   Student can create a new record of application after applying. (Ready for testing)
-   Student can can view the list of step 1 documents. (Ready for testing)
-   Student can submit step 1 documents. (Backend Done)
-   Student can update a step 1 document. (Backend Done)
-   Chairperson can view all endorsement requests. (Backend Done)
-   Supervisor can view an application by id. (Backend Done)
-   Supervisor can update an application by id.
-   OSA can view all applicants. (Ready for testing)
-   Company can view all applicants. (Backend Done)
-   Company can update a application status.

-   Supervisor can view the the document submissions by application_id. (Pending)

-   Company can view the the document submissions.
-   Student can view the list of documents to be submitted. (Partial Complete)
-

-   Chaiperson can view the list of applications by student_id and student's program_id.

-   Student views the list of jobs (Ready for testing) (Source: /student/jobs, getAllJobs)
-   Student view the job by ID (In Progress for Front-end)
-   Student applies for the job by ID (Ready for testing) (Source: /student/jobs/job_id/apply, applyJob)
-   Proceeds to Step 1 (Ready for testing) (Source: /student/jobs/job_id/apply, applyJob)
-   Submit Step 1 required documents:

    -   Student can view the step one document submissions. (Ready for testing) (Source: /student/applications/{application_id}/document-submissions/step-1/get, getStepOneAllDocuments)
    -   Coordinator can view the student applications. (Ready for testing) (Source: /coordinator/students/{student_id}/applications, getStudentApplicationById)
    -   Coordinator can view the student application process. (Ready for testing) (Source: /coordinator/students/{student_id}/applications/{application_id}, getStudentApplicationById)
    -   Student uploads an application Letter (Ready for testing) (Source: /student/applications/{application_id}/upload-document/{document_submission_id}, uploadDocument)
    -   Student uploads an endorsement Letter (Ready for testing) (Source: /student/applications/{application_id}/upload-document/{document_submission_id}, uploadDocument)
    -   Student uploads a cover Letter (Ready for testing) (Source: /student/applications/{application_id}/upload-document/{document_submission_id}, uploadDocument)
    -   Student can request for endorsement letter. (Ready for testing) (Source: /student/applications/{application_id}/request-endorsement-letter, requestEndorsementLetterByApplicationId)
    -   Chairperson can view the list of endorsement letter requests. (Ready for testing) (Source: /chairperson/endorsement-letter-requests, getAllEndorsementLetterRequests)
    -   Chairperson can view a specific endorsement letter request. (Partial) (Source: /chairperson/endorsement-letter-requests/{endorsement_request_id}, getEndorsementLetterRequest)
    -   NEED ANALYSIS - Supervisor can view the list of applicants by office_id (Ready for testing) (Source: /supervisor/applicants)
    -   NEED ANALYSIS - Supervisor can view a specific applicant (Ready for testing) (Source: /supervisor/applicants/{application_id}, getApplicantApplicationById)

    -   Company can view the list of applicants (Ready for testing) (Source: /company/applicants, getAllApplicants)

    -   Company can view a specific applicant (Ready for testing) (Source: /company/applicants/{application_id}, getApplicantApplicationById)
    -   Company can update a document submission (Ready for testing) (Source: /company/applicants/{application_id}, updateApplicantApplicationById)
    -   Company can send an application letter and auto update all document_submission into approve. (Ready for testing) (Source: /company/applicants/submit-acceptance-letter, addAcceptanceLetter)
    -   The system will auto generate some documents based on the student's handbook (e.g. Medical Certificate). (Ready for testing) (Controller: CompanyApplicantController, updateStudentStatus)
    -   Student can view the stepTwo Document Submissions. (Ready for testing) (Source: /student/applications/{application_id}/document-submissions/step-2/get, getStepTwoAllDocuments)
    -   OSA can view the applicant's applications. (Ready for testing) (Source: /osa/applicants/{application_id}/applications, getApplicantApplications)
    -   OSA can view the applicant's application documents. (Ready for testing) (Source: /osa/applicants/{application_id}/applications/{application_id}, getApplicantApplicationById)
    -   TBA - OSA can add new document in the applicant's document_submissions by application_id (Backend Done) (Source: /osa/applicants/{applicant_id}/applications/{applicant_id}/create-document, addNewDocument)
    -   TBA - OSA can update a document type in the applicant's document_submissions by application_id (Backend Done) (Source: /osa/applicants/{applicant_id}/applications/{applicant_id}/update-document/document_id, updateDocumentById)
    -   OSA can mark a status to a document_submission by ID (Ready for testing) (Source: /osa/applicants/{applicant_id}/applications/{application_id}/status/{document_id}, updateDocumentStatus)
    -   Coordinator can deploy the student if the status is now at 11 (Ready for Deployment) (Source: /coordinator/applicants/deploy-students)

-   Step 1.1 Requesting an endorsement letter

    -   Student adds a new record of endorsement letter
    -   Student can add other students by their ID for the created endorsement letter
    -   Chairperson views the list of students requesting for endorsement letter
    -   Chairperson creates an endorsement letter
    -   Chairperson submits the endorsement letter to the dean
    -   Dean signs the endorsement letter.

    Step 1.2. Checking of initial documents (Cover Letter, Application Letter, and Endorsement Letter)

    -   Company views the list of document submissions.
    -   Company can update status, remarks each document submissions.
    -   Company creates a new document submissions named "acceptance letter" and submits a pdf of acceptance letter.
    -   Company can update the student status, or submits a application letter to approved and it will automatically updates marks the student's submissions.
    -   The application and student status will be updated into approved. The step attribute in application record will be updated into step 2.

-   Step 2 submit the other documents

    -   Initial documents to be submitted: medical certificate, notarized writted letter.
    -   OSA can create new document submissions based on the application record.
    -   Student submits each document based on the document type id
    -   OSA reviews each document and approves it
    -   Coordinator & Chairperson can view the list of document submissions
    -   Coordinator can view the list of of document submissions and if each document submissions is approve then he can update the student status to "On going Internship/Immersion". The system updates the step to 3.

-   Step 3 Access DTR and WAR
    -   Student can now access DTR and WAR
