import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-resources',
  templateUrl: './resources.component.html',
  styleUrls: ['./resources.component.css']
})
export class ResourcesComponent implements OnInit {

  formopen = false;
  form;
  list;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit() {
    this.getResourcelist();
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


  getResourcelist() {
    this.service.get('admin.php?type=getResourcelist').subscribe((response: any) => {
      this.list = response;
    });
  }
  submit(form) {
    if (form.valid) {
      this.service.post('admin.php?type=addResource', JSON.stringify(form.value)).subscribe(response => {
        if (response['status'] === 'success') {
          alert('Record Inserted Successfully');
          this.getResourcelist();
          this.formopen = false;
        } else {
          alert('Please try Again');
        }
      });
    } else {
      alert('Enter Correct Data');
    }
  }
  addclientbtn() {
    this.formopen = true;
  }
  closeclientbtn() {
    this.formopen = false;
  }

}
