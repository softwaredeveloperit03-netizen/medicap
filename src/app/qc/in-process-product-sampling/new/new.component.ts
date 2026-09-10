import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  IPPS_FORM_TITLE, IPPS_API, IPPS_SAMPLE_TYPES, IPPS_PROVIDE_TO_OPTIONS, parseIppsResponse
} from '../ipps.constants';
declare let alertify: any;

@Component({
  selector: 'app-ipps-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  readonly formTitle = IPPS_FORM_TITLE;
  readonly sampleTypes = IPPS_SAMPLE_TYPES;
  readonly provideToOptions = IPPS_PROVIDE_TO_OPTIONS;

  form_no = '';
  products: any[] = [];
  saving = false;
  submitting = false;

  form: any = {
    sample_type: 'in_process',
    product_name: '',
    product_code: '',
    lot_number: '',
    amount_requested: '',
    amount_provided: '',
    reason: '',
    testing_required: 'No',
    provide_samples_to: 'QC Laboratory',
    requested_by: '',
    requested_date: '',
    sampled_by: '',
    sampled_date: ''
  };

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.form.requested_by = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
    this.form.sampled_by = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
    this.form.requested_date = new Date().toISOString().substring(0, 10);
    this.form.sampled_date = new Date().toISOString().substring(0, 10);
    this.getNextFormNo();
    this.loadProducts();
  }

  getNextFormNo() {
    this.service.get(IPPS_API + 'type=getNextFormNo').subscribe((res: any) => {
      this.form_no = res.form_no || '';
    });
  }

  loadProducts() {
    this.service.get(IPPS_API + 'type=getProducts').subscribe((res: any) => {
      this.products = Array.isArray(res) ? res : [];
    });
  }

  onProductSelect(code: string) {
    const p = this.products.find(x => x.product_code === code);
    if (p) {
      this.form.product_code = p.product_code;
      this.form.product_name = p.product_name || '';
    }
  }

  buildPayload() {
    return { form_no: this.form_no, ...this.form };
  }

  saveDraft() {
    this.saving = true;
    this.service.postTextResponse(IPPS_API + 'type=saveRecord', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.saving = false;
        const res = parseIppsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Draft saved.');
          this.router.navigate(['/qc/in-process-product-sampling/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Save failed');
        }
      }, () => { this.saving = false; alertify.error('Save failed'); });
  }

  submit() {
    if (!this.form.product_name || !this.form.lot_number) {
      alertify.error('Product name and lot number are required.');
      return;
    }
    this.submitting = true;
    this.service.postTextResponse(IPPS_API + 'type=submitRecord', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.submitting = false;
        const res = parseIppsResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Sampling request submitted.');
          if (this.form.testing_required === 'Yes') {
            alertify.warning('Complete Analytical Test Request Form FQC-005-06-A if testing is required.');
          }
          this.router.navigate(['/qc/in-process-product-sampling/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Submit failed');
        }
      }, () => { this.submitting = false; alertify.error('Submit failed'); });
  }
}
