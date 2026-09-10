import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  productList: { make: string }[] = [];
  grades: any[] = [];
  grade = '';
  unit = 'g';
  plant_id: any;
  makeInput = '';
  equipmentsType: { vendor_no: string; vendor_name: string }[] = [];
  selectedVendorNo = '';
  selectedEquipment: { vendor_no?: string; vendor_name?: string } = {};
  eqData: { vendor_no: string; vendor_name: string }[] = [];
  loadingManufacturers = false;
  saving = false;

  readonly unitOptions = ['g', 'kg', 'mg', 'ml', 'L'];
  readonly fallbackGrades = ['Commecial', 'AR', 'Pharma'];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loadManufacturers();
    this.loadGrades();
  }

  loadGrades(): void {
    this.service.get('common.php?type=getGrades').subscribe({
      next: (response: any) => {
        this.grades = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.grades = [];
      },
    });
    this.service.observableGrade.subscribe((response) => {
      if (Array.isArray(response) && response.length) {
        this.grades = response;
      }
    });
  }

  gradeOptions(): string[] {
    const fromApi = (this.grades || [])
      .map((g) => String(g?.grade ?? g?.Grade ?? '').trim())
      .filter((g) => g !== '');
    if (fromApi.length) {
      return fromApi;
    }
    if (String(this.plant_id) === '59') {
      return ['AR', 'LR', 'GR', 'Pharma', 'Other'];
    }
    return this.fallbackGrades;
  }

  private mapVendorManufacturers(rows: any[]): { vendor_no: string; vendor_name: string }[] {
    const blocked = new Set(['blacklist', 'temparory block', 'temporary block', 'in active']);
    const seen = new Set<string>();
    const out: { vendor_no: string; vendor_name: string }[] = [];
    for (const row of rows || []) {
      const name = String(row?.vendor_name ?? row?.mfg_name ?? '').trim();
      if (!name) {
        continue;
      }
      const status = String(row?.status ?? '').trim().toLowerCase();
      if (blocked.has(status)) {
        continue;
      }
      const no = String(row?.vendor_no ?? '').trim();
      const key = (no || name).toLowerCase();
      if (seen.has(key)) {
        continue;
      }
      seen.add(key);
      out.push({ vendor_no: no || name, vendor_name: name });
    }
    return out.sort((a, b) => a.vendor_name.localeCompare(b.vendor_name));
  }

  loadManufacturers(): void {
    this.loadingManufacturers = true;
    this.service.getJsonArray('purchase/vendor.php?type=getVendorLog').subscribe({
      next: (rows) => {
        this.equipmentsType = this.mapVendorManufacturers(rows);
        this.loadingManufacturers = false;
        if (this.equipmentsType.length === 0) {
          this.loadManufacturersFallback();
        }
      },
      error: () => this.loadManufacturersFallback(),
    });
  }

  private loadManufacturersFallback(): void {
    this.service.getJsonArray('common.php?type=getChemicalManufacturer').subscribe({
      next: (rows) => {
        this.equipmentsType = this.mapVendorManufacturers(rows);
        this.loadingManufacturers = false;
      },
      error: () => {
        this.loadingManufacturers = false;
        this.equipmentsType = [];
      },
    });
  }

  onManufacturerChange(vendorNo: string): void {
    const match = this.equipmentsType.find(
      (item) => String(item.vendor_no) === String(vendorNo)
    );
    this.selectedEquipment = match || {};
  }

  addEquipment(): void {
    const vendorNo = this.selectedEquipment?.vendor_no;
    const vendorName = this.selectedEquipment?.vendor_name;
    if (!vendorNo && !vendorName) {
      alertify.error('Select a manufacturer first.');
      return;
    }
    const duplicate = this.eqData.some(
      (row) => String(row.vendor_no) === String(vendorNo)
    );
    if (duplicate) {
      alertify.error('Manufacturer already added.');
      return;
    }
    this.eqData.push({
      vendor_no: String(vendorNo || ''),
      vendor_name: String(vendorName || ''),
    });
    this.selectedVendorNo = '';
    this.selectedEquipment = {};
  }

  removeManufacturer(index: number): void {
    this.eqData.splice(index, 1);
  }

  addMake(): void {
    const make = (this.makeInput || '').trim();
    if (!make) {
      alertify.error('Add at least one make.');
      return;
    }
    this.productList.push({ make });
    this.makeInput = '';
  }

  delData(index: number): void {
    this.productList.splice(index, 1);
  }

  private buildPayload(formValue: any): Record<string, unknown> {
    return {
      chemical_name: String(formValue?.chemical_name || '').trim(),
      grade: String(this.grade || formValue?.grade || '').trim(),
      molecular_wt: formValue?.molecular_wt ?? '',
      cas_name: formValue?.cas_name ?? '',
      unit: this.unit || 'g',
      chem_type: 'Chemical',
      make: this.productList,
      manufacturer: this.eqData,
      pack_size: '[]',
    };
  }

  save(form: any): void {
    if (!form?.valid) {
      alertify.error('All required fields must be filled.');
      return;
    }
    if (!String(this.grade || '').trim()) {
      alertify.error('Please select grade.');
      return;
    }
    if (this.productList.length === 0) {
      alertify.error('Add at least one make before saving.');
      return;
    }

    const payload = this.buildPayload(form.value);
    if (!payload.chemical_name) {
      alertify.error('Chemical name is required.');
      return;
    }

    this.saving = true;
    this.service
      .postTextResponse('qc/chemical.php?type=saveMasterChemical', JSON.stringify(payload))
      .subscribe({
        next: (raw: string) => {
          this.saving = false;
          let response: any;
          try {
            response = this.service.parsePhpJson(raw);
          } catch {
            alertify.error('Server returned an invalid response. Deploy backend PHP (qc/chemical.php) if not done yet.');
            return;
          }
          if (response?.status === 'success') {
            alertify.success(response?.message || 'Record inserted successfully.');
            form.resetForm();
            this.productList = [];
            this.eqData = [];
            this.makeInput = '';
            this.grade = '';
            this.unit = 'g';
            this.router.navigate(['/master/chemical']);
          } else {
            alertify.error(response?.message || response?.status || 'Failed to save chemical.');
          }
        },
        error: () => {
          this.saving = false;
          alertify.error('Failed to save chemical. Check your connection.');
        },
      });
  }
}
