import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  selectedResult;
  equipment_name='';
  from_date='';
  to_date='';
  equipment_type='';
  category;
  selectedData=[];
  isView=false;

  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getEquipmentCleaningLog();
    this.getCategory();
  }
  getCategory(){
    this.service.get('equipments.php?type=getEquipmentCategories').subscribe(response=>{
      this.category=response;
    });
  }

  getName(index){
    index=index-1;
    if(index !==-1){
      this.selectedData=this.category[index];
    }
  }
  getEquipmentCleaningLog(){
    this.service.get('equipments.php?type=getEquipmentCleaningLog&equipment_name='+this.equipment_name +'&from_date='+this.from_date+'&to_date='+this.to_date +'&equipment_type='+this.equipment_type).subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  download(){
    this.service.open('equipments.php?type=downloadEquipmentCleaningLog&equipment_name='+this.equipment_name +'&from_date='+this.from_date+'&to_date='+this.to_date +'&equipment_type='+this.equipment_type);
  }

}
