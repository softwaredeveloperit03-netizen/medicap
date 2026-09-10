import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  ATR_SAMPLE_CATEGORIES, ATR_PRODUCTION_CATEGORIES, ATR_TEST_OPTIONS, emptyTestsRequested
} from '../atr.constants';
declare let alertify: any;

@Component({
  selector: 'app-atr-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  readonly sampleCategories = ATR_SAMPLE_CATEGORIES;
  readonly testOptions = ATR_TEST_OPTIONS;

  request_no = '';
  products: any[] = [];
  materials: any[] = [];
  selectedProductCode = '';
  selectedMaterialCode = '';

  form: any = {
    product_description: '',
    product_code: '',
    lot_number: '',
    mfg_date: '',
    sample_type: '',
    sampling_site: '',
    num_of_samples: '',
    sample_categories: [] as string[],
    sample_category_other: '',
    tests_requested: emptyTestsRequested(),
    processing_start_date: '',
    holding_time: '',
    comments: '',
    requestor: '',
    requestor_date: '',
    date_results_required: ''
  };

  saving = false;
  submitting = false;

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    const today = new Date().toISOString().substring(0, 10);
    this.form.requestor_date = today;
    this.form.requestor = localStorage.getItem('emp_name') || localStorage.getItem('emp_id') || '';
    this.getNextRequestNo();
    this.loadProducts();
    this.loadMaterials();
  }

  getNextRequestNo() {
    this.service.get('qc/analytical_test_request.php?type=getNextRequestNo').subscribe((res: any) => {
      this.request_no = res.request_no || '';
    });
  }

  loadProducts() {
    this.service.get('qc/analytical_test_request.php?type=getProducts').subscribe((res: any) => {
      this.products = Array.isArray(res) ? res : [];
    });
  }

  loadMaterials() {
    this.service.get('qc/analytical_test_request.php?type=getMaterials').subscribe((res: any) => {
      this.materials = Array.isArray(res) ? res : [];
    });
  }

  onProductSelect(code: string) {
    if (!code) return;
    const p = this.products.find(x => x.product_code === code);
    if (p) {
      this.form.product_code = p.product_code;
      let desc = p.product_name || '';
      if (p.strength) desc += ', ' + p.strength;
      if (p.dosage_form) desc += ', ' + p.dosage_form;
      this.form.product_description = desc;
    }
  }

  onMaterialSelect(code: string) {
    if (!code) return;
    const m = this.materials.find(x => x.material_code === code);
    if (m) {
      this.form.product_code = m.material_code;
      this.form.product_description = (m.material_name || '') + (m.grade ? ' - ' + m.grade : '');
    }
  }

  toggleCategory(cat: string) {
    const idx = this.form.sample_categories.indexOf(cat);
    if (idx >= 0) {
      this.form.sample_categories.splice(idx, 1);
    } else {
      this.form.sample_categories.push(cat);
    }
  }

  isCategoryChecked(cat: string): boolean {
    return this.form.sample_categories.indexOf(cat) >= 0;
  }

  isProductionSample(): boolean {
    return this.form.sample_categories.some((c: string) => ATR_PRODUCTION_CATEGORIES.includes(c));
  }

  onProcessingStartChange() {
    if (this.form.processing_start_date && !this.form.holding_time) {
      this.form.holding_time = '30 days';
    }
  }

  validate(): boolean {
    if (!this.form.product_description) {
      alertify.error('Product Description is required.');
      return false;
    }
    if (!this.form.lot_number) {
      alertify.error('Lot # is required.');
      return false;
    }
    if (this.form.sample_categories.length === 0) {
      alertify.error('Select at least one Sample Category.');
      return false;
    }
    if (this.form.sample_categories.includes('Other') && !this.form.sample_category_other) {
      alertify.error('Specify Other sample category.');
      return false;
    }
    if (!this.form.sample_type) {
      alertify.error('Type of Samples is required.');
      return false;
    }
    if (!this.form.sampling_site) {
      alertify.error('Sampling Site is required.');
      return false;
    }
    if (!this.form.num_of_samples) {
      alertify.error('# of Samples is required.');
      return false;
    }
    if (!this.form.requestor) {
      alertify.error('Requestor is required.');
      return false;
    }
    return true;
  }

  buildPayload(status: 'draft' | 'submit') {
    return {
      request_no: this.request_no,
      ...this.form,
      requestor_emp_id: localStorage.getItem('emp_id'),
      entry_by: localStorage.getItem('emp_id'),
      holding_time: this.form.holding_time || (this.form.processing_start_date ? '30 days' : '')
    };
  }

  saveDraft() {
    if (!this.validate()) return;
    this.saving = true;
    this.service.postJson('qc/analytical_test_request.php?type=saveRequest', JSON.stringify(this.buildPayload('draft')))
      .subscribe((res: any) => {
        this.saving = false;
        if (res && res.status === 'success') {
          alertify.success('Draft saved.');
          if (res.id) this.form.id = res.id;
        } else {
          alertify.error(res?.message || res?.status || 'Save failed');
        }
      }, () => { this.saving = false; alertify.error('Save failed'); });
  }

  submit() {
    if (!this.validate()) return;
    this.submitting = true;
    const saveThenSubmit = (id: number) => {
      this.service.postJson('qc/analytical_test_request.php?type=submitRequest', JSON.stringify({ ...this.buildPayload('submit'), id }))
        .subscribe((res: any) => {
          this.submitting = false;
          if (res && res.status === 'success') {
            alertify.success('Analytical Test Request submitted.');
            this.router.navigate(['/qc/analytical-test-request/log']);
          } else {
            alertify.error(res?.message || res?.status || 'Submit failed');
          }
        }, () => { this.submitting = false; alertify.error('Submit failed'); });
    };

    if (this.form.id) {
      saveThenSubmit(this.form.id);
    } else {
      this.service.postJson('qc/analytical_test_request.php?type=saveRequest', JSON.stringify(this.buildPayload('draft')))
        .subscribe((res: any) => {
          if (res && res.status === 'success' && res.id) {
            this.form.id = res.id;
            saveThenSubmit(res.id);
          } else {
            this.submitting = false;
            alertify.error('Could not save request before submit.');
          }
        }, () => { this.submitting = false; alertify.error('Save failed'); });
    }
  }
}
