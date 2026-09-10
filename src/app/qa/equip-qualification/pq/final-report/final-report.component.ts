import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-final-report',
  templateUrl: './final-report.component.html',
  styleUrls: ['./final-report.component.css'],
})
export class FinalReportComponent implements OnInit {
  results: any[] = [];
  isView: boolean = false;
  selectedReport: any = {};
  // selectedReport: any = {}; // Object to store selected report details

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingpq();
  }
  //  .get(
  //       'qa/qualification.php?type=getRequest&Id=' + this.selectedReport['id'],
  //       JSON.stringify(temp)

  getPendingpq() {
    this.service
      .get('qa/qualification.php?type=getRequest1')
      .subscribe((response: any) => {
        this.results = response;
        console.log(this.results);
      });
  }

  view(index: number) {
    this.selectedReport = this.results[index];
    console.log(this.selectedReport);
    this.isView = true;
  }

  // getChangeControls() {
  //   this.service
  //     .get(
  //       'qa/capa.php?type=getcapalog&fromdate=' +
  //         this.fromdate +
  //         '&todate=' +
  //         this.todate
  //     )
  //     .subscribe((response: any) => {
  //       this.results = response;
  //     });
  // }
}

