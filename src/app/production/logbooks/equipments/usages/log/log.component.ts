import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]

})
export class LogComponent implements OnInit {
  results;
  selectedResult;
  selectedData=[];
  isView=false;
  equipment_name=''
  from_date='';
  to_date='';
  category;
  equipment_type='';
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getEquipmentUsagesLog();
    this.getCategory();
  }

  getCategory(){
    this.service.get('equipments.php?type=getEquipmentCategories').subscribe(response=>{
      this.category=response;
    });
  }
  getEquipmentUsagesLog(){
    this.service.get('equipments.php?type=getEquipmentUsagesLog&equipment_name='+this.equipment_name +'&from_date='+this.from_date+'&to_date='+this.to_date +'&equipment_type='+this.equipment_type).subscribe(response=>{
      this.results=response;
    });
  }
  getName(index){
    index=index-1;
    if(index !==-1){
      this.selectedData=this.category[index];
    }
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
