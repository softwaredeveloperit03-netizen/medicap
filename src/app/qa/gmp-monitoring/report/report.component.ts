import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {

  checkLists = [];
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getGMPMonitoringChecklist();
  }

  getGMPMonitoringChecklist() {
    const formData = new FormData();
    formData.append('section', '');
    formData.append('department', '');

    this.service.post('qaDepartment.php?type=getGMPMonitoringChecklist', formData).subscribe(response => {
      this.checkLists = JSON.parse(JSON.stringify(response));
    });
  }

}
