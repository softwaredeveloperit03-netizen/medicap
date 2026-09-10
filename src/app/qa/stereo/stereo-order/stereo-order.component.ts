import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;

@Component({
  selector: 'app-stereo-order',
  templateUrl: './stereo-order.component.html',
  styleUrls: ['./stereo-order.component.css'],
})
export class StereoOrderComponent implements OnInit {
  isNew = false;
  entries;
  vendors;
  dosages;
  products;
  file: File;

  isChecker;
  isApprover;

  departments;
  employees;
  participants = [];
  emp_id = '';
  participant = '';
  department = '';
  // tslint:disable-next-line: variable-name
  vendor_name = '';
  // tslint:disable-next-line: variable-name
  dosage_form = '';
  // tslint:disable-next-line: variable-name
  product_name = '';

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getStereoOrder();
    this.getVendor();
    this.getDosage();
    this.getProduct();
    this.get_rights();

    if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
    } else {
      this.isApprover = false;
    }

    if (localStorage.getItem('checker') === 'true') {
      this.isChecker = true;
    } else {
      this.isChecker = false;
    }
  }

  check(value) {
    this.service
      .get('vendor.php?type=updateStatusCheck&id=' + value)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getStereoOrder();
        }
      });
  }

  approve(value) {
    this.service
      .get('vendor.php?type=updateStatus&id=' + value)
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.getStereoOrder();
        }
      });
  }

  getVendor() {
    this.service.get('vendor.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }

  getDosage() {
    this.service.get('vendor.php?type=getDosage').subscribe((response) => {
      this.dosages = response;
    });
  }

  getProduct() {
    this.products = [];
    this.service
      .get(
        'vendor.php?type=getDosagebyId&selecteddosage_form=' + this.dosage_form
      )
      .subscribe((response) => {
        this.products = response;
      });
  }

  saveForm(data) {
    const formData = new FormData();
    formData.append('vendor_name', data.value.vendor_name);
    formData.append('date', data.value.date);

    formData.append('dosage_form', data.value.dosage_form);
    formData.append('product_name', data.value.product_name);
    formData.append('batch_no', data.value.batch_no);
    formData.append('lic_no', data.value.lic_no);
    formData.append('mfg_date', data.value.mfg_date);

    formData.append('exp_date', data.value.exp_date);
    formData.append('mrp', data.value.mrp);
    formData.append('length_size', data.value.length_size);
    formData.append('width_size', data.value.width_size);
    formData.append('round', data.value.round);
    formData.append('square', data.value.square);
    formData.append('metal', data.value.metal);
    formData.append('rubber', data.value.rubber);
    formData.append('stereo_no', data.value.stereo_no);
    formData.append('file', this.file, this.file.name);

    this.service.post('vendor.php?type=saveStereoform', formData).subscribe(
      (response) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.isNew = false;
          this.getStereoOrder();
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
      },
      (error: Response) => {
        if (error.status === 400) {
          alert('An error has occurred.');
        } else {
          alert('An error has occurred, http status:' + error.status);
        }
      }
    );
  }

  getStereoOrder() {
    this.service.get('vendor.php?type=getStereoOrder').subscribe((response) => {
      this.entries = response;
    });
  }

  onFileChange($event) {
    this.file = $event.target.files[0];
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
