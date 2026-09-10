import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  results: any[] = [];
  loading = false;

  isuser = 'No';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.get_rights();
    this.loadEquipments();
  }

  loadEquipments(): void {
    this.loading = true;
    this.service.get('engineering/calibration.php?type=getEquipments').subscribe({
      next: (response: any) => {
        const rows = Array.isArray(response) ? response : [];
        this.results = rows.map((row) => this.mapRow(row));
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Unable to load equipment list.');
      },
    });
  }

  private mapRow(row: any): any {
    const freqIn = this.primaryFrequency(row, 'calibration_frequency_inhouse');
    const freqEx = this.primaryFrequency(row, 'calibration_frequency_external');
    const lastIn = this.lastCalibrationDate(row, 'calibration_frequency_inhouse');
    const lastEx = this.lastCalibrationDate(row, 'calibration_frequency_external');
    return {
      ...row,
      equipment_type: row.equipment_name || row.equipment_type || '-',
      calibration_requirement: row.calibration_required || '-',
      calibration_type: row.calibration_type || '-',
      frequency_internal: freqIn,
      frequency_external: freqEx,
      last_date: lastIn || lastEx || '',
    };
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

  private primaryFrequency(row: any, key: string): string {
    const list = this.parseFrequency(row?.[key]);
    const checked = list.filter((item: any) => item?.checked && !item?.__cal_type_meta);
    if (checked.length) {
      return checked.map((item: any) => String(item.particular || '').trim()).filter(Boolean).join(', ');
    }
    return '-';
  }

  private lastCalibrationDate(row: any, key: string): string {
    const list = this.parseFrequency(row?.[key]);
    const checked = list.find((item: any) => item?.checked && item?.last_cali_date);
    return checked?.last_cali_date || '';
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        const rights = Array.isArray(response) && response.length ? response[0] : null;
        this.isuser = rights?.isuser || 'No';
      });
  }
}
