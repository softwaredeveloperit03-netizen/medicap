export interface StandardSubtest {
  subtest: string;
}

export interface StandardTestTemplate {
  test_type: string;
  test: string;
  testCode: string;
  subtestList: StandardSubtest[];
}

/** Common QC test master templates with subtests for Medicap. */
export const STANDARD_TEST_TEMPLATES: StandardTestTemplate[] = [
  {
    test_type: 'Chemical',
    test: 'Assay',
    testCode: 'TM-001',
    subtestList: [
      { subtest: 'Standard Preparation' },
      { subtest: 'Sample Preparation' },
      { subtest: 'Procedure' },
      { subtest: 'Calculation' },
    ],
  },
  {
    test_type: 'Indentification',
    test: 'Identification',
    testCode: 'TM-002',
    subtestList: [
      { subtest: 'By IR Spectrum' },
      { subtest: 'By HPLC Retention Time' },
      { subtest: 'By TLC Rf Value' },
    ],
  },
  {
    test_type: 'Chemical',
    test: 'Related Substances',
    testCode: 'TM-003',
    subtestList: [
      { subtest: 'Standard Preparation' },
      { subtest: 'Sample Preparation' },
      { subtest: 'Chromatographic Conditions' },
      { subtest: 'Calculation' },
    ],
  },
  {
    test_type: 'LOD',
    test: 'Loss on Drying',
    testCode: 'TM-004',
    subtestList: [{ subtest: 'Procedure' }, { subtest: 'Calculation' }],
  },
  {
    test_type: 'Chemical',
    test: 'Water Content',
    testCode: 'TM-005',
    subtestList: [{ subtest: 'Karl Fischer Procedure' }, { subtest: 'Calculation' }],
  },
  {
    test_type: 'Physcial',
    test: 'Description',
    testCode: 'TM-006',
    subtestList: [{ subtest: 'Appearance' }, { subtest: 'Colour' }],
  },
  {
    test_type: 'Chemical',
    test: 'pH',
    testCode: 'TM-007',
    subtestList: [{ subtest: 'Procedure' }],
  },
  {
    test_type: 'Microbiology',
    test: 'Microbial Limit',
    testCode: 'TM-008',
    subtestList: [
      { subtest: 'Total Aerobic Microbial Count' },
      { subtest: 'Total Yeast and Mould Count' },
      { subtest: 'Escherichia coli' },
      { subtest: 'Salmonella' },
    ],
  },
];
