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
  selectedMaterial=[];
  isShow=false;
  from_date='';
  product_type='';
  to_date='';
  today='';
  company;
  company_unit='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe) {     
     this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');    
   this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   
   this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }

  ngOnInit(): void {
    this.getDispensingLog();
    this.getCompanyUnit();
  }

  getDispensingLog(){
    this.service.get('store/dispensing.php?type=getDispensingLog&dosage_form='+this.product_type+'&to_date='+this.to_date+'&from_date='+this.from_date+'$company_unit='+this.company_unit).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  getCompanyUnit(){
    this.service.get('common.php?type=getCompanyUnits').subscribe(response=>{
      this.company=response;
    });
  }

  show(index){
    let material=this.selectedResult['materials'];
    this.selectedMaterial=material[index];
    this.isShow=true;
  }
  download(){
    this.service.open('store/dispensing.php?type=downloadDispensingLog&product_type='+this.product_type+'&to_date='+this.to_date+'&from_date='+this.from_date);
  }

  downloadRecord(){
    this.service.open('store/dispensing.php?type=downloadDispensingRecord&id='+this.selectedResult['id']);
  }

}
