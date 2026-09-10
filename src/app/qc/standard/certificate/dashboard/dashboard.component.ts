import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  from_date = '';
  to_date = '';
  isView = false;
  results;
  grndetails=[];
  selectedReport = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd'); }

  ngOnInit() {
    this.getGRNLog();
  }

  getGRNLog() {
    this.service.get('qc/standard/receiving.php?type=getGRNLog&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.grndetails=this.selectedReport['grn_details'];
    this.isView = true;
  }

  download() {
    this.service.open('qc/chemical.php?type=GRNPDF&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('qc/chemical.php?type=GRNLogPDF&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
