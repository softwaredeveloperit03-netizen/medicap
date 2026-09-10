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
  selectedResult=[];
  isView=false;
  from_date='';
  to_date='';
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  ngOnInit(): void {
    this.getEnvInvestigations();
  }
  getEnvInvestigations(){
    this.service.get('microbiology/isolate.php?type=getEnvInvestigations&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('microbiology/isolate.php?type=downloadEnvInvestigations&id='+this.selectedResult['id']);
  }

}
