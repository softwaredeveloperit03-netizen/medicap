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

  isView = false;
  results;
  selectedReport = [];

  from_date='';
  to_date='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getMixedGRNs();
  }

  getMixedGRNs() {
    this.service.get('store/raw.php?type=getMixedGRNs&from_date='+this.from_date +'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  downloadRecord(){
    this.service.open('store/raw.php?type=downloadMixedGRNsRecord&id='+this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=downloadMixedGRNs&from_date='+this.from_date +'&to_date='+this.to_date);
  }

}
