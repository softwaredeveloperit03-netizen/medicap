import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-coa',
  templateUrl: './coa.component.html',
  styleUrls: ['./coa.component.css'],
  providers:[DatePipe]
})
export class CoaComponent implements OnInit {
  from_date='';
  to_date='';
  today='';
  results;
  selectedResult=[];
  isView=false; 
  grades;
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');   
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');    
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getInprocessTestings(); 
  }
  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
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

  download(){
    this.service.open('ipqc/finish.php?type=downloadTestingLog&from_date='+this.from_date +'&to_date='+this.to_date);
  }


  downloadReport(){
    this.service.open('ipqc/finish.php?type=downloadTestingLog&id='+this.selectedResult['id']);
  }


}
