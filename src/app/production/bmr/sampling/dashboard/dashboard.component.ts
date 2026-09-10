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
  from_date='';
  to_date='';
  selectedResult=[];
  isView=false;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {      
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getIntimationLog();
  }
  getIntimationLog(){
    this.service.get('production/bmr/sampling.php?type=getIntimationLog&from_date='+this.from_date+ '&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download(){
    this.service.open('production/bmr/sampling.php?type=downloadIntimationSlip&from_date='+this.from_date+ '&to_date='+this.to_date);
  }

  downloadReport(){
    this.service.open('production/bmr/sampling.php?type=downloadIntimationRecord&id='+ this.selectedResult['id']);
  }

}
