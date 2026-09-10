import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  data: any[] = [];
  emps: any[] = [];

  barcodeValue = '';
  selectedEquipmentCode = '';
  selectedEquipment: any = null;

  tempmon: string;
  date: string;
  timeinhr: string;
  temp: string;
  humidity: string;
  done_by: string;
  donebyDate: string;
  remark: string;

  isNew = false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getEmployeeQualityControl();
    this.getDetails();
  }

  getDetails() {
    this.service
      .get('common.php?type=getSECTIONS&department1=' + localStorage.getItem('department'))
      .subscribe((response: any) => {
        this.data = Array.isArray(response) ? response : [];
      });
  }

  /** Match section_code as string (dropdown/API often mix string/number). */
  private findSection(code: string): any {
    const c = String(code ?? '').trim();
    if (!c || !this.data?.length) {
      return null;
    }
    return this.data.find((d) => String(d.section_code) === c) || null;
  }

  /** Use this before save: selection, scan, or code only. */
  private resolveEquipment(): any {
    if (this.selectedEquipment) {
      return this.selectedEquipment;
    }
    const code = String(this.selectedEquipmentCode || '').trim();
    return code ? this.findSection(code) : null;
  }

  onSelect() {
    this.selectedEquipment = this.findSection(this.selectedEquipmentCode);
    console.log('Selected manually:', this.selectedEquipment);
  }

  onScanBarcode(code: string) {
    const c = String(code ?? '').trim();
    const matched = this.findSection(c);
    if (matched) {
      this.selectedEquipmentCode = String(matched.section_code);
      this.selectedEquipment = matched;
      console.log('Selected via scan:', this.selectedEquipment);
    } else {
      this.selectedEquipment = null;
      alert('Room not found!');
    }
  }

  getEmployeeQualityControl() {
    this.service
      .get('common.php?type=get_Eqgetemployee_byDeptipments&depart=' + localStorage.getItem('department'))
      .subscribe((response: any) => {
        this.emps = Array.isArray(response) ? response : [];
      });
  }

  addtemprec(form: any) {
    if (!form?.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const equip = this.resolveEquipment();
    if (!equip) {
      alertify.error('Please select Area / Room or scan a valid barcode');
      return;
    }

    const payload = { ...form.value };
    payload['section'] = `${equip.section_name}-${equip.section_code}`;
    payload['department'] = localStorage.getItem('department');

    console.log(payload);

    this.service.post('equipments.php?type=savetemperhumrec', JSON.stringify(payload)).subscribe({
      next: (response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Record Save Successfully');
          this.isNew = false;
          form.resetForm?.();
          this.selectedEquipment = null;
          this.selectedEquipmentCode = '';
          this.barcodeValue = '';
        } else {
          alertify.error(response['status'] || 'Save failed');
        }
      },
      error: () => alertify.error('Network error'),
    });
  }
}