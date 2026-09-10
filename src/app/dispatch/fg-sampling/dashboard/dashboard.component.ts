import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-fg-sampling-dashboard',
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  loading = false;
  showChecklistModal = false;
  products: any[] = [];
  checklistMaster: any[] = [];
  logs: any[] = [];

  formData: any = {};

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.resetForm();
    this.getProducts();
    this.getChecklistMaster();
    this.getSamplingLogs();
  }

  resetForm(): void {
    this.formData = {
      samplingfg_id: '',
      product_code: '',
      product_name: '',
      batch_no: '',
      sampling_qty: '',
      uom: '',
      reason_for_sampling: '',
      checklist: [],
      sampling_remark: '',
      observation: '',
    };
  }

  getProducts(): void {
    this.service
      .get('dispatch/fg_sampling.php?type=getProducts')
      .subscribe((response: any) => {
        this.products = Array.isArray(response) ? response : [];
      });
  }

  getChecklistMaster(): void {
    this.service
      .get('dispatch/fg_sampling.php?type=getChecklistMaster')
      .subscribe((response: any) => {
        this.checklistMaster = Array.isArray(response) ? response : [];
        this.formData.checklist = this.checklistMaster.map((item: any) => ({
          id: item.id,
          checklist_point: item.checklist_point,
          checked: false,
        }));
      });
  }

  getSamplingLogs(): void {
    this.loading = true;
    this.service
      .get('dispatch/fg_sampling.php?type=getSamplingLog')
      .subscribe(
        (response: any) => {
          this.logs = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => {
          this.loading = false;
        }
      );
  }

  onProductChange(): void {
    const selected = this.products.find(
      (p: any) => String(p.samplingfg_id) === String(this.formData.samplingfg_id)
    );
    if (selected) {
      this.formData.product_code = selected.product_code || '';
      this.formData.product_name = selected.product_name || '';
      this.formData.batch_no = selected.batch_no || '';
      this.formData.uom = selected.unit || this.formData.uom || 'Nos';
      if (!this.formData.sampling_qty) {
        this.formData.sampling_qty = selected.batch_qty || '';
      }
    } else {
      this.formData.product_code = '';
      this.formData.product_name = '';
    }
  }

  checkedChecklistCount(): number {
    if (!Array.isArray(this.formData.checklist)) {
      return 0;
    }
    return this.formData.checklist.filter((x: any) => x.checked).length;
  }

  saveSampling(form: any): void {
    if (!form.valid) {
      alertify.error('All required fields are mandatory');
      return;
    }

    const payload = {
      samplingfg_id: this.formData.samplingfg_id,
      product_code: this.formData.product_code,
      batch_no: this.formData.batch_no,
      sampling_qty: this.formData.sampling_qty,
      uom: this.formData.uom,
      reason_for_sampling: this.formData.reason_for_sampling,
      checklist: this.formData.checklist,
      sampling_remark: this.formData.sampling_remark,
      observation: this.formData.observation,
    };

    this.service
      .post('dispatch/fg_sampling.php?type=saveSampling', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response && response.status === 'success') {
          alertify.success('Sample submitted successfully');
          this.resetForm();
          this.getChecklistMaster();
          this.getSamplingLogs();
        } else {
          alertify.error('Failed to submit FG sampling record');
        }
      });
  }
}
