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


    results;
    to_date='';
    from_date='';
    constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
      this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
      this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd'); 
    }
    ngOnInit() {
      this.getDeptIndendsLog();
    }
  
    getDeptIndendsLog() {
      this.service.get('purchase/indend/spare.php?type=getIndendsLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
        this.results = response;
      });
    }
    download() {
      this.service.open('purchase/indend/spare.php?type=downloadIndendsLog&from_date='+this.from_date+'&to_date='+this.to_date)
    }
  
  }
  