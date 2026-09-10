import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-postapproval1',
  templateUrl: './postapproval1.component.html',
  styleUrls: ['./postapproval1.component.css']
})
export class Postapproval1Component implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  plant_id;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInitiatedCC();
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

  getInitiatedCC(){
    this.service.get('qms/ccpermanant.php?type=getPendingPostApprovalbyCQA').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  viewfile(link) {
    window.open(this.service.url + 'upload/ccpermanant/' + link);
  }

  save(data){
    if(!data.valid){
      alertify.error('All feilds are required');
      return;
    }
    let temp=data.value;
    temp['cc_no']=this.selectedResult['cc_no'];
    this.service.post('qms/ccpermanant.php?type=savePostApprovalChecking'+'&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isView = false;
        this.getInitiatedCC();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

}
