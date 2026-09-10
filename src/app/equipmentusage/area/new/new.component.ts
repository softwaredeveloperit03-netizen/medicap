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

  selectedEquipmentCode = '';
  selectedEquipment: any = null;

  barcodeValue = '';
  tempmon: string;
  date: string;
  timeinhr: string;
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

  /** Resolve current area from selection or scan */
  private resolveEquipment(): any {
    if (this.selectedEquipment) {
      return this.selectedEquipment;
    }
    const code = (this.selectedEquipmentCode || this.barcodeValue || '').trim();
    if (!code || !this.data?.length) {
      return null;
    }
    return this.data.find((d) => String(d.section_code) === String(code)) || null;
  }

  onSelect() {
    const eq = this.resolveEquipment();
    this.selectedEquipment = eq;
    if (eq) {
      this.selectedEquipmentCode = String(eq.section_code);
    }
    console.log('Selected manually:', this.selectedEquipment);
  }

  onScanBarcode(code: string) {
    const c = (code || '').trim();
    if (!c) {
      return;
    }
    const matched = this.data?.find((d) => String(d.section_code) === String(c));
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

  addtemprec(data: any) {
    if (!data?.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const equip = this.resolveEquipment();
    // if (!equip) {
    //   alertify.error('Please select Area / Room from the list or scan a valid barcode');
    //   return;
    // }

    const temp = { ...data.value };
    temp['section'] = `${equip.section_name}-${equip.section_code}`;
    temp['department'] = localStorage.getItem('department');

    console.log(temp);

    this.service.post('equipments.php?type=save_area_ceaningRecord', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Record Save Successfully');
          this.isNew = false;
          data.resetForm?.();
          this.selectedEquipment = null;
          this.selectedEquipmentCode = '';
          this.barcodeValue = '';
        } else {
          alertify.error(response['status'] || 'Save failed');
        }
      },
      error: () => {
        alertify.error('Network error');
      },
    });
  }
}