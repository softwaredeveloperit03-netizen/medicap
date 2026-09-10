import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
   import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-bill-contractor',
  templateUrl: './bill-contractor.component.html',
  styleUrls: ['./bill-contractor.component.css']
})
export class BillContractorComponent implements OnInit {
  isView = false;
  isNew = false;
  selectedEntry;
  file: File;
  file_bill1: File;
  list;
  bill1;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

    
  }

  ngOnInit() {
    this.getContaractorBillData();
    this.get_rights();
  }

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
          localStorage.getItem('department')
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

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.bill1 = this.service.url + this.selectedEntry['file_bill1'];
    this.isView = true;
  }

  open(url) {
    url = this.service.url + '../../upload/contractor/bills/' + url;
    window.open(url, '_blank');
  }

  getContaractorBillData() {
    this.service.get('admin.php?type=getContaractorBillData').subscribe(response => {
      this.list = response;
    });
  }

  selectedFile: File;
  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
  }


 
   saveForm(data) {
      const formData = new FormData();

      if (this.selectedFile !== undefined) {
        formData.append('file_bill1', this.selectedFile, this.selectedFile.name);
      } 

      formData.append('month', data.value.month);
      formData.append('labours_no', data.value.labours_no);
      formData.append('gents', data.value.gents);
      formData.append('gents_per_wages', data.value.gents_per_wages);
      formData.append('operators', data.value.operators);
      formData.append('operators_per_wages', data.value.operators_per_wages);
      formData.append('womens', data.value.womens);
      formData.append('womens_per_wages', data.value.womens_per_wages);
 
      this.service.post('admin.php?type=AddContaractorBill', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getContaractorBillData();
          this.isNew = false;
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
      });
    }

  close() {
    this.router.navigate(['/hr']);
  }

}
