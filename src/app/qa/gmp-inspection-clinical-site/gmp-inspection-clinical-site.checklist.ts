export interface ChecklistItem {
  item_no: string;
  check_point: string;
  response: string;
  comments: string;
}

export interface ChecklistSection {
  section_no: string;
  section_title: string;
  items: ChecklistItem[];
}

export interface ChecklistPage {
  page_no: number;
  sections: ChecklistSection[];
}

export interface SiteContact {
  name: string;
  title: string;
}

export function createDefaultContacts(): SiteContact[] {
  return Array.from({ length: 4 }, () => ({ name: '', title: '' }));
}

function item(no: string, point: string): ChecklistItem {
  return { item_no: no, check_point: point, response: '', comments: '' };
}

export function createDefaultChecklistPages(): ChecklistPage[] {
  return [
    {
      page_no: 1,
      sections: [
        {
          section_no: '1',
          section_title: 'SECTION 1: General Information',
          items: [
            item('1', 'Organization Chart'),
            item('2', 'Agencies Inspection'),
            item('3', 'Computer Systems on site, validation etc.'),
          ],
        },
        {
          section_no: '2',
          section_title: 'SECTION 2: Buildings and Facilities',
          items: [
            item('1', 'Floor Plan of Clinical Research Center, Laboratories, Archives area, freezer areas, corridors adequate size?'),
            item('2', 'Back-up system for power failure'),
            item('3', 'Space is adequate for employees?'),
            item('4', 'Utilities, HVAC, water system? PM.etc.'),
          ],
        },
        {
          section_no: '3',
          section_title: 'SECTION 3: Personnel',
          items: [
            item('1', 'Number of Employees'),
            item('2', 'Education, degree and qualification'),
            item('3', 'Training program'),
          ],
        },
      ],
    },
    {
      page_no: 2,
      sections: [
        {
          section_no: '3',
          section_title: 'SECTION 3: Personnel (continued)',
          items: [item('4', 'Person(s) in charge')],
        },
        {
          section_no: '4',
          section_title: 'SECTION 4: Equipment',
          items: [
            item('1', 'List of Instruments and equipment'),
            item('2', 'Equipment mentioned in the study protocol'),
            item('3', 'Unique numbering of equipment'),
            item('4', 'Qualification of equipment'),
            item('5', 'Calibration and PM and log books'),
            item('6', 'Back up of electronic data frequency'),
            item('7', 'Chromatograms check'),
            item('8', 'Power generator in case of power failure? UPS?'),
          ],
        },
        {
          section_no: '5',
          section_title: 'SECTION 5: Laboratory Operation for the Study',
          items: [
            item('1', 'Screening of subject SOP? Pick up several subjects of this study for verification purposes against the above mentioned SOP and the "subject check-in" document. Verify the raw data that are kept in the computer.'),
            item('2', 'Verify the original data of the Institutional Review Board Approval of this project.'),
          ],
        },
      ],
    },
    {
      page_no: 3,
      sections: [
        {
          section_no: '5',
          section_title: 'SECTION 5: Laboratory Operation for the Study (continued)',
          items: [
            item('3', 'Check the Consent Form for this project for confidentiality statement, the description of compensation, information provided to the subjects about the trial, etc.'),
            item('4', 'Choose consent forms filled by several subjects and check them in the presence of the Study Medical Director.'),
            item('5', 'Verify the Clinical Raw Data records of the test articles, tablet storage and the dispensing room security (locked?).'),
            item('6', 'Check the quantity received vs. remaining qty.'),
            item('7', 'Check dispensing records for traceability.'),
            item('8', 'Does the flow of samples seem adequate? Describe flow sequence, likelihood of getting lost.'),
            item('9', 'Is the lab accredited or licensed as a testing laboratory? Describe certifying authorities, certificate issue/expiry dates (get copy).'),
            item('10', 'Does the analysts participate in a Proficiency Testing Program? Describe frequency of testing. Are comparative results reported back to lab., review data.'),
            item('11', 'Does the lab adequately identify in-coming samples? Describe accession # labels, barcode readers.'),
            item('12', 'Was the testing equipment observed for each category (e.g. Chemistry)? Describe equipment brand, model, capabilities (smpl/hr, tests/smpl), reagents, number of instruments, backup.'),
            item('13', 'Is testing equipment included in calibration program?'),
            item('14', 'Were equipment maintenance logs available for testing equipment?'),
            item('15', 'Do the testing labs utilize Control Charts? Describe: Adequate?'),
            item('16', 'Does the lab has written SOPs? Describe: cover equipment testing, Quality Systems, adequate, etc.'),
            item('17', 'Does the lab perform special assays (e.g. HPLC etc.)?'),
            item('18', 'Are equipment validated?'),
          ],
        },
      ],
    },
    {
      page_no: 4,
      sections: [
        {
          section_no: '5',
          section_title: 'SECTION 5: Laboratory Operation for the Study (continued)',
          items: [
            item('19', 'Are test methods validated?'),
            item('20', 'Do you have training program for Medical Technologist, qualifications required? Review CV\'s.'),
            item('21', 'Does the lab have the capability to print hardcopy results at other locations? Describe: Lab processing centers, Investigator Sites, Sponsor\'s location, etc.'),
            item('22', 'Are there any Computer Systems? Are they validated? Review protocols and reports.'),
            item('23', 'Are procedures for getting samples to the lab satisfactory? Describe: kits & requisition forms provided to Sites, call courier, etc.?'),
            item('24', 'Does the lab retain samples, in case re-runs are required? How long?'),
            item('25', 'Are the procedures for getting hardcopy results back to the Sites satisfactory? Describe: mail, Fax, courier, air handler, printer, etc.?'),
            item('26', 'Are the results coming back complete or in several parts?'),
            item('27', 'Is the run-around time for hardcopy results satisfactory? Describe: days, hours, etc.'),
            item('28', 'Does the lab make "Phone Alerts" to the Site? Criteria adequate? Call made to proper person? What is the procedure?'),
          ],
        },
        {
          section_no: '6',
          section_title: 'SECTION 6: Clinical Facilities and Operations',
          items: [
            item('1', 'How many projects can you run at the same time in the Clinical Research Center? At the Dosing and Collection Room?'),
            item('2', 'Observe collection of blood samples taken from subjects, the procedure in use, how samples and subjects are identified and find the correlation between raw data and the above.'),
          ],
        },
      ],
    },
    {
      page_no: 5,
      sections: [
        {
          section_no: '6',
          section_title: 'SECTION 6: Clinical Facilities and Operations (continued)',
          items: [
            item('3', 'Check the blood samples freezer for temp monitoring documenting in the log book.'),
          ],
        },
        {
          section_no: '7',
          section_title: 'SECTION 7: Quality Assurance',
          items: [
            item('1', 'QA\'s responsibilities'),
            item('2', 'SOPs distribution, retrieval and maintenance'),
            item('3', 'Training program'),
            item('4', 'Agencies Inspection records'),
          ],
        },
        {
          section_no: '8',
          section_title: 'SECTION 8: Records',
          items: [
            item('1', 'Check in the computer (where raw data are kept) subject data/profiles (means) of unchanged drug blood samples taken at different times and individual subject profiles of whole blood drug concentration vs. time for two subjects and compare with the provided data, graphs and calculations of the submission.'),
          ],
        },
      ],
    },
    {
      page_no: 6,
      sections: [
        {
          section_no: '8',
          section_title: 'SECTION 8: Records (continued)',
          items: [
            item('2', 'Check the following forms in the original submission: Study specific check-in questionnaire; Master actual record of water administration; Master actual dosing; Master actual times for blood sampling; Vital signs report. Compare with the provided copy of the submission.'),
            item('3', 'Obtain CofA of the test substance and detailed results of dissolution test conducted on the test and reference substances.'),
          ],
        },
        {
          section_no: '9',
          section_title: 'SECTION 9: Samples',
          items: [
            item('1', 'Storage of blood samples, location and temp. ID# freezers.'),
            item('2', 'Pick random samples and check the storage conditions. Compare info. to the submission information.'),
            item('3', 'Stability studies conducted on the blood samples, period and temperature?'),
            item('4', 'Samples of test articles can be taken during the inspection and send to FDA lab if applicable'),
          ],
        },
      ],
    },
    {
      page_no: 7,
      sections: [
        {
          section_no: '10',
          section_title: 'SECTION 10: Archives',
          items: [
            item('1', 'Evaluate the archive system vs. the SOP.'),
            item('2', 'Find out how long for a retrieved document allowed for consultation before their return to the archive. Is this part of the SOP?'),
            item('3', 'How long the data are kept before disposal?'),
          ],
        },
      ],
    },
  ];
}
