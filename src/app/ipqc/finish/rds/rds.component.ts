import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-rds',
  templateUrl: './rds.component.html',
  styleUrls: ['./rds.component.css'],
  providers:[DatePipe]
})
export class RdsComponent implements OnInit {
  from_date='';
  to_date='';
  results;
  selectedResult=[];
  isView=false;

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getInprocessTestings();
  }

  getInprocessTestings(){
    this.service.get('ipqc/finish.php?type=getTestingLog&from_date='+this.from_date +'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }

   
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true; 
  }
  download(id){
    this.service.open('ipqc/finish.php?type=downloadTestingLog&id='+id);
  }


}
