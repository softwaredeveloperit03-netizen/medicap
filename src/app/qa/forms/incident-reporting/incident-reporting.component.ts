import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-incident-reporting',
  templateUrl: './incident-reporting.component.html',
  styleUrls: ['./incident-reporting.component.css']
})
export class IncidentReportingComponent implements OnInit {
  isNew = false;
  report_no;
  report_date;
  totalforms;
  constructor(private service: DataAccessService) {
    this.report_date = new Date().toLocaleDateString();
    this.report_date = new Date().toLocaleDateString();
    let res = this.report_date.split('/');
    this.report_date = res[2] + '-' + res[1] + '-' + res[0];
   }

  ngOnInit() {
    this.getUserForms();
  }

  getUserForms() {
    this.service.get('incidentreporting.php?type=getUserForms')
    .subscribe(response => {
      this.totalforms = response;
    });
  }

  saveUserForm(formData) {
    this.service.post('incidentreporting.php?type=saveUserForm', JSON.stringify(formData.value))
    .subscribe(response => {
      if (response['status'] == 'sucess') {
        alert('Incident Report Saved Successfully');
      } else {
        alert('An error occured, Please try again');
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

}
