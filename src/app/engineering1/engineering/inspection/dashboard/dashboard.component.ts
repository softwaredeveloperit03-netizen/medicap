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

  equipments;
  from_date='';
  to_date='';
  selectedResult=[];
  isView=false;
  checkpoints=[];

  constructor(private service:DataAccessService,private datePipe:DatePipe) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');     
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getEquipments();

  }
  getEquipments(){
    this.service.get('engineering/inspection.php?type=getInspectionsLog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.equipments = response;
    });
  }
  view(index){
    this.selectedResult=this.equipments[index];
    this.checkpoints=this.selectedResult['checkpoints'];
    this.isView=true;
  }
  download(){
    this.service.open('engineering/inspection.php?type=downloadInspectionsLog&from_date='+this.from_date+'&to_date='+this.to_date)
  }

}
