import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  FPS_FORM_TITLE, FPS_API, FPS_BATCH_TYPES, emptyTestRows, parseFpsResponse
} from '../fps.constants';
declare let alertify: any;

@Component({
  selector: 'app-fps-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  readonly formTitle = FPS_FORM_TITLE;
  readonly batchTypes = FPS_BATCH_TYPES;

  form_no = '';
  products: any[] = [];
  testRows = emptyTestRows();
  saving = false;
  submitting = false;

  form: any = {
    batch_type: 'commercial',
    product_name: '',
    strength: '',
    lot_no: '',
    code_no: '',
    atr_reference: ''
  };

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getNextFormNo();
    this.loadProducts();
  }

  getNextFormNo() {
    this.service.get(FPS_API + 'type=getNextFormNo').subscribe((res: any) => {
      this.form_no = res.form_no || '';
    });
  }

  loadProducts() {
    this.service.get(FPS_API + 'type=getProducts').subscribe((res: any) => {
      this.products = Array.isArray(res) ? res : [];
    });
  }

  onProductSelect(code: string) {
    const p = this.products.find(x => x.product_code === code);
    if (p) {
      this.form.code_no = p.product_code;
      this.form.product_name = p.product_name || '';
      this.form.strength = p.strength || '';
    }
  }

  buildPayload() {
    return { form_no: this.form_no, test_rows: this.testRows, ...this.form };
  }

  saveDraft() {
    this.saving = true;
    this.service.postTextResponse(FPS_API + 'type=savePlan', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.saving = false;
        const res = parseFpsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Draft saved.');
          this.router.navigate(['/qc/finished-product-sampling/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Save failed');
        }
      }, () => { this.saving = false; alertify.error('Save failed'); });
  }

  submit() {
    if (!this.form.product_name || !this.form.lot_no) {
      alertify.error('Product name and lot number are required.');
      return;
    }
    this.submitting = true;
    this.service.postTextResponse(FPS_API + 'type=submitPlan', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.submitting = false;
        const res = parseFpsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Sampling plan submitted to QC Manager.');
          this.router.navigate(['/qc/finished-product-sampling/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Submit failed');
        }
      }, () => { this.submitting = false; alertify.error('Submit failed'); });
  }
}
