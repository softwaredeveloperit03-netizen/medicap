import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify; 
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit { 
  isView = false;
  results: any = [];
  selectedResult = [];
  from_date = '';
  to_date = '';
  today = '';
  constructor(private service: DataAccessService,private datePipe :DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getAwaitingBatches();
  }

  getAwaitingBatches() {
    this.service.get('production/plant9/transfer.php?type=getTransferLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(){
    this.service.open('production/plant9/transfer.php?type=downloadTransferNote&id='+this.selectedResult['id']);
  }

}
