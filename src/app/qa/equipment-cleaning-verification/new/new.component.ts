import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { allowDurationNumber, ecvHomePath, ecvHubPath, isSwabSampleType } from '../ecv.utils';

declare let alertify: any;

interface SampleRow {
  sample_info: string;
}

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent implements OnInit {
  formNo = 'FQA-002-01-A';
  revisionNo = '00';
  effectiveDate = '2025-04-01';
  hubPath = ecvHubPath();
  homePath = ecvHomePath();
  equipments: any[] = [];
  products: any[] = [];
  selectedEquipment: any = null;
  selectedProduct: any = null;
  selectedPreviousProduct: any = null;
  equipmentId = '';

  sampleType = '';
  sampleTypeSpecify = '';
  volume30ml = false;
  volume50ml = false;
  cleaningDate = '';
  expiryDate = '';

  sampleRows: SampleRow[] = Array.from({ length: 6 }, () => ({ sample_info: '' }));

  waterTemp = '';
  durationFlush: number | null = null;
  sampleTakenTime = '';
  sampleTakenDate = '';
  sampledByDate = '';
  checkedByDate = '';
  comments = '';

  previousProductName = '';
  productCode = '';
  qcLabSampleNo = '';
  lotNo = '';
  saving = false;
  submitted = false;
  fieldErrors: { [key: string]: string } = {};

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    const now = new Date();
    this.cleaningDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
    this.sampleTakenDate = this.datePipe.transform(now, 'yyyy-MM-dd') || '';
    this.sampleTakenTime = this.datePipe.transform(now, 'HH:mm') || '';
    this.updateExpiryDate();
  }

  ngOnInit(): void {
    this.getEquipments();
    this.getProducts();
    this.loadAutoStamp();
  }

  get isSwabSample(): boolean {
    return isSwabSampleType(this.sampleType);
  }

  loadAutoStamp(): void {
    this.service.get('qa/equipmentCleaningVerification.php?type=getEquipmentCleaningStamp').subscribe(
      (response: any) => {
        this.sampledByDate = response?.stamp || this.fallbackStamp();
        this.checkedByDate = this.sampledByDate;
        if (response?.date) {
          this.sampleTakenDate = response.date;
        }
        if (response?.time) {
          this.sampleTakenTime = response.time;
        }
      },
      () => {
        this.sampledByDate = this.fallbackStamp();
        this.checkedByDate = this.sampledByDate;
      }
    );
  }

  fallbackStamp(): string {
    const name =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    const empId = localStorage.getItem('emp_id') || '';
    const when = this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm') || '';
    return name ? name + (empId ? ' (' + empId + ')' : '') + ' - ' + when : when;
  }

  private normalizeProducts(list: any[]): any[] {
    return (Array.isArray(list) ? list : []).map((p) => ({
      ...p,
      display_name: p.product_name || p.material_name || p.product_code || p.material_code || 'Product',
    }));
  }

  getEquipments(): void {
    this.service.get('master/equipment.php?type=getEquipments').subscribe(
      (response: any) => {
        this.equipments = Array.isArray(response) ? response : [];
        if (!this.equipments.length) {
          this.loadEquipmentsFallback();
        }
      },
      () => this.loadEquipmentsFallback()
    );
  }

  loadEquipmentsFallback(): void {
    this.service.get('common.php?type=getEquipments').subscribe((response: any) => {
      this.equipments = Array.isArray(response) ? response : [];
    });
  }

  getProducts(): void {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(
      (response: any) => {
        this.products = this.normalizeProducts(response);
      },
      () => {
        this.service.get('common.php?type=getProducts').subscribe((res: any) => {
          this.products = this.normalizeProducts(res);
        });
      }
    );
  }

  onEquipmentChange(): void {
    if (this.submitted) {
      this.validateForm();
    }
    if (!this.selectedEquipment) {
      this.equipmentId = '';
      return;
    }
    this.equipmentId =
      this.selectedEquipment.equipment_sr_no ||
      this.selectedEquipment.equipment_code ||
      this.selectedEquipment.id ||
      '';
  }

  onPreviousProductChange(product: any): void {
    this.selectedPreviousProduct = product;
    this.previousProductName = product
      ? product.product_name || product.material_name || product.display_name || ''
      : '';
    if (this.submitted) {
      this.validateForm();
    }
  }

  onProductChange(product: any): void {
    this.selectedProduct = product;
    if (!product) {
      if (this.submitted) {
        this.validateForm();
      }
      return;
    }
    this.productCode = product.product_code || product.material_code || '';
    if (!this.selectedPreviousProduct) {
      this.selectedPreviousProduct = product;
      this.previousProductName = product.product_name || product.material_name || product.display_name || '';
    }
    if (this.submitted) {
      this.validateForm();
    }
  }

  onSampleTypeChange(): void {
    if (this.isSwabSample) {
      this.durationFlush = null;
      this.volume30ml = false;
      this.volume50ml = false;
    }
    if (this.submitted) {
      this.validateForm();
    }
  }

  onDurationKeydown(event: KeyboardEvent): boolean {
    const allowed = allowDurationNumber(event);
    if (!allowed) {
      event.preventDefault();
    }
    return allowed;
  }

  updateExpiryDate(): void {
    if (!this.cleaningDate) {
      this.expiryDate = '';
      return;
    }
    const date = new Date(this.cleaningDate);
    date.setDate(date.getDate() + 30);
    this.expiryDate = this.datePipe.transform(date, 'yyyy-MM-dd') || '';
    if (this.submitted) {
      this.validateForm();
    }
  }

  hasSampleInfo(): boolean {
    return this.sampleRows.some((row) => String(row?.sample_info || '').trim() !== '');
  }

  validateForm(): boolean {
    const errors: { [key: string]: string } = {};

    if (!this.selectedEquipment) {
      errors.equipment = 'Equipment Name is required';
    }
    if (!this.selectedPreviousProduct && !String(this.previousProductName || '').trim()) {
      errors.previousProduct = 'Previous Product Name is required';
    }
    if (!this.cleaningDate) {
      errors.cleaningDate = 'Date of Cleaning is required';
    }
    if (!String(this.productCode || '').trim()) {
      errors.productCode = 'Product Code is required';
    }
    if (!String(this.qcLabSampleNo || '').trim()) {
      errors.qcLabSampleNo = 'QC Lab Sample # is required';
    }
    if (!String(this.lotNo || '').trim()) {
      errors.lotNo = 'Lot is required';
    }
    if (!this.sampleType) {
      errors.sampleType = 'Sample Type is required';
    }
    if (!this.hasSampleInfo()) {
      errors.sampleInfo = 'Enter at least one Sample Information detail';
    }

    if (!this.isSwabSample) {
      if (!String(this.waterTemp || '').trim()) {
        errors.waterTemp = 'Water Temp is required for rinse samples';
      }
      if (this.durationFlush === null || this.durationFlush === undefined || String(this.durationFlush) === '') {
        errors.durationFlush = 'Duration of Flush is required';
      } else if (isNaN(Number(this.durationFlush)) || Number(this.durationFlush) < 0) {
        errors.durationFlush = 'Duration of Flush must be a valid number';
      }
      if (!this.volume30ml && !this.volume50ml) {
        errors.volume = 'Select Volume of Final Rinse (30 ml / 50 ml)';
      }
    }

    this.fieldErrors = errors;
    return Object.keys(errors).length === 0;
  }

  save(form: NgForm): void {
    this.submitted = true;
    if (!this.validateForm() || form.invalid) {
      alertify.error(Object.values(this.fieldErrors)[0] || 'Please fill all required fields');
      return;
    }

    this.saving = true;
    const payload = {
      form_no: this.formNo,
      equipment_name: this.selectedEquipment.equipment_name,
      equipment_id: this.equipmentId,
      equipment_code: this.selectedEquipment.equipment_code,
      previous_product_name: this.previousProductName,
      cleaning_date: this.cleaningDate,
      product_code: this.productCode,
      qc_lab_sample_no: this.qcLabSampleNo,
      lot_no: this.lotNo,
      sample_type: this.sampleType,
      sample_type_specify: this.sampleTypeSpecify,
      sample_rows: this.sampleRows,
      water_temp: this.waterTemp,
      duration_flush: this.isSwabSample ? '' : String(this.durationFlush),
      volume_30ml: this.volume30ml ? 'Yes' : 'No',
      volume_50ml: this.volume50ml ? 'Yes' : 'No',
      sample_taken_time: this.sampleTakenTime,
      sample_taken_date: this.sampleTakenDate,
      sampled_by_date: this.sampledByDate,
      checked_by_date: this.checkedByDate,
      expiry_date: this.expiryDate,
      comments: this.comments,
    };

    this.service
      .post('qa/equipmentCleaningVerification.php?type=saveEquipmentCleaningVerification', JSON.stringify(payload))
      .subscribe(
        (response: any) => {
          this.saving = false;
          if (response?.status === 'success') {
            alertify.success('Form saved successfully');
            this.router.navigate([this.hubPath + '/log']);
          } else {
            alertify.error(response?.status || 'Failed to save form');
          }
        },
        () => {
          this.saving = false;
          alertify.error('Failed to save form');
        }
      );
  }
}
