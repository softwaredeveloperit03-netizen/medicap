import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-material',
  templateUrl: './material.component.html',
  styleUrls: ['./material.component.css']
})
export class MaterialComponent implements OnInit {
  isView = false;
  isNew = false;
  selectedEntry;
  isApprover;
  isChecker;
  list;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
    this.getMateriallist();

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



  update(status) {
    this.service.get('admin.php?type=updateMaterial&id=' + this.selectedEntry.id +
    '&status=' + status).subscribe(response => {
      alert('Updated Successfully');
      this.isView = false;
      this.getMateriallist();
    });
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getMateriallist() {
    this.service.get('admin.php?type=getMateriallist').subscribe(response => {
      this.list = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('material', data.value.material);
      formData.append('no_required', data.value.no_required);
      formData.append('company', data.value.company);
      formData.append('details', data.value.details);

      this.service.post('admin.php?type=AddHousekeepingData', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getMateriallist();
          this.isNew = false;
          alert('Successfully Send for Approval');
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
    this.router.navigate(['/housekeeping-dashboard']);
  }


}
