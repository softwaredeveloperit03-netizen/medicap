import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {

  isView = false;
  results;
  selectedPlan = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTestingReport();
  }

  getTestingReport() {
    this.service.get('qc/water.php?type=getTestingReport').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedPlan = this.results[index];
    this.isView = true;
  }

}
