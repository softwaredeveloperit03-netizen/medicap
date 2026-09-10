export interface HplcUnit {
  id: string;
  name: string;
  make: string;
  model: string;
  location: string;
  detector: string;
  pump: string;
  autosampler: string;
  status: 'Qualified' | 'Calibration Due' | 'Out of Service';
}

export const HPLC_UNITS: HplcUnit[] = [
  {
    id: 'HPLC-01',
    name: 'Waters Alliance e2695',
    make: 'Waters',
    model: 'e2695 + 2489 UV',
    location: 'QC Lab — Room 102',
    detector: 'UV-Vis 2489',
    pump: 'Quaternary',
    autosampler: '2695',
    status: 'Qualified',
  },
  {
    id: 'HPLC-02',
    name: 'Agilent 1260 Infinity II',
    make: 'Agilent',
    model: '1260 II + DAD',
    location: 'QC Lab — Room 103',
    detector: 'DAD WR',
    pump: 'Quaternary',
    autosampler: '1260',
    status: 'Qualified',
  },
  {
    id: 'HPLC-03',
    name: 'Shimadzu LC-2030C',
    make: 'Shimadzu',
    model: 'Nexera LC-2030C',
    location: 'R&D — Room 205',
    detector: 'PDA',
    pump: 'Binary',
    autosampler: 'SIL-30',
    status: 'Calibration Due',
  },
  {
    id: 'HPLC-04',
    name: 'Waters ACQUITY UPLC',
    make: 'Waters',
    model: 'H-Class + QDa',
    location: 'QC Lab — Room 104',
    detector: 'PDA + QDa MS',
    pump: 'Binary UPLC',
    autosampler: 'FTN',
    status: 'Qualified',
  },
];
