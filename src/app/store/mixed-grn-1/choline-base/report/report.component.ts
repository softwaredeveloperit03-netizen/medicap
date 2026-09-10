  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
import { from } from 'rxjs';
  import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css'],
  providers:[DatePipe]
})
export class ReportComponent implements OnInit { 

  isView = false;
  results: any = [];
  selectedResult = [];
  from_date ='';
  to_date ='';
  today ='';
  constructor(private service: DataAccessService,private datePipe : DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
    this.getMixingLog();
  }

  getMixingLog() {
    this.service.get('store/cholinebase.php?type=getMixingLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(){
    this.service.open('store/cholinebase.php?type=downloadMixingLog&id='+this.selectedResult['id'])
  }

}
