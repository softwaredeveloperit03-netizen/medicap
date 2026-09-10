import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css'],
  providers:[DatePipe]
})
export class CheckingComponent implements OnInit {

  results;
  from_date='';
  to_date='';
  isview=false;
  selectedResult=[];
  units;
  company_unit='';

 constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit() {
    this.getTechnicalLog();
    this.getUnits();
  }
  getTechnicalLog(){
    // this.service.get('production/technical.php?type=getInprocessTechnicalLog&company_unit='+this.company_unit+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.service.get('production/technical.php?type=getInprocessTechnicals').subscribe(response=>{
      this.results=response;
    });
  }
  getUnits(){
    this.service.get('common.php?type=getCompanyUnits').subscribe(response=>{
      this.units=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isview=true;
  }
  approve(status){
     this.service.get('production/technical.php?type=checkTestingReport&id='+this.selectedResult['id']+'&status='+status).subscribe(response =>{
      if(response['status']=='success'){
        this.getTechnicalLog();
        this.isview = false;
        alertify.success("Approve Successfully !!!");
      }else{
        alertify.error("Error to Approve !!!");
      }
    });
  }

}
