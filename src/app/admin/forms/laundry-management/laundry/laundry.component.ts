import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-laundry',
  templateUrl: './laundry.component.html'
})
export class LaundryComponent implements OnInit {

  isView = false;
  isNew = false;
  department_name ='';
  department;
  selectedEntry;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getDepartments();
    this.getLaundrylist();
  }

  viewEntry(index) {
  this.selectedEntry = this.list[index];
  this.isView = true;
  }

  open(url) {
    window.open(url, '_blank')
  }

  getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.department = response;
    });
  }

  getLaundrylist() {
    this.service.get('admin.php?type=getLaundrylist').subscribe(response => {
      this.list = response;
    });
  }

   saveForm(data) {
      const formData = new FormData();

      formData.append('department_name', data.value.department_name);
      formData.append('uniform_type', data.value.uniform_type);
      formData.append('no_units', data.value.no_units);
      formData.append('size', data.value.size);

      this.service.post('admin.php?type=AddLaundryData', formData).subscribe(response => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getLaundrylist();
          this.isNew = false;
          alert('Successfully Saved');
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
    this.router.navigate(['/laundry-dashboard']);
  }

}

