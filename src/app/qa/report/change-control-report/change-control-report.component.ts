import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-change-control-report',
  templateUrl: './change-control-report.component.html',
  styleUrls: ['./change-control-report.component.css']
})
export class ChangeControlReportComponent implements OnInit {
  entries;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getChangeControls();
  }

  getChangeControls() {
    this.service.get('qaDepartment.php?type=getChangeControls').subscribe(response => {
      this.entries = response;
    });
  }

}
