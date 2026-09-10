import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation-form',
  templateUrl: './deviation-form.component.html',
  styleUrls: ['./deviation-form.component.css']
})
export class DeviationFormComponent implements OnInit {
  report_no;
  reporting_date;
  tempdate;
  constructor(private service: DataAccessService) {
    this.reporting_date = new Date().toLocaleDateString();
    let res = this.reporting_date.split('/');
    this.reporting_date = res[2] + '-' + res[1] + '-' + res[0];
    console.log(this.reporting_date);
   }

  ngOnInit() {
    this.getReportNo();
  }

  getReportNo() {
    this.service.get('incidentreporting.php?type=getReportNo')
    .subscribe(response => {
      this.report_no = response['form_no'];
    });
  }

  viewdate() {
    console.log(this.tempdate);
  }
}
