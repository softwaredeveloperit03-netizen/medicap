import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  from_date='';
  to_date='';
  
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {   
       this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }


  ngOnInit(): void {
    this.getDeviationLog();
  }

  getDeviationLog(){
    this.service.get('qms/deviation.php?type=getDeviationLog').subscribe(response=>{
      this.results=response;
    });
  }

  viewfile(link){
    window.open(this.service.url + 'upload/deviation/' + link);
  }


  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  downloadRecord(){
    this.service.open('qms/deviation.php?type=downloadDeviationRecord&id='+this.selectedResult['id']+'&deviation_no='+this.selectedResult['deviation_no']);
  }

  downloadLog(){
    this.service.open('qms/deviation.php?type=downloadDeviationLog&from_date='+this.from_date+'&to_date='+this.to_date);
  }
 
}
