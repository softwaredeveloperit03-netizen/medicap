import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isView=false;
  results;
  selectedResult;
  stages;
    selectedCheckList: any;
    selectedChec: any;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    // this.getStages();
    this.getData();
  }
  getData() {
   
    this.service.get('hr/appraisalchecklist.php?type=getChecklist').subscribe(response => {
      this.results = response;
    
    });
  }
  // getStages(){
  //   this.service.get('production/stage.php?type=get_iqpc_stages').subscribe(response => {
  //     this.results = response;
  //   })
  // }

  // view(val){
  //   this.selectedResult=this.results[val];
  //   this.stages = JSON.parse(this.selectedResult['stages_test']);
  //   this.isview = true;
  // }

  // view(index)
  // {
  //   this.isView =  true ;
  //   this.selectedCheckList = this.results[index];
  //   this.selectedChec = this.selectedCheckList.checkList[index];
  // }

  view(index) {
    this.selectedCheckList = this.results[index];
    this.isView = true;
  }

}
