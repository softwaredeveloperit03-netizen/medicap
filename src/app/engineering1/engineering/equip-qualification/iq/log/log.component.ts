import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  selectedResult=[];
  department_list=[];
  equipment_list=[];
  utilityList=[];
  installList=[];
  machineList=[];
  blankList=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getLogIq();
  }
  getLogIq(){
    this.service.get('qa/qualification_iq.php?type=getLogIq').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    
    this.department_list=this.selectedResult['department'];
    this.equipment_list=this.selectedResult['equipment'];
    this.utilityList=this.selectedResult['utility'];

    this.blankList=this.selectedResult['blank_check'];
    this.machineList=this.selectedResult['machine_check'];
    this.installList=this.selectedResult['installation_check'];
    this.isView=true;
  }

}
