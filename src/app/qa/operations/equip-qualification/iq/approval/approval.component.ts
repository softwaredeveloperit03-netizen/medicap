import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
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
    this.getPendingIq();
  }
  getPendingIq(){
    this.service.get('qa/qualification_iq.php?type=getCheckedIq').subscribe(response=>{
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

  
updateIq(status) {
    this.service.get('qa/qualification_iq.php?type=updateIq&status=' + status + '&id=' + this.selectedResult['id'] ).subscribe(response => {
      if (response['status']) {
        alert('data Updated Successfully');
        this.isView = false;
        this.getPendingIq();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  

}
 

