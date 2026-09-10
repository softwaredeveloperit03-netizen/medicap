import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getlog();
  }
  results
  getlog(){
    this.service.get('bmr/process.php?type=stageLinemasterLog').subscribe(response => {
      this.results = response;
    })
  }
  isequip=false;
  equipmentList=[];
  AddEqup(index){
    this.equipmentList=[];
   
    this.isequip=true;
    this.equipmentList=this.results[index]['equipmentList'];
  }
  isStageViewModal=false;
  openStageViewModal(index){
    this.selectedStages=this.results[index]['stages'];
    this.isStageViewModal=true;
  }
  selectedStages=[];
  currentStageViewIndex: number = -1;

}