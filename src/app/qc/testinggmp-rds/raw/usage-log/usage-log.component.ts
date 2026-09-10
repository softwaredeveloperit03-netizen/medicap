import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-usage-log',
  templateUrl: './usage-log.component.html',
  styleUrls: ['./usage-log.component.css']
})
export class UsageLogComponent implements OnInit {
  private readonly EQUIPMENT_USAGE_LOG_KEY = 'qc_raw_equipment_usage_log';
  private readonly CHEMICAL_USAGE_LOG_KEY = 'qc_raw_chemical_usage_log';

  equipmentLogs: any[] = [];
  chemicalLogs: any[] = [];
  searchTestingNo = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLogs();
  }

  downloadUsageLog(): void {
    const form = document.createElement('form');
    form.method = 'POST';
    form.target = '_blank';
    form.action = this.service.url + 'qc/testing/raw.php?type=downloadUsageLog&token=' +
      localStorage.getItem('token') + '&plant_id=' + localStorage.getItem('plant_id');
    const add = (name: string, value: string) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    };
    add('equipment', JSON.stringify(this.filteredEquipmentLogs || []));
    add('chemical', JSON.stringify(this.filteredChemicalLogs || []));
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  loadLogs(): void {
    this.equipmentLogs = this.readStorageArray(this.EQUIPMENT_USAGE_LOG_KEY);
    this.chemicalLogs = this.readStorageArray(this.CHEMICAL_USAGE_LOG_KEY);
    if (!this.equipmentLogs.length && !this.chemicalLogs.length) {
      this.seedStaticLogs();
      this.equipmentLogs = this.readStorageArray(this.EQUIPMENT_USAGE_LOG_KEY);
      this.chemicalLogs = this.readStorageArray(this.CHEMICAL_USAGE_LOG_KEY);
    }
  }

  get filteredEquipmentLogs(): any[] {
    const q = (this.searchTestingNo || '').trim().toLowerCase();
    if (!q) return this.equipmentLogs;
    return this.equipmentLogs.filter((x) => String(x.testing_no || '').toLowerCase().includes(q));
  }

  get filteredChemicalLogs(): any[] {
    const q = (this.searchTestingNo || '').trim().toLowerCase();
    if (!q) return this.chemicalLogs;
    return this.chemicalLogs.filter((x) => String(x.testing_no || '').toLowerCase().includes(q));
  }

  clearLogs(): void {
    localStorage.removeItem(this.EQUIPMENT_USAGE_LOG_KEY);
    localStorage.removeItem(this.CHEMICAL_USAGE_LOG_KEY);
    this.loadLogs();
  }

  private readStorageArray(key: string): any[] {
    try {
      const raw = localStorage.getItem(key);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }

  private seedStaticLogs(): void {
    const equipmentSeed = [
      {
        testing_no: 'SBT-006',
        test_id: '101',
        test_name: 'Assay',
        equipment_name: 'UV-Visible Spectrophotometer',
        equipment_code: 'EQ-UV-102',
        start_time: '09:10',
        end_time: '09:35',
        entry_by: 'APPL002',
        entry_on: '2026-03-25T09:40:00'
      },
      {
        testing_no: 'SBT-006',
        test_id: '101',
        test_name: 'Assay',
        equipment_name: 'Analytical Balance',
        equipment_code: 'EQ-BAL-014',
        start_time: '09:00',
        end_time: '09:08',
        entry_by: 'APPL002',
        entry_on: '2026-03-25T09:40:00'
      }
    ];
    const chemicalSeed = [
      {
        testing_no: 'SBT-006',
        test_id: '101',
        test_name: 'Assay',
        chemical_name: 'Methanol (HPLC Grade)',
        chemical_code: 'CH-MTH-201',
        batch_no: 'MTH240410B',
        qty_used: '50 ml',
        entry_by: 'APPL002',
        entry_on: '2026-03-25T09:40:00'
      },
      {
        testing_no: 'SBT-006',
        test_id: '101',
        test_name: 'Assay',
        chemical_name: 'Acetonitrile',
        chemical_code: 'CH-ACN-188',
        batch_no: 'ACN240415C',
        qty_used: '30 ml',
        entry_by: 'APPL002',
        entry_on: '2026-03-25T09:40:00'
      }
    ];
    localStorage.setItem(this.EQUIPMENT_USAGE_LOG_KEY, JSON.stringify(equipmentSeed));
    localStorage.setItem(this.CHEMICAL_USAGE_LOG_KEY, JSON.stringify(chemicalSeed));
  }
}

