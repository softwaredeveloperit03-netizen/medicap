import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-advice',
  templateUrl: './advice.component.html',
  styleUrls: ['./advice.component.css'],
})
export class AdviceComponent implements OnInit {
  isView = false;
  isNew = false;

  selectedEntry;
  advice;
  products;

  reactiveForm: FormGroup;
  constructor(private service: DataAccessService, private fb: FormBuilder) {
    this.loggedInDept = localStorage.getItem('department');
    this.reactiveForm = this.fb.group({
      date: ['', [Validators.required]],
      name: ['', [Validators.required]],
      address: ['', [Validators.required]],
      batch_no: ['', [Validators.required]],
      product_name: ['', [Validators.required]],
      capa: ['', [Validators.required]],
      training: ['', [Validators.required]],
    });
  }

  ngOnInit() {
    this.getAdvice();
    this.getProducts();
    this.get_rights();
  }

  getAdvice() {
    this.service
      .get('qaDepartment.php?type=getAdvice')
      .subscribe((response) => {
        this.advice = response;
      });
  }

  getProducts() {
    this.service
      .get('qaDepartment.php?type=getApprovedProducts')
      .subscribe((response) => {
        this.products = response;
      });
  }

  viewPDF(index) {
    this.selectedEntry = this.advice[index];
    this.service.open(
      'pdf1/product_recall.php?type=generateAdvicePDF&id=' +
        this.selectedEntry.id
    );
  }

  saveAdvice() {
    if (this.reactiveForm.valid) {
      alertify.error('All fields are required');
      return;
    }
    const formData = new FormData();
    formData.append('name', this.reactiveForm.value.name);
    formData.append('date', this.reactiveForm.value.date);
    formData.append('address', this.reactiveForm.value.address);
    formData.append('batch_no', this.reactiveForm.value.batch_no);
    formData.append('product_name', this.reactiveForm.value.product_name);
    formData.append('capa', this.reactiveForm.value.capa);
    formData.append('training', this.reactiveForm.value.training);

    this.service
      .post('qaDepartment.php?type=saveAdvice', formData)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.isNew = false;
          this.reactiveForm.reset();
          alertify.success('Success');
          this.getAdvice();
        } else {
          alertify.error('error');
        }
      });
  }

  viewAdvice(index) {
    this.isView = true;
    this.selectedEntry = this.advice[index];
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
