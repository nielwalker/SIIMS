import React from "react";
import { Page, Image, Document, StyleSheet, View, Text } from "@react-pdf/renderer";
import image1 from "../../assets/images/logo/head.png";
import image2 from "../../assets/images/logo/CITC_LOGO.png";
import { Table, TH, TR, TD } from "@ag-media/react-pdf-table";
import { formatDateOnly } from "../../utils/formatDate";

const styles = StyleSheet.create({
  body: {
    paddingTop: 35,
    paddingBottom: 65,
    paddingHorizontal: 35,
  },
  headerContainer: {
    flexDirection: "row",
    alignItems: "left",
    marginBottom: 20,
  },
  image1: {
    flexGrow: 1,
    height: 150,
    objectFit: "cover",
  },
  image2: {
    flexGrow: 1,
    height: 40,
    objectFit: "cover",
  },
  title: {
    textAlign: "center",
    fontSize: 12,
    marginTop: 6,
    marginBottom: 6,
    fontWeight: 700,
  },
  subTitle: {
    textAlign: "center",
    fontSize: 10,
    marginBottom: 10,
  },
  infoRow: {
    flexDirection: "row",
    fontSize: 10,
    marginBottom: 4,
  },
  label: { width: 130, fontWeight: 700 },
  value: { flex: 1 },
  tableHeader: {
    backgroundColor: "#f0f0f0",
    fontWeight: "bold",
    textAlign: "left",
    fontSize: 11,
    padding: 5,
  },
  tableCell: {
    textAlign: "center",
    fontSize: 11,
    padding: 5,
  },
  footBlock: { marginTop: 24, fontSize: 10 },
  underlineName: {
    textDecoration: 'underline',
    textAlign: 'center',
    marginTop: 6,
  },
  signRow: { flexDirection: 'row', alignItems: 'flex-start', marginTop: 24 },
  signLabel: { width: 100, fontSize: 10 },
  signBlock: { marginLeft: 8, width: 220 },
  signRole: { fontSize: 10, textAlign: 'left' },
  textLeft: {
    textAlign: "left",
  },
});

