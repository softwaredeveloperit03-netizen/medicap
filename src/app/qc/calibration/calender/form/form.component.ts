import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css'],
})
export class FormComponent implements OnInit {
  loading = false;
  isTypeModal = false;
  isGenerateModal = false;
  generateFor: 'Inhouse' | 'External' = 'Inhouse';

  equipmentslog: any[] = [];
  selectedResult: any = null;
  searchQuery = '';
  departmentFilter = '';

  calibration_type = '';
  inhouse_department = '';
  external_department = '';
  departments: Array<{ department_name?: string }> = [];

  generateFrequency = '';
  lastCaliDate = '';

  closeLink = '/qc/calibration/calender';

  frequencyOptions = ['Daily', 'Weekly', 'FortNightly', 'Monthly', 'Quarterly', 'Half-Yearly', 'Annually'];

  performDepartmentOptions = [
    'Quality Control',
    'Quality Assurance',
    'Production',
    'Store',
    'Engineering',
    'R AND D',
    'Microbiology',
  ];

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    if (returnUrl) {
      this.closeLink = returnUrl;
    }
    this.loadDepartments();
    this.getEquipmentsLog();
  }

  loadDepartments(): void {
    this.service.get('hr/employee.php?type=get_department_by_designation').subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.departments = list.length ? list : this.performDepartmentOptions.map((d) => ({ department_name: d }));
      },
      error: () => {
        this.departments = this.performDepartmentOptions.map((d) => ({ department_name: d }));
      },
    });
  }

  get departmentSelectOptions(): string[] {
    const set = new Set<string>(this.performDepartmentOptions);
    (this.departments || []).forEach((row) => {
      const name = String(row?.department_name || '').trim();
      if (name) {
        set.add(name);
      }
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b));
  }

  get departmentOptions(): string[] {
    const set = new Set<string>(this.performDepartmentOptions);
    (this.equipmentslog || []).forEach((row) => {
      if (row?.department) {
        set.add(String(row.department).trim());
      }
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b));
  }

  get filteredMaterials(): any[] {
    let rows = Array.isArray(this.equipmentslog) ? [...this.equipmentslog] : [];
    if (this.departmentFilter) {
      rows = rows.filter((row) => String(row.department || '').trim() === this.departmentFilter);
    }
    if (!this.searchQuery || !this.searchQuery.trim()) {
      return rows;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return rows.filter((row) => {
      const haystack = [
        row.equipment_code,
        row.equipment_name,
        row.department,
        row.location,
        row.description,
        row.status,
        row.calibration_type,
        row.inhouse_department,
      ].map((v) => (v == null ? '' : String(v).toLowerCase()));
      return haystack.some((v) => v.includes(query));
    });
  }

  getEquipmentsLog(): void {
    this.loading = true;
    this.service.get('engineering/calibration.php?type=getEquipments').subscribe({
      next: (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        this.equipmentslog = rows.map((row) => this.normalizeEquipmentRow(row));
        this.loading = false;
      },
      error: () => {
        this.equipmentslog = [];
        this.loading = false;
        alertify.error('Unable to load calibration equipment list.');
      },
    });
  }

  private parseFrequency(value: any): any[] {
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string' && value.trim()) {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  private typeFromMeta(freq: any[]): string {
    for (const item of freq || []) {
      if (item?.__cal_type_meta && item?.calibration_type) {
        return String(item.calibration_type).trim();
      }
    }
    return '';
  }

  private hasRealFrequency(freq: any[]): boolean {
    return (freq || []).some(
      (item) => item && !item.__cal_type_meta && item.checked && String(item.particular || '').trim()
    );
  }

  resolveCalibrationType(row: any): string {
    const direct = this.normalizeCalType(row?.calibration_type);
    if (direct) {
      return direct;
    }
    const freqIn = this.parseFrequency(row?.calibration_frequency_inhouse);
    const freqEx = this.parseFrequency(row?.calibration_frequency_external);
    const metaType = this.typeFromMeta(freqIn) || this.typeFromMeta(freqEx);
    if (metaType) {
      return metaType;
    }
    const hasIn = this.hasRealFrequency(freqIn);
    const hasEx = this.hasRealFrequency(freqEx);
    if (hasIn && hasEx) {
      return 'Both';
    }
    if (hasIn) {
      return 'Inhouse';
    }
    if (hasEx) {
      return 'External';
    }
    return '';
  }

  normalizeEquipmentRow(row: any): any {
    const freqIn = this.parseFrequency(row?.calibration_frequency_inhouse);
    const freqEx = this.parseFrequency(row?.calibration_frequency_external);
    const normalized = {
      ...row,
      calibration_frequency_inhouse: freqIn,
      calibration_frequency_external: freqEx,
      inhouse_summary: row?.inhouse_summary || { generated: false },
      external_summary: row?.external_summary || { generated: false },
    };
    normalized.calibration_type = this.resolveCalibrationType(normalized);
    return normalized;
  }

  private buildTypeMeta(type: string): any[] {
    return [
      {
        id: 0,
        __cal_type_meta: true,
        calibration_type: type,
        checked: false,
        last_cali_date: '',
        last_prevent_date: '',
      },
    ];
  }

  openSetType(row: any): void {
    this.selectedResult = row;
    this.calibration_type = row?.calibration_type || '';
    this.inhouse_department =
      row?.inhouse_department || row?.department || this.performDepartmentOptions[0] || '';
    this.external_department =
      row?.external_department || row?.inhouse_department || row?.department || this.inhouse_department;
    this.isTypeModal = true;
  }

  openGenerate(row: any, dueType: 'Inhouse' | 'External'): void {
    if (!this.normalizeCalType(row?.calibration_type)) {
      alertify.warning('Please set calibration type first.');
      this.openSetType(row);
      return;
    }
    if (dueType === 'Inhouse' && !this.isInhouseType(row)) {
      alertify.warning('Calibration type is not set for Inhouse.');
      return;
    }
    if (dueType === 'External' && !this.isExternalType(row)) {
      alertify.warning('Calibration type is not set for External.');
      return;
    }
    this.selectedResult = row;
    this.generateFor = dueType;
    this.generateFrequency = '';
    this.lastCaliDate = '';
    this.isGenerateModal = true;
  }

  scheduleStatusInhouse(row: any): string {
    const summary = row?.inhouse_summary;
    if (!summary?.generated) {
      return 'Not generated';
    }
    return summary.next_due_fmt ? `Enable ${summary.next_due_fmt}` : 'Generated';
  }

  scheduleStatusInhouseTill(row: any): string {
    const till = row?.inhouse_summary?.calendar_till_fmt;
    return till ? `Inhouse till: ${till}` : '';
  }

  scheduleStatusExternal(row: any): string {
    const summary = row?.external_summary;
    return summary?.generated
      ? (summary.next_due_fmt ? `Enable ${summary.next_due_fmt}` : 'Generated')
      : 'Not generated';
  }

  normalizeCalType(value: any): string {
    return String(value || '').trim();
  }

  isInhouseType(row: any): boolean {
    const type = this.normalizeCalType(row?.calibration_type).toLowerCase();
    return type === 'inhouse' || type === 'both';
  }

  isExternalType(row: any): boolean {
    const type = this.normalizeCalType(row?.calibration_type).toLowerCase();
    return type === 'external' || type === 'both';
  }

  canGenerateInhouse(row: any): boolean {
    if (!this.isInhouseType(row)) {
      return false;
    }
    return !row?.inhouse_summary?.generated;
  }

  canGenerateExternal(row: any): boolean {
    if (!this.isExternalType(row)) {
      return false;
    }
    return !row?.external_summary?.generated;
  }

  patchEquipmentRow(id: any, patch: Record<string, any>): void {
    const idx = this.equipmentslog.findIndex((row) => String(row?.id) === String(id));
    if (idx >= 0) {
      const updated = this.normalizeEquipmentRow({ ...this.equipmentslog[idx], ...patch });
      this.equipmentslog = [
        ...this.equipmentslog.slice(0, idx),
        updated,
        ...this.equipmentslog.slice(idx + 1),
      ];
    }
  }

  saveCalibrationType(): void {
    if (!this.calibration_type) {
      alertify.warning('Please select calibration type.');
      return;
    }
    const type = this.calibration_type;
    if ((type === 'Inhouse' || type === 'Both') && !String(this.inhouse_department || '').trim()) {
      alertify.warning('Please select inhouse department.');
      return;
    }
    if ((type === 'External' || type === 'Both') && !String(this.external_department || this.inhouse_department || '').trim()) {
      alertify.warning('Please select external department.');
      return;
    }
    const inDept = String(this.inhouse_department || '').trim();
    const exDept = String(this.external_department || inDept).trim();
    const freqIn =
      type === 'Inhouse' || type === 'Both' ? this.buildTypeMeta(type) : [];
    const freqEx =
      type === 'External' || type === 'Both' ? this.buildTypeMeta(type) : [];

    const obj = {
      id: this.selectedResult.id,
      equipment_id: this.selectedResult.id,
      calibration_type: type,
      inhouse_department: inDept,
      external_department: exDept,
      perform_department: inDept,
      calibration_frequency_inhouse: freqIn,
      calibration_frequency_external: freqEx,
    };

    this.postCalibration('update_calibration_type', obj, () => {
      this.patchEquipmentRow(this.selectedResult.id, {
        calibration_type: type,
        inhouse_department: inDept,
        external_department: exDept,
        calibration_frequency_inhouse: freqIn,
        calibration_frequency_external: freqEx,
      });
      this.isTypeModal = false;
    });
  }

  generateSchedule(): void {
    if (!this.generateFrequency) {
      alertify.warning('Please select frequency.');
      return;
    }
    if (!this.lastCaliDate) {
      alertify.warning('Please enter last calibration date.');
      return;
    }

    const freqPayload = [
      {
        id: 1,
        particular: this.generateFrequency,
        checked: true,
        last_cali_date: this.lastCaliDate,
        last_prevent_date: '',
      },
    ];

    const obj = {
      id: this.selectedResult.id,
      equipment_id: this.selectedResult.id,
      schedule_for: this.generateFor,
      calibration_frequency_inhouse:
        this.generateFor === 'Inhouse' ? freqPayload : this.selectedResult.calibration_frequency_inhouse || [],
      calibration_frequency_external:
        this.generateFor === 'External' ? freqPayload : this.selectedResult.calibration_frequency_external || [],
    };

    this.postCalibration('update_equipment_Calibration_schedule', obj, () => {
      this.isGenerateModal = false;
      this.getEquipmentsLog();
    });
  }

  private postCalibration(type: string, body: object, onSuccess: (result?: any) => void): void {
    this.service
      .postTextResponse(`engineering/calibration.php?type=${type}`, JSON.stringify(body))
      .subscribe({
        next: (text) => {
          let result: { status?: string } = {};
          try {
            result = JSON.parse(String(text || '').trim());
          } catch {
            alertify.error('Unexpected server response. Please try again.');
            return;
          }
          if (result.status === 'success') {
            alertify.success('Saved successfully');
            onSuccess(result);
          } else {
            alertify.error(result.status || 'Save failed.');
          }
        },
        error: () => alertify.error('Unable to reach calibration server.'),
      });
  }

  primaryFrequency(row: any, dueType: 'Inhouse' | 'External'): string {
    const key = dueType === 'Inhouse' ? 'calibration_frequency_inhouse' : 'calibration_frequency_external';
    const list = Array.isArray(row?.[key]) ? row[key] : [];
    const checked = list.filter((item: any) => item.checked);
    if (checked.length) {
      return checked.map((item: any) => String(item.particular || '').trim()).join(', ');
    }
    return list.length ? list.map((item: any) => String(item.particular || '').trim()).join(', ') : '-';
  }
}
