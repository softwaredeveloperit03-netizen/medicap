import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

interface LogEntry {
  entry_date: string;
  instrument_id: string;
  equipment_name: string;
  calibration_due: string;
  air_velocity: string;
  magnehelic_reading_1: string;
  magnehelic_reading_2: string;
  magnehelic_reading_3: string;
  performed_by: string;
  reviewed_by: string;
}

@Component({
  selector: 'app-booth-monitoring-new-log',
  templateUrl: './new-log.component.html',
  styleUrls: ['./new-log.component.css']
})
export class NewLogComponent implements OnInit {
  sheet_no = '';
  log_date = '';
  entries: LogEntry[] = [];
  samplingEquipments: any[] = [];
  saving = false;
  empName = '';

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.log_date = new Date().toISOString().substring(0, 10);
    this.empName = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
    this.initRows(1);
    this.getNextSheetNo();
    this.loadSamplingEquipments();
  }

  initRows(count: number) {
    this.entries = [];
    for (let i = 0; i < count; i++) {
      this.entries.push(this.emptyRow());
    }
  }

  emptyRow(): LogEntry {
    return {
      entry_date: this.log_date,
      instrument_id: '',
      equipment_name: '',
      calibration_due: '',
      air_velocity: '',
      magnehelic_reading_1: '',
      magnehelic_reading_2: '',
      magnehelic_reading_3: '',
      performed_by: this.empName,
      reviewed_by: ''
    };
  }

  addRow() {
    this.entries.push(this.emptyRow());
  }

  getNextSheetNo() {
    this.service.get('qc/sampling/booth_monitoring.php?type=getNextSheetNo').subscribe((res: any) => {
      this.sheet_no = res.sheet_no || '';
    });
  }

  loadSamplingEquipments() {
    this.service.get('qc/sampling/booth_monitoring.php?type=getSamplingEquipments').subscribe((res: any) => {
      this.samplingEquipments = Array.isArray(res) ? res : [];
    });
  }

  fillFromEquipment(row: LogEntry, equipmentCode: string) {
    if (!equipmentCode) {
      row.instrument_id = '';
      row.equipment_name = '';
      row.calibration_due = '';
      return;
    }
    const eq = this.samplingEquipments.find(e => e.equipment_code === equipmentCode);
    if (!eq) {
      row.instrument_id = '';
      row.equipment_name = '';
      return;
    }
    row.instrument_id = eq.equipment_code;
    row.equipment_name = eq.equipment_name || '';
    if (eq.calibration_due) {
      row.calibration_due = String(eq.calibration_due).substring(0, 10);
    }
  }

  formatEquipmentLabel(eq: any): string {
    return (eq.equipment_name || '') + ' (' + (eq.equipment_code || '') + ')';
  }

  validate(): boolean {
    const filled = this.entries.filter(e => e.instrument_id || e.air_velocity);
    if (filled.length === 0) {
      alertify.error('Enter at least one monitoring row.');
      return false;
    }
    for (const row of filled) {
      if (!row.entry_date) {
        alertify.error('Date is required for each filled row.');
        return false;
      }
      if (!row.instrument_id) {
        alertify.error('Select Instrument ID (Sampling Equipment) for each filled row.');
        return false;
      }
      if (!row.air_velocity) {
        alertify.error('Air Velocity is required for each filled row.');
        return false;
      }
      const velocity = parseFloat(row.air_velocity);
      if (!isNaN(velocity) && (velocity < 72 || velocity > 108)) {
        alertify.error('Air Velocity must be between 72 and 108 ft/min.');
        return false;
      }
      if (!row.performed_by) {
        alertify.error('Performed By is required for each filled row.');
        return false;
      }
    }
    return true;
  }

  save() {
    if (!this.validate()) return;
    this.saving = true;
    const payload = {
      sheet_no: this.sheet_no,
      log_date: this.log_date,
      entry_by: localStorage.getItem('emp_id'),
      entries: this.entries
    };
    this.service.postJson('qc/sampling/booth_monitoring.php?type=saveBoothMonitoringLog', JSON.stringify(payload))
      .subscribe((res: any) => {
        this.saving = false;
        if (res && res.status === 'success') {
          alertify.success('Booth monitoring log saved.');
          this.router.navigate(['/qc/sampling/booth-monitoring/log']);
        } else {
          alertify.error((res && (res.message || res.status)) || 'Save failed');
        }
      }, (err) => {
        this.saving = false;
        const msg = err && err.error && (err.error.message || err.error.status || err.error);
        alertify.error(typeof msg === 'string' ? msg : 'Save failed');
      });
  }
}
