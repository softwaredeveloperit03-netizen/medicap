import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

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
  durationFlush = '';
  sampleTakenTime = '';
  sampleTakenDate = '';
  sampledByDate = '';
  comments = '';

  previousProductName = '';
  productCode = '';
  qcLabSampleNo = '';
  lotNo = '';

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.cleaningDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.updateExpiryDate();
  }

  ngOnInit(): void {
    this.getEquipments();
    this.getProducts();
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
        this.products = Array.isArray(response) ? response : [];
      },
      () => {
        this.service.get('common.php?type=getProducts').subscribe((res: any) => {
          this.products = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  onEquipmentChange(): void {
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
      ? product.product_name || product.material_name || ''
      : '';
  }

  onProductChange(product: any): void {
    this.selectedProduct = product;
    if (!product) {
      return;
    }
    this.productCode = product.product_code || product.material_code || '';
    if (!this.selectedPreviousProduct) {
      this.selectedPreviousProduct = product;
      this.previousProductName = product.product_name || product.material_name || '';
    }
  }

  updateExpiryDate(): void {
    if (!this.cleaningDate) {
      this.expiryDate = '';
      return;
    }
    const date = new Date(this.cleaningDate);
    date.setDate(date.getDate() + 30);
    this.expiryDate = this.datePipe.transform(date, 'yyyy-MM-dd') || '';
  }

  save(form: NgForm): void {
    if (form.invalid || !this.selectedEquipment || !this.sampleType) {
      alertify.error('Please fill all required fields');
      return;
    }

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
      duration_flush: this.durationFlush,
      volume_30ml: this.volume30ml ? 'Yes' : 'No',
      volume_50ml: this.volume50ml ? 'Yes' : 'No',
      sample_taken_time: this.sampleTakenTime,
      sample_taken_date: this.sampleTakenDate,
      sampled_by_date: this.sampledByDate,
      checked_by_date: '',
      expiry_date: this.expiryDate,
      comments: this.comments,
    };

    this.service
      .post('qa/equipmentCleaningVerification.php?type=saveEquipmentCleaningVerification', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Form saved successfully');
          this.router.navigate(['/qa/equipment-cleaning-verification/log']);
        } else {
          alertify.error(response?.status || 'Failed to save form');
        }
      });
  }
}
