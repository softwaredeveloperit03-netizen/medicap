export interface ChecklistItem {
  item_no: string;
  check_point: string;
  response: string;
  remarks: string;
}

export interface ChecklistPage {
  page_no: number;
  title: string;
  items: ChecklistItem[];
}

export function createDefaultChecklistPages(): ChecklistPage[] {
  return [
    {
      page_no: 2,
      title: 'Quality Management & Documentation',
      items: [
        { item_no: '2.1', check_point: 'Quality Assurance System is established and documented', response: '', remarks: '' },
        { item_no: '2.2', check_point: 'Self inspection / internal audit system is in place', response: '', remarks: '' },
        { item_no: '2.3', check_point: 'Approved SOPs are available for all QC activities', response: '', remarks: '' },
        { item_no: '2.4', check_point: 'Document control and revision management is effective', response: '', remarks: '' },
        { item_no: '2.5', check_point: 'Change control procedures are followed', response: '', remarks: '' },
        { item_no: '2.6', check_point: 'Deviation and CAPA procedures are documented and followed', response: '', remarks: '' },
        { item_no: '2.7', check_point: 'Laboratory documentation system is adequate', response: '', remarks: '' },
        { item_no: '2.8', check_point: 'Data traceability and review procedures are in place', response: '', remarks: '' },
      ],
    },
    {
      page_no: 3,
      title: 'Personnel, Premises & Equipment',
      items: [
        { item_no: '3.1', check_point: 'Personnel qualifications and training records are maintained', response: '', remarks: '' },
        { item_no: '3.2', check_point: 'Job descriptions and responsibilities are defined', response: '', remarks: '' },
        { item_no: '3.3', check_point: 'Health, hygiene and gowning requirements are followed', response: '', remarks: '' },
        { item_no: '3.4', check_point: 'Laboratory premises are suitable and adequately maintained', response: '', remarks: '' },
        { item_no: '3.5', check_point: 'Environmental controls (temp, humidity, pressure) are monitored', response: '', remarks: '' },
        { item_no: '3.6', check_point: 'Equipment is identified and status labelled', response: '', remarks: '' },
        { item_no: '3.7', check_point: 'Equipment calibration program is current and documented', response: '', remarks: '' },
        { item_no: '3.8', check_point: 'Equipment maintenance and qualification records are available', response: '', remarks: '' },
        { item_no: '3.9', check_point: 'Cleaning and sanitation of equipment and premises is adequate', response: '', remarks: '' },
      ],
    },
    {
      page_no: 4,
      title: 'Materials, Sampling & Testing',
      items: [
        { item_no: '4.1', check_point: 'Reference standards are controlled and traceable', response: '', remarks: '' },
        { item_no: '4.2', check_point: 'Reagents, chemicals and supplies are properly labelled and stored', response: '', remarks: '' },
        { item_no: '4.3', check_point: 'Water / water systems are qualified and monitored', response: '', remarks: '' },
        { item_no: '4.4', check_point: 'Sampling procedures and representative sampling are followed', response: '', remarks: '' },
        { item_no: '4.5', check_point: 'Sample identification, handling and storage are adequate', response: '', remarks: '' },
        { item_no: '4.6', check_point: 'Raw material testing procedures are followed', response: '', remarks: '' },
        { item_no: '4.7', check_point: 'In-process and intermediate testing controls are in place', response: '', remarks: '' },
        { item_no: '4.8', check_point: 'Finished product testing procedures are followed', response: '', remarks: '' },
        { item_no: '4.9', check_point: 'Analytical method validation documentation is available', response: '', remarks: '' },
      ],
    },
    {
      page_no: 5,
      title: 'Results, Stability & Data Integrity',
      items: [
        { item_no: '5.1', check_point: 'Test result review, approval and release procedures are followed', response: '', remarks: '' },
        { item_no: '5.2', check_point: 'OOS / OOT investigation procedures are documented and followed', response: '', remarks: '' },
        { item_no: '5.3', check_point: 'Retesting and re-sampling procedures are controlled', response: '', remarks: '' },
        { item_no: '5.4', check_point: 'Stability testing program is established and current', response: '', remarks: '' },
        { item_no: '5.5', check_point: 'Data integrity principles (ALCOA+) are followed', response: '', remarks: '' },
        { item_no: '5.6', check_point: 'Electronic records and audit trail controls are adequate', response: '', remarks: '' },
        { item_no: '5.7', check_point: 'Laboratory notebooks / worksheets are maintained properly', response: '', remarks: '' },
        { item_no: '5.8', check_point: 'Retention / reference sample management is adequate', response: '', remarks: '' },
        { item_no: '5.9', check_point: 'Overall audit observations and conclusions documented', response: '', remarks: '' },
      ],
    },
  ];
}

export interface CompanyContact {
  name: string;
  title: string;
}

export function createDefaultContacts(): CompanyContact[] {
  return Array.from({ length: 4 }, () => ({ name: '', title: '' }));
}
