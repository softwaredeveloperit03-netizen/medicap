import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  form: FormGroup;
  isView=false;
  selectedResult=[];
  installList=[];
  machineList=[];
  blankList=[];
  constructor(private service:DataAccessService,private formBuilder: FormBuilder) { }

  ngOnInit(): void {
    this.getIQLog();
  }

  getIQLog() {
    this.service.get('qa/qualification.php?type=getRequest').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  addbtn(data){
    this.installList[this.installList.length]=data.value;
    data.resetForm();
  }
  addmachinbtn(data){
    this.machineList[this.machineList.length]=data.value;
    data.resetForm();
  }

  deleteList(index){
    this.installList.splice(index,1);
  }
  deleteMachinList(index){
    this.machineList.splice(index,1)
  }
  addblankbtn(data){
    this.blankList[this.blankList.length]=data.value;
    data.resetForm();
  }
  deleteblank(index){
    this.blankList.splice(index,1);
  }
  save(data){
    let temp=data.value;
    temp['equipment_name']=this.selectedResult['equip_name'];
    temp['department_name']=this.selectedResult['department_name'];
    temp['section_name']=this.selectedResult['section_name'];
     temp['installation_check']=this.installList;
     temp['blank_check']=this.blankList;
     temp['machine_check']=this.machineList;
     this.service.post('qa/qualification.php?type=saveIq',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.error("save data Succesfuly");
        this.getIQLog();
        this.isView=false;
      }else{
        alertify.error("some Error Occured!");
      }
    });

  }

}