const GenerateWeeklyAccomplishmentReport = ({
  imageHeight = 80,
  weeklyEntries = [],
  header = {
    studentName: "",
    companyName: "",
    coordinatorName: "",
    chairpersonName: "",
    unitOfficeDept: "",
    traineeName: "",
    supervisorName: "",
    periodStart: "",
    periodEnd: "",
  },
}) => {
  const stripHtml = (html) => String(html || "").replace(/<[^>]*>/g, " ").replace(/&nbsp;/g, " ").replace(/\s+/g, " ").trim();
  const totalHours = weeklyEntries.reduce((acc, r) => acc + Number(r.no_of_hours || 0), 0);
  return (
    <Document>
      <Page size="A4" style={styles.body}>
        {/* Header with 2 images */}
        <View style={styles.headerContainer} fixed>
          <Image
            src={image1}
            style={{ ...styles.image1, height: imageHeight }}
          />
          <Image
            src={image2}
            style={{ ...styles.image2, height: imageHeight }}
          />
        </View>

        {/* Title and Period */}
        <Text style={styles.title}>IT421 - PRACTICUM</Text>
        <Text style={styles.title}>WEEKLY ACCOMPLISHMENT REPORT</Text>
        <Text style={styles.subTitle}>
          For the Period {header.periodStart || "_____"} to {header.periodEnd || "_____"}
        </Text>

        {/* Identity block in bordered, uniform two-column table */}
        <View style={{ marginBottom: 14 }}>
          <Table>
            <TR>
              <TD style={{ ...styles.tableHeader, width: 130 }}>Name:</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>{header.studentName || header.traineeName || ''}</TD>
            </TR>
            <TR>
              <TD style={{ ...styles.tableHeader, width: 130 }}>Company:</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>{header.companyName || ''}</TD>
            </TR>
            <TR>
              <TD style={{ ...styles.tableHeader, width: 130 }}>Unit/Office/Dept:</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>{header.unitOfficeDept || ''}</TD>
            </TR>
          </Table>
        </View>

        {/* Table Area */}
        <View>
          <Table>
            {/* Header Row */}
            <TH>
              <TD style={styles.tableHeader}>Date</TD>
              <TD style={styles.tableHeader}>No. of Hours</TD>
              <TD style={styles.tableHeader}>Activities/Tasks (Success Indicator)</TD>
              <TD style={styles.tableHeader}>Score: Accomplished/Targets</TD>
              <TD style={styles.tableHeader}>New Learnings (example: technical, interpersonal)</TD>
            </TH>

            {/* Body Rows */}
            {weeklyEntries.map((record, index) => (
              <TR key={index}>
                <TD style={styles.tableCell}>{formatDateOnly(record.start_date)}</TD>
                <TD style={styles.tableCell}>{record.no_of_hours} hrs</TD>
                <TD style={{ ...styles.tableCell, ...styles.textLeft }}>{stripHtml(record.tasks)}</TD>
                <TD style={styles.tableCell}>{""}</TD>
                <TD style={{ ...styles.tableCell, ...styles.textLeft }}>{stripHtml(record.learnings)}</TD>
              </TR>
            ))}
            <TR>
              <TD style={{ ...styles.tableCell, fontWeight: 700 }}>Total Hours:</TD>
              <TD style={{ ...styles.tableCell, fontWeight: 700 }}>{totalHours} hrs</TD>
              <TD style={styles.tableCell}></TD>
              <TD style={styles.tableCell}></TD>
              <TD style={styles.tableCell}></TD>
            </TR>
          </Table>
        </View>
      </Page>

      {/* Second page: Ratings table and signatories */}
      <Page size="A4" style={styles.body}>
        <View style={styles.headerContainer} fixed>
          <Image src={image1} style={{ ...styles.image1, height: imageHeight }} />
          <Image src={image2} style={{ ...styles.image2, height: imageHeight }} />
        </View>
        <View>
          <Table>
            <TH>
              <TD style={{ ...styles.tableHeader, textAlign: 'center' }}>Rating (Numerical)</TD>
              <TD style={{ ...styles.tableHeader, textAlign: 'center' }}>Rating (Adjectival)</TD>
              <TD style={{ ...styles.tableHeader, textAlign: 'center' }}>Description</TD>
            </TH>
            <TR>
              <TD style={styles.tableCell}>5</TD>
              <TD style={styles.tableCell}>Outstanding</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>100% and above meeting the success indicators.</TD>
            </TR>
            <TR>
              <TD style={styles.tableCell}>4</TD>
              <TD style={styles.tableCell}>Very Satisfactory</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>90% to 99.99% of the success indicators.</TD>
            </TR>
            <TR>
              <TD style={styles.tableCell}>3</TD>
              <TD style={styles.tableCell}>Satisfactory</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>80% to 89.99% of the success indicators.</TD>
            </TR>
            <TR>
              <TD style={styles.tableCell}>2</TD>
              <TD style={styles.tableCell}>Unsatisfactory</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>70% to 79.99% of the success indicators.</TD>
            </TR>
            <TR>
              <TD style={styles.tableCell}>1</TD>
              <TD style={styles.tableCell}>Poor</TD>
              <TD style={{ ...styles.tableCell, ...styles.textLeft }}>Below 70% of the success indicators.</TD>
            </TR>
          </Table>
        </View>

        {/* Signatories */}
        {/* Signatories aligned near the labels (left side) */}
        <View style={styles.signRow}>
          <Text style={styles.signLabel}>Prepared by:</Text>
          <View style={styles.signBlock}>
            <Text style={{ ...styles.underlineName, textAlign: 'left' }}>{header.traineeName || header.studentName || ''}</Text>
            <Text style={styles.signRole}>Trainee</Text>
          </View>
        </View>
        <View style={styles.signRow}>
          <Text style={styles.signLabel}>Approved by:</Text>
          <View style={styles.signBlock}>
            <Text style={{ ...styles.underlineName, textAlign: 'left' }}>{header.supervisorName || ''}</Text>
            <Text style={styles.signRole}>Supervisor</Text>
          </View>
        </View>
      </Page>
    </Document>
  );
};

export default GenerateWeeklyAccomplishmentReport;
