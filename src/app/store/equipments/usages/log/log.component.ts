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
  equipment_name=''
  from_date='';
  to_date='';
  category;
  equipment_type='';
  equipment_code='';
  today=''
  results1=[];
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
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
    this.service.get('equipments.php?type=getEquipmentUsagesLog&equipment_id='+this.equipment_code +'&from_date='+this.from_date+'&to_date='+this.to_date +'&equipment_type='+this.equipment_type).subscribe(response=>{
      this.results=response;
      this.filterEquipment();
    });
  }

  filterEquipment() {
    this.results1 = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['equipment_code'].toUpperCase().includes(this.equipment_code.toUpperCase())) {
        this.results1[this.results1.length] = material;
      }
    }
  }
  getName(index){
    index=index-1;
    if(index !==-1){
      this.selectedData=this.category[index];
    }
  }
  download(){
    this.service.open('equipments.php?type=downloadEquipmentUsagesLog&equipment_name='+this.equipment_name +'&from_date='+this.from_date+'&to_date='+this.to_date +'&equipment_type='+this.equipment_type)
  }


}
