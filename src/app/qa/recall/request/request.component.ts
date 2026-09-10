import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css'],
})
export class RequestComponent implements OnInit {
  products = [];
  selectedProduct;

  isNew = false;
  isView = false;

  receivedQuantityFile: File;
  examination_report: File;
  destruction_report: File;

  reactiveForm: FormGroup;
  constructor(
    private service: DataAccessService,
    private router: Router,
    private fb: FormBuilder
  ) {
    this.loggedInDept = localStorage.getItem('department');
    this.reactiveForm = this.fb.group({
      product_name: ['', [Validators.required]],
      batch_no: ['', [Validators.required]],
      mfg_date: ['', [Validators.required]],
      exp_date: ['', [Validators.required]],
      coordinator: ['', [Validators.required]],
      company_person: ['', [Validators.required]],
      clients_person: ['', [Validators.required]],
      email: ['', [Validators.required]],
      mobile: ['', [Validators.required]],
      mock_recall: ['', [Validators.required]],
      public_recall: ['', [Validators.required]],
      professional_recall: ['', [Validators.required]],
      enforcement_auth_recall: ['', [Validators.required]],
      television: ['', [Validators.required]],
      radio: ['', [Validators.required]],
      newspaper: ['', [Validators.required]],
      cfagents: ['', [Validators.required]],
      stockiest: ['', [Validators.required]],
      distributors: ['', [Validators.required]],
      mfg_error: ['', [Validators.required]],
      packing_error: ['', [Validators.required]],
      side_effect: ['', [Validators.required]],
      dosage_error: ['', [Validators.required]],
      degradation: ['', [Validators.required]],
      abnormal_stability: ['', [Validators.required]],
      contamination_product: ['', [Validators.required]],
      release_change: ['', [Validators.required]],
      packing_defect: ['', [Validators.required]],
      overprint_error_price: ['', [Validators.required]],
      overprint_error_batch: ['', [Validators.required]],
      overprint_error_exp: ['', [Validators.required]],
      other_reason: ['', [Validators.required]],
      product_receive: ['', [Validators.required]],
      capa: ['', [Validators.required]],
      training: ['', [Validators.required]],
    });
  }

  ngOnInit() {
    this.getProductRecall();
    this.get_rights();
  }

  getProductRecall() {
    this.service
      .get('qa/recall.php?type=getProductRecall')
      .subscribe((response) => {
        this.products = JSON.parse(JSON.stringify(response));
      });
  }

  onFileChange($event, type) {
    if (type === 'received_quantity') {
      this.receivedQuantityFile = $event.target.files[0];
    } else if (type === 'examination_report') {
      this.examination_report = $event.target.files[0];
    } else if (type === 'destruction_report') {
      this.destruction_report = $event.target.files[0];
    }
  }

  saveProductRecall() {
    const formData = new FormData();
    formData.append('product_name', this.reactiveForm.value.product_name);
    formData.append('batch_no', this.reactiveForm.value.batch_no);
    formData.append('mfg_date', this.reactiveForm.value.mfg_date);
    formData.append('exp_date', this.reactiveForm.value.exp_date);
    formData.append('coordinator', this.reactiveForm.value.coordinator);
    formData.append('company_person', this.reactiveForm.value.company_person);
    formData.append('clients_person', this.reactiveForm.value.clients_person);
    formData.append('office_address', this.reactiveForm.value.office_address);
    formData.append('office_phone', this.reactiveForm.value.office_phone);
    formData.append(
      'residence_address',
      this.reactiveForm.value.residence_address
    );
    formData.append('residence_phone', this.reactiveForm.value.residence_phone);
    formData.append('email', this.reactiveForm.value.email);
    formData.append('mobile', this.reactiveForm.value.mobile);
    formData.append('mock_recall', this.reactiveForm.value.mock_recall);
    formData.append('public_recall', this.reactiveForm.value.public_recall);
    formData.append(
      'professional_recall',
      this.reactiveForm.value.professional_recall
    );
    formData.append(
      'enforcement_auth_recall',
      this.reactiveForm.value.enforcement_auth_recall
    );
    formData.append('television', this.reactiveForm.value.television);
    formData.append('radio', this.reactiveForm.value.radio);
    formData.append('newspaper', this.reactiveForm.value.newspaper);
    formData.append('cfagents', this.reactiveForm.value.cfagents);
    formData.append('stockiest', this.reactiveForm.value.stockiest);
    formData.append('distributors', this.reactiveForm.value.distributors);
    formData.append('mfg_error', this.reactiveForm.value.mfg_error);
    formData.append('packing_error', this.reactiveForm.value.packing_error);
    formData.append('side_effect', this.reactiveForm.value.side_effect);
    formData.append(
      'abnormal_stability',
      this.reactiveForm.value.abnormal_stability
    );
    formData.append('degradation', this.reactiveForm.value.degradation);
    formData.append(
      'contamination_product',
      this.reactiveForm.value.contamination_product
    );
    formData.append('release_change', this.reactiveForm.value.release_change);
    formData.append('packing_defect', this.reactiveForm.value.packing_defect);
    formData.append(
      'overprint_error_price',
      this.reactiveForm.value.overprint_error_price
    );
    formData.append(
      'overprint_error_batch',
      this.reactiveForm.value.overprint_error_batch
    );
    formData.append(
      'overprint_error_exp',
      this.reactiveForm.value.overprint_error_exp
    );
    formData.append('other_reason', this.reactiveForm.value.other_reason);
    formData.append('product_receive', this.reactiveForm.value.product_receive);
    formData.append('training', this.reactiveForm.value.training);
    formData.append('capa', this.reactiveForm.value.capa);

    formData.append('received_quantity', this.destruction_report);
    formData.append('examination_report', this.examination_report);
    formData.append('destruction_report', this.destruction_report);

    this.service
      .post('qa/recall.php?type=saveProductRecall', formData)
      .subscribe((response) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          this.router.navigate(['qa/recall/']);
          alertify.success('Data Save Successfully');
          this.reactiveForm.reset();
        } else {
          alertify.error('something went wrong');
        }
      });
  }

  viewPlan(index) {
    this.selectedProduct = this.products[index];
    this.isView = true;
  }

  viewPDF(index) {
    this.selectedProduct = this.products[index];
    this.service.open(
      'pdf1/product_recall.php?type=generateRecallPDF&id=' +
        this.selectedProduct.id
    );
  }

  close() {
    this.router.navigate(['/product-recall-dashboard']);
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//
}
