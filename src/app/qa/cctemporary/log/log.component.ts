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
  isView=false;
  selectedResult=[];
  from_date='';
  to_date='';
  
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {   
       this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.getCCLog();
  }

  getCCLog(){
    this.service.get('qms/cctemporary.php?type=getCCLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/cctemporary/' + link);
  }

  downloadRecord(){
    this.service.open('qms/cctemporary.php?type=downloadCCLog&id='+this.selectedResult['id']);
  }

  downloadLog(){
    this.service.open('qms/cctemporary.php?type=downloadCCRecord&from_date='+this.from_date+'&to_date='+this.to_date +'&cc_no='+this.selectedResult['cc_no']);
  }

}
