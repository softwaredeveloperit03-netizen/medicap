import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-cleaning-log',
  templateUrl: './cleaning-log.component.html',
  styleUrls: ['./cleaning-log.component.css']
})
export class CleaningLogComponent implements OnInit {

  isNew = false;
  entries;
  labour_name = '';
  selectedEntry;
  isApprover;
  isChecker;
  isView = false;
  steps = [];
  Labour;

  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
    this.getDistructionDetails();
    this.getLabourDetails();
  }

  addSteps() {
    this.steps[this.steps.length] = this.labour_name;
    this.labour_name = '';
  }

  deleteSteps(index) {
    this.steps.splice(index, 1);
  }

  viewEntry(index) {
    this.selectedEntry = this.entries[index];
    this.isView = true;
  }

  saveForm(data) {
    const formData = new FormData();

    formData.append('activity', data.value.activity);
    formData.append('approx_time', data.value.approx_time);
    formData.append('steps', this.steps.toString());

    this.service.post('admin.php?type=saveDailyWork', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        this.steps = [];
        data.resetForm();
        this.getDistructionDetails();
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

  getLabourDetails() {
    this.service.get('admin.php?type=getLabourDetails').subscribe(response => {
      this.Labour = response;
    });
  }

  getDistructionDetails() {
    this.service.get('admin.php?type=getDailyWorkDetails').subscribe(response => {
      this.entries = response;
    });
  }

  close() {
    this.router.navigate(['/admin/gardev']);
  }

}
