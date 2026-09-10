import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  results;
  selectedResult=[];
  isView=false;
  department_list;

  utilityList;
  equipment_list;
  remark_add='';
  statusdata='';
  remark_m='';
  status_m='';
  updateInstal=[];
  task;
  installData=[];
  machineData=[];
  blankData=[];
  isOk=false;
  isNotok=false;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingIq();
  }
  getPendingIq(){
    this.service.get('qa/qualification_iq.php?type=getPendingIq').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    console.log(this.selectedResult);
    
    this.department_list=this.selectedResult['department'];
    this.equipment_list=this.selectedResult['equipment'];
    this.utilityList=this.selectedResult['utility'];

    this.installData=this.selectedResult['installation_check'];
    for(let i=0;i<this.installData.length;i++){
      let material=this.installData[i];
      material['status'] = '';
      material['remark'] = '';
      this.installData[i] = material;
    }
    this.machineData=this.selectedResult['machine_check'];
    for(let i=0;i<this.machineData.length;i++){
      let material=this.machineData[i];
      material['status'] = '';
      material['remark'] = '';
      this.machineData[i] = material;
    }

    this.blankData=this.selectedResult['blank_check'];
    for(let i=0;i<this.blankData.length;i++){
      let material=this.blankData[i];
      material['status'] = '';
      material['remark'] = '';
      this.blankData[i] = material;
    }

    this.isView=true;
  }


  getddat(value){
    this.updateInstal.push({name: this.remark_add});
    this.task = '';
  }

  getFound(show){
    if(show=='ok'){
      this.isOk=true;
      this.isNotok=false;
    }else if(show=='not_ok'){
      this.isOk=false;
      this.isNotok=true;
    }
  }
  getstatus(event,index){
    console.log('dd',event);
  }

  updatemachine=[];
  
  checkIq(data,status){
    let temp=data.value;
    console.log('install',this.installData);
    temp['machine_check']=this.machineData;
    temp['installation_check']=this.installData;
    temp['blank_check']=this.blankData;
    this.selectedResult['installation_check'] = this.installData;
       this.service.post('qa/qualification_iq.php?type=saveCheckpointIq&id='+ this.selectedResult['id'] +'&status='+status,JSON.stringify(temp)).subscribe(response=>{
        if(response['status']=='success'){
          alertify.success("save data Succesffuly");
          this.getPendingIq();
          this.isView=false;
        }else{
          alertify.error("some Error Occured!");
        }
      });
    }
}
 



