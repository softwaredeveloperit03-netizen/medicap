import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css'],
  providers:[DatePipe]
})
export class ListComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  equipment_name='';
  from_date='';
  to_date='';
  equipment_type='';
  category;
  selectedData=[];
  constructor(private service:DataAccessService, private datePipe: DatePipe) { 
    this.from_date = this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }


  ngOnInit(): void {
    this.getEquipmentList();
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

  getEquipmentList(){
    this.service.get('store/equipment.php?type=getEquipmentList&equipment_type='+this.equipment_type).subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  download(){
    this.service.open('store/equipment.php?type=downloadEquipmentList&equipment_type='+this.equipment_type);
  }
}
