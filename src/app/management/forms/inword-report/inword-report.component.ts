import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-inword-report',
  templateUrl: './inword-report.component.html'
})
export class InwordReportComponent implements OnInit {
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getdata();
  }
  datalist = [];
  getdata(){
    this.service.get('security.php?type=getTotalInword').subscribe((response:any) => {
      this.datalist = response;
    })
  }

}
