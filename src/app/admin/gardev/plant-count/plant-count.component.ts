import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plant-count',
  templateUrl: './plant-count.component.html',
  styleUrls: ['./plant-count.component.css']
})
export class PlantCountComponent implements OnInit {

  isNew = false;
  entries;
  selectedEntry;
  isView = false;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit() {
    this.getPlantDetails();
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


  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  saveForm(data) {
    const formData = new FormData();

    formData.append('plant_name', data.value.plant_name);
    formData.append('count', data.value.count);
    formData.append('plantation_date', data.value.plantation_date);
    formData.append('plant_age', data.value.plant_age);

    this.service.post('admin.php?type=savePlantCount', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        data.resetForm();
        this.getPlantDetails();
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

  getPlantDetails() {
    this.service.get('admin.php?type=getPlantCount').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/admin/gardev']);
  }

}
